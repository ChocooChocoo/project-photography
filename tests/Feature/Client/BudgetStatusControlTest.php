<?php

namespace Tests\Feature\Client;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BudgetStatusControlTest extends TestCase
{
    private const STATUS_LABEL = '<label class="form-label">Status</label>';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-09-23 10:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_create_form_renders_status_label_and_select(): void
    {
        $response = $this->actingAs($this->createClient())->get(route('client.budget.index'));

        $response->assertOk();

        $html = $response->getOriginalContent()->render();

        $this->assertStringContainsString(self::STATUS_LABEL, $html);
        $this->assertStringContainsString(
            '<select id="budget_status" name="status" class="form-select" required>',
            $html
        );

        // app.js hides document.getElementById("status") on window load while it
        // cleans up the theme preloader. A form control must not own that id, or
        // the control disappears for the user even though the markup is present.
        $this->assertStringNotContainsString('id="status"', $html);
    }

    public function test_edit_template_renders_status_label_and_select(): void
    {
        $response = $this->actingAs($this->createClient())->get(route('client.budget.index'));

        $response->assertOk();

        $html = $response->getOriginalContent()->render();

        $this->assertStringContainsString(
            '<select id="edit_status" name="status" class="form-select" required>',
            $html
        );
        $this->assertSame(2, substr_count($html, self::STATUS_LABEL));
    }

    private function createClient(): UserModel
    {
        return UserModel::create([
            'role' => 'client',
            'user_type' => 'photographer',
            'first_name' => 'Budget',
            'last_name' => 'Test',
            'email' => 'budget-status@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_client_budget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->string('budget_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('minimum_budget', 10, 2)->nullable();
            $table->decimal('maximum_budget', 10, 2)->nullable();
            $table->decimal('preferred_budget', 10, 2)->nullable();
            $table->decimal('spent_amount', 10, 2)->nullable();
            $table->foreignId('category_id')->nullable();
            $table->string('budget_type')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
