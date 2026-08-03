<?php

namespace Tests\Feature;

use App\Http\Controllers\StudioOwner\SubscriptionController;
use App\Http\Requests\StudioOwner\SubscribeRequest;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use App\Services\StripeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function test_trial_creation_uses_the_exact_trial_deadline_for_all_dates(): void
    {
        $owner = UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Trial',
            'last_name' => 'Owner',
            'email' => 'trial-owner@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Trial Studio',
            'status' => 'active',
        ]);
        $owner->setRelation('studio', $studio);
        Auth::setUser($owner);

        $plan = $this->createPlan(['trial_days' => 14, 'billing_cycle' => 'yearly']);
        $request = SubscribeRequest::create('/subscription/subscribe', 'POST', ['plan_id' => $plan->id]);
        $controller = new SubscriptionController(Mockery::mock(StripeService::class));

        $response = $controller->subscribe($request);
        $subscription = StudioPlanModel::sole();

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->getData(true)['trial']);
        $this->assertSame('2026-08-17 10:30:00', $subscription->trial_ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-17', $subscription->end_date->toDateString());
        $this->assertSame('2026-08-17', $subscription->next_billing_date->toDateString());
    }

    public function test_trial_becomes_inactive_at_its_exact_deadline(): void
    {
        $subscription = $this->createSubscription([
            'trial_ends_at' => '2026-08-04 10:30:00',
            'end_date' => '2026-08-04',
            'next_billing_date' => '2026-08-04',
        ]);

        Carbon::setTestNow('2026-08-04 10:29:59');
        $this->assertTrue($subscription->isActive());

        Carbon::setTestNow('2026-08-04 10:30:00');
        $this->assertFalse($subscription->isActive());
    }

    public function test_currently_active_scope_uses_trial_and_paid_boundaries(): void
    {
        $futureTrial = $this->createSubscription([
            'subscription_reference' => 'SUB-FUTURE-TRIAL',
            'trial_ends_at' => '2026-08-04 10:30:00',
            'end_date' => '2026-08-04',
            'next_billing_date' => '2026-08-04',
        ]);
        $this->createSubscription([
            'subscription_reference' => 'SUB-EXPIRED-TRIAL',
            'trial_ends_at' => '2026-08-03 10:30:00',
            'end_date' => '2026-08-03',
            'next_billing_date' => '2026-08-03',
        ]);
        $paidThroughToday = $this->createSubscription([
            'subscription_reference' => 'SUB-PAID-TODAY',
            'trial_ends_at' => null,
            'end_date' => '2026-08-03',
            'next_billing_date' => '2026-08-03',
        ]);
        $this->createSubscription([
            'subscription_reference' => 'SUB-PAID-PAST',
            'trial_ends_at' => null,
            'end_date' => '2026-08-02',
            'next_billing_date' => '2026-08-02',
        ]);

        $activeIds = StudioPlanModel::currentlyActive()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$futureTrial->id, $paidThroughToday->id], $activeIds);
        $this->assertTrue($paidThroughToday->isActive());
    }

    public function test_expiry_command_updates_only_due_subscriptions_and_is_safe_to_rerun(): void
    {
        $expiredTrial = $this->createSubscription([
            'subscription_reference' => 'SUB-DUE-TRIAL',
            'trial_ends_at' => '2026-08-03 10:30:00',
            'end_date' => '2026-08-03',
            'next_billing_date' => '2026-08-03',
        ]);
        $expiredPaid = $this->createSubscription([
            'subscription_reference' => 'SUB-DUE-PAID',
            'trial_ends_at' => null,
            'end_date' => '2026-08-02',
            'next_billing_date' => '2026-08-02',
        ]);
        $futureTrial = $this->createSubscription([
            'subscription_reference' => 'SUB-SAFE-TRIAL',
            'trial_ends_at' => '2026-08-04 10:30:00',
            'end_date' => '2026-08-04',
            'next_billing_date' => '2026-08-04',
        ]);
        $paidThroughToday = $this->createSubscription([
            'subscription_reference' => 'SUB-SAFE-PAID',
            'trial_ends_at' => null,
            'end_date' => '2026-08-03',
            'next_billing_date' => '2026-08-03',
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput('Expired 2 subscription(s).')
            ->assertSuccessful();

        $this->assertSame('expired', $expiredTrial->fresh()->status);
        $this->assertSame('expired', $expiredPaid->fresh()->status);
        $this->assertSame('active', $futureTrial->fresh()->status);
        $this->assertSame('active', $paidThroughToday->fresh()->status);
        $this->assertSame('paid', $expiredTrial->fresh()->payment_status);

        $this->artisan('subscriptions:expire')
            ->expectsOutput('Expired 0 subscription(s).')
            ->assertSuccessful();
    }

    private function createPlan(array $overrides = []): SubscriptionPlanModel
    {
        return SubscriptionPlanModel::create(array_merge([
            'user_type' => 'studio',
            'plan_type' => 'premium',
            'billing_cycle' => 'monthly',
            'plan_code' => 'STU_PREMIUM_MON',
            'name' => 'Premium',
            'description' => 'Test plan',
            'price' => 1999,
            'commission_rate' => 5,
            'trial_days' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function createSubscription(array $overrides = []): StudioPlanModel
    {
        $plan = SubscriptionPlanModel::first() ?? $this->createPlan();

        return StudioPlanModel::create(array_merge([
            'studio_id' => 1,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'next_billing_date' => '2026-09-01',
            'amount_paid' => 0,
            'payment_status' => 'paid',
            'status' => 'active',
        ], $overrides));
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
            $table->timestamps();
        });

        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status');
            $table->string('status');
            $table->json('plan_snapshot')->nullable();
            $table->json('stripe_response')->nullable();
            $table->json('usage_metrics')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }
}
