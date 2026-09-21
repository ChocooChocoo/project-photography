<?php

namespace Tests\Feature\Subscription;

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

class SubscriptionTrialHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function test_first_ever_subscription_still_starts_the_free_trial(): void
    {
        [$owner] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan(['trial_days' => 14]);

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')
            ->once()
            ->with('1999.00', Mockery::type('string'), 'Premium', 'monthly', 'PHP', 14)
            ->andReturn(['id' => 'cs_first_trial', 'url' => 'https://checkout.test/first-trial']);

        $response = (new SubscriptionController($stripe))->subscribe(
            SubscribeRequest::create('/owner/subscription/subscribe', 'POST', ['plan_id' => $plan->id])
        );

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->getData(true)['trial']);

        $subscription = StudioPlanModel::sole();
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertSame('active', $subscription->status);
    }

    public function test_cancel_then_resubscribe_goes_to_checkout_without_a_trial(): void
    {
        [$owner, $studio] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan(['trial_days' => 14]);

        $this->subscription($studio, $plan, [
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'trial_ends_at' => '2026-08-20 10:00:00',
            'end_date' => '2026-08-20',
            'next_billing_date' => '2026-08-20',
            'cancelled_at' => '2026-08-11 09:00:00',
        ]);

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')
            ->once()
            ->with('1999.00', Mockery::type('string'), 'Premium', 'monthly', 'PHP')
            ->andReturn(['id' => 'cs_resub_paid', 'url' => 'https://checkout.test/resub-paid']);

        $response = (new SubscriptionController($stripe))->subscribe(
            SubscribeRequest::create('/owner/subscription/subscribe', 'POST', ['plan_id' => $plan->id])
        );

        $this->assertSame(200, $response->status());
        $payload = $response->getData(true);
        $this->assertFalse($payload['trial']);
        $this->assertStringContainsString('already been used', $payload['message']);

        $pending = StudioPlanModel::where('stripe_session_id', 'cs_resub_paid')->firstOrFail();
        $this->assertSame('pending', $pending->status);
        $this->assertSame('pending', $pending->payment_status);
        $this->assertNull($pending->trial_ends_at);
        $this->assertSame('1999.00', $pending->amount_paid);
    }

    public function test_expired_trial_resubscribe_goes_to_checkout_without_a_trial(): void
    {
        [$owner, $studio] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan(['trial_days' => 14]);

        $this->subscription($studio, $plan, [
            'status' => 'expired',
            'payment_status' => 'paid',
            'trial_ends_at' => '2026-08-05 10:00:00',
            'end_date' => '2026-08-05',
            'next_billing_date' => '2026-08-05',
        ]);

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')
            ->once()
            ->with('1999.00', Mockery::type('string'), 'Premium', 'monthly', 'PHP')
            ->andReturn(['id' => 'cs_expired_paid', 'url' => 'https://checkout.test/expired-paid']);

        $response = (new SubscriptionController($stripe))->subscribe(
            SubscribeRequest::create('/owner/subscription/subscribe', 'POST', ['plan_id' => $plan->id])
        );

        $this->assertSame(200, $response->status());
        $this->assertFalse($response->getData(true)['trial']);

        $pending = StudioPlanModel::where('stripe_session_id', 'cs_expired_paid')->firstOrFail();
        $this->assertNull($pending->trial_ends_at);
    }

    public function test_legacy_row_without_trial_ends_at_but_with_snapshot_trial_days_blocks_a_new_trial(): void
    {
        [$owner, $studio] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan(['trial_days' => 14]);

        $this->subscription($studio, $plan, [
            'status' => 'expired',
            'payment_status' => 'paid',
            'trial_ends_at' => null,
            'end_date' => '2026-08-01',
            'next_billing_date' => '2026-08-01',
            'plan_snapshot' => ['trial_days' => 14],
        ]);

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')
            ->once()
            ->with('1999.00', Mockery::type('string'), 'Premium', 'monthly', 'PHP')
            ->andReturn(['id' => 'cs_legacy_paid', 'url' => 'https://checkout.test/legacy-paid']);

        $response = (new SubscriptionController($stripe))->subscribe(
            SubscribeRequest::create('/owner/subscription/subscribe', 'POST', ['plan_id' => $plan->id])
        );

        $this->assertSame(200, $response->status());
        $this->assertFalse($response->getData(true)['trial']);

        $pending = StudioPlanModel::where('stripe_session_id', 'cs_legacy_paid')->firstOrFail();
        $this->assertNull($pending->trial_ends_at);
    }

    private function ownerStudio(): array
    {
        $owner = UserModel::create([
            'role' => 'owner', 'user_type' => 'photographer', 'first_name' => 'Owner',
            'last_name' => 'Test', 'email' => 'owner-'.str()->random(8).'@example.com',
            'mobile_number' => '09170000001', 'password' => 'secret', 'status' => 'active',
            'email_verified' => true,
        ]);
        $studio = StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'Test Studio', 'status' => 'active']);
        $owner->setRelation('studio', $studio);

        return [$owner, $studio];
    }

    private function plan(array $overrides = []): SubscriptionPlanModel
    {
        return SubscriptionPlanModel::create(array_merge([
            'user_type' => 'studio', 'plan_type' => 'premium', 'billing_cycle' => 'monthly',
            'plan_code' => 'STU_PREMIUM_'.str()->upper(str()->random(5)), 'name' => 'Premium',
            'description' => 'Test plan', 'price' => 1999, 'commission_rate' => 10,
            'trial_days' => 0, 'status' => 'active',
        ], $overrides));
    }

    private function subscription(StudiosModel $studio, SubscriptionPlanModel $plan, array $overrides = []): StudioPlanModel
    {
        return StudioPlanModel::create(array_merge([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => '2026-08-01', 'end_date' => '2026-09-01', 'next_billing_date' => '2026-09-01',
            'amount_paid' => 1999, 'payment_status' => 'paid', 'status' => 'active',
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
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');

            $table->softDeletes();
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

            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_first_failure_invoice_id')->nullable();
            $table->timestamp('scheduled_cancellation_at')->nullable();
            $table->timestamp('first_failure_at')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
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
