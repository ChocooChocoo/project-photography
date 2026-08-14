<?php

namespace Tests\Feature\Dashboard;

use App\Http\Controllers\Client\DashboardController;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiscoveryRankingTest extends TestCase
{
    private UserModel $client;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');
        $this->client = $this->createClient();
        $this->actingAs($this->client);

        Route::get('/_test/client/dashboard', [DashboardController::class, 'index']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_featured_studio_flagged_in_marketplace_index(): void
    {
        $featured = $this->createStudio('Featured Studio');
        $regular = $this->createStudio('Regular Studio');
        $this->createSubscription($featured, 5);
        $this->createSubscription($regular, 1);

        $response = $this->get('/_test/client/dashboard');

        $response->assertOk();
        $featuredIds = $response->viewData('featuredStudioIds');
        $this->assertContains($featured->id, $featuredIds);
        $this->assertNotContains($regular->id, $featuredIds);
    }

    public function test_unsubscribed_studio_is_not_listed(): void
    {
        $subscribed = $this->createStudio('Subscribed Studio');
        $unsubscribed = $this->createStudio('Unsubscribed Studio');
        $this->createSubscription($subscribed, 2);

        $response = $this->get('/_test/client/dashboard');

        $response->assertOk();
        $studioIds = $response->viewData('studios')->pluck('id');
        $this->assertContains($subscribed->id, $studioIds);
        $this->assertNotContains($unsubscribed->id, $studioIds);
    }

    public function test_featured_badge_renders_on_dashboard(): void
    {
        $featured = $this->createStudio('Featured Studio');
        $this->createSubscription($featured, 5);

        $response = $this->get('/_test/client/dashboard');

        $html = $response->getOriginalContent()->render();
        $this->assertStringContainsString('Featured studios are verified premium members', $html);
    }

    private function createClient(): UserModel
    {
        return UserModel::create([
            'role' => 'client',
            'user_type' => 'client',
            'first_name' => 'Discovery',
            'last_name' => 'Client',
            'email' => 'discovery-client@example.com',
            'mobile_number' => '09170000003',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->client->id,
            'studio_name' => $name,
            'status' => 'verified',
        ]);
    }

    private function createSubscription(StudiosModel $studio, int $priorityLevel): StudioPlanModel
    {
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'plan_code' => 'PLAN-' . str()->upper(str()->random(8)),
            'name' => 'Plan ' . $priorityLevel,
            'price' => 590,
            'commission_rate' => 5,
            'trial_days' => 0,
            'priority_level' => $priorityLevel,
            'status' => 'active',
        ]);

        return StudioPlanModel::create([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-' . str()->upper(str()->random(10)),
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'next_billing_date' => '2026-09-01',
            'amount_paid' => 590,
            'payment_status' => 'paid',
            'status' => 'active',
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
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('starting_price', 10, 2)->default(0);
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_freelancers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('brand_name')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('starting_price', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('municipality');
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('pvt_freelancer_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('category_id');
            $table->timestamps();
        });
        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('priority_level')->default(0);
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_client_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('studio_id');
            $table->timestamps();
            $table->unique(['client_id', 'studio_id']);
        });
    }
}
