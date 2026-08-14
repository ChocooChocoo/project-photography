<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_onboarding_page_renders_with_next_and_skip_controls(): void
    {
        $client = $this->createUser('client');

        $response = $this->actingAs($client)->get(route('onboarding'));

        $response->assertOk();
        $html = $response->getOriginalContent()->render();
        $this->assertStringContainsString('Next', $html);
        $this->assertStringContainsString('Skip', $html);
        $this->assertStringContainsString(action([\App\Http\Controllers\OnboardingController::class, 'complete']), $html);
    }

    public function test_onboarding_complete_sets_timestamp_and_redirects_to_dashboard(): void
    {
        $client = $this->createUser('client');

        $this->actingAs($client)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('client.dashboard'));

        $this->assertNotNull($client->fresh()->onboarding_completed_at);
    }

    public function test_onboarding_redirects_to_dashboard_when_already_completed(): void
    {
        $client = $this->createUser('client');
        $client->update(['onboarding_completed_at' => now()]);

        $this->actingAs($client)
            ->get(route('onboarding'))
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_onboarding_redirects_owner_to_owner_dashboard(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('owner.dashboard'));
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Onboard',
            'last_name' => 'Test',
            'email' => $role.'-onboarding@example.com',
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
            $table->string('suffix')->nullable();
            $table->string('org_role')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }
}
