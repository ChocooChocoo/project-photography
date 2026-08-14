<?php

namespace Tests\Feature;

use App\Http\Controllers\StudioOwner\SubscriptionController;
use App\Http\Requests\StudioOwner\SubscribeRequest;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use App\Services\StripeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SubscriptionBillingLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-12 10:00:00');
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'services.stripe.secret_key' => 'sk_test_subscription',
            'services.stripe.subscription_webhook_secret' => 'whsec_subscription_test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public function test_paid_checkout_uses_recurring_subscription_checkout(): void
    {
        [$owner, $studio] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan();

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')
            ->once()
            ->with('1999.00', Mockery::type('string'), 'Premium', 'monthly', 'PHP')
            ->andReturn(['id' => 'cs_sub_123', 'url' => 'https://checkout.test/sub']);

        $response = (new SubscriptionController($stripe))->subscribe(
            SubscribeRequest::create('/owner/subscription/subscribe', 'POST', ['plan_id' => $plan->id])
        );

        $this->assertSame(200, $response->status());
        $this->assertSame('cs_sub_123', StudioPlanModel::sole()->stripe_session_id);
    }

    public function test_trial_checkout_failure_rolls_back_the_local_trial(): void
    {
        [$owner] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan(['trial_days' => 14]);
        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')->once()->andReturn(null);

        $response = (new SubscriptionController($stripe))->subscribe(
            SubscribeRequest::create('/owner/subscription/subscribe', 'POST', ['plan_id' => $plan->id])
        );

        $this->assertSame(500, $response->status());
        $this->assertSame(0, StudioPlanModel::count());
    }

    public function test_checkout_webhook_is_authoritative_and_idempotent(): void
    {
        $plan = $this->plan();
        $subscription = StudioPlanModel::create([
            'studio_id' => 1,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-CHECKOUT',
            'stripe_session_id' => 'cs_sub_123',
            'start_date' => '2026-08-12',
            'end_date' => '2026-09-12',
            'next_billing_date' => '2026-09-12',
            'amount_paid' => 1999,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $payload = $this->event('checkout.session.completed', [
            'id' => 'cs_sub_123',
            'metadata' => ['subscription_reference' => 'SUB-CHECKOUT'],
            'subscription' => 'sub_123',
            'customer' => 'cus_123',
            'payment_status' => 'paid',
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();
        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();

        $fresh = $subscription->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertSame('sub_123', $fresh->stripe_subscription_id);
        $this->assertSame('cus_123', $fresh->stripe_customer_id);
        $this->assertSame(0, SystemRevenueModel::where('subscription_id', $subscription->id)->count());
    }

    public function test_card_free_trial_checkout_is_authoritative_with_no_payment_required(): void
    {
        $plan = $this->plan(['trial_days' => 14]);
        $subscription = StudioPlanModel::create([
            'studio_id' => 1,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-TRIAL',
            'stripe_session_id' => 'cs_trial',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-26',
            'next_billing_date' => '2026-08-26',
            'trial_ends_at' => '2026-08-26 10:00:00',
            'amount_paid' => 0,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
        $payload = $this->event('checkout.session.completed', [
            'id' => 'cs_trial',
            'metadata' => ['subscription_reference' => 'SUB-TRIAL'],
            'subscription' => 'sub_trial',
            'customer' => 'cus_trial',
            'payment_status' => 'no_payment_required',
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('paid', $subscription->fresh()->payment_status);
        $this->assertSame('sub_trial', $subscription->fresh()->stripe_subscription_id);
    }

    public function test_invoice_before_checkout_is_reconciled_by_subscription_id(): void
    {
        $this->ownerStudio();
        $plan = $this->plan();
        $subscription = $this->subscription($plan, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'stripe_subscription_id' => 'sub_out_of_order',
            'stripe_customer_id' => 'cus_out_of_order',
        ]);
        $invoice = $this->event('invoice.paid', [
            'id' => 'in_out_of_order',
            'subscription' => 'sub_out_of_order',
            'customer' => 'cus_out_of_order',
            'amount_paid' => 199900,
            'period_start' => Carbon::parse('2026-08-12')->timestamp,
            'period_end' => Carbon::parse('2026-09-12')->timestamp,
        ]);
        $this->postJson('/webhook/stripe/subscriptions', $invoice, [
            'Stripe-Signature' => $this->signature($invoice),
        ])->assertOk();

        $checkout = $this->event('checkout.session.completed', [
            'id' => 'cs_out_of_order',
            'metadata' => ['subscription_reference' => $subscription->subscription_reference],
            'subscription' => 'sub_out_of_order',
            'customer' => 'cus_out_of_order',
            'payment_status' => 'paid',
        ]);
        $this->postJson('/webhook/stripe/subscriptions', $checkout, [
            'Stripe-Signature' => $this->signature($checkout),
        ])->assertOk();

        $this->assertSame(1, SystemRevenueModel::where('stripe_invoice_id', 'in_out_of_order')->count());
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_invoice_before_checkout_is_deferred_when_only_session_id_is_local(): void
    {
        $this->ownerStudio();
        $plan = $this->plan();
        $subscription = $this->subscription($plan, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'stripe_session_id' => 'cs_deferred',
        ]);
        $invoice = $this->event('invoice.paid', [
            'id' => 'in_deferred',
            'subscription' => 'sub_deferred',
            'customer' => 'cus_deferred',
            'amount_paid' => 199900,
            'period_start' => Carbon::parse('2026-08-12')->timestamp,
            'period_end' => Carbon::parse('2026-09-12')->timestamp,
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $invoice, [
            'Stripe-Signature' => $this->signature($invoice),
        ])->assertOk();
        $this->assertSame(1, \DB::table('tbl_stripe_subscription_webhook_events')->count());

        $checkout = $this->event('checkout.session.completed', [
            'id' => 'cs_deferred',
            'metadata' => ['subscription_reference' => $subscription->subscription_reference],
            'subscription' => 'sub_deferred',
            'customer' => 'cus_deferred',
            'payment_status' => 'paid',
        ]);
        $this->postJson('/webhook/stripe/subscriptions', $checkout, [
            'Stripe-Signature' => $this->signature($checkout),
        ])->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(1, SystemRevenueModel::where('stripe_invoice_id', 'in_deferred')->count());
        $this->assertSame(0, \DB::table('tbl_stripe_subscription_webhook_events')->count());
    }

    public function test_failed_invoice_before_checkout_is_deferred_and_enters_grace_after_checkout(): void
    {
        $this->ownerStudio();
        $plan = $this->plan();
        $subscription = $this->subscription($plan, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'stripe_session_id' => 'cs_failed_deferred',
        ]);
        $failed = $this->event('invoice.payment_failed', [
            'id' => 'in_failed_deferred',
            'subscription' => 'sub_failed_deferred',
            'customer' => 'cus_failed_deferred',
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $failed, [
            'Stripe-Signature' => $this->signature($failed),
        ])->assertOk();

        $checkout = $this->event('checkout.session.completed', [
            'id' => 'cs_failed_deferred',
            'metadata' => ['subscription_reference' => $subscription->subscription_reference],
            'subscription' => 'sub_failed_deferred',
            'customer' => 'cus_failed_deferred',
            'payment_status' => 'paid',
        ]);
        $this->postJson('/webhook/stripe/subscriptions', $checkout, [
            'Stripe-Signature' => $this->signature($checkout),
        ])->assertOk();

        $fresh = $subscription->fresh();
        $this->assertSame('grace', $fresh->status);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertTrue($fresh->hasAccess());
        $this->assertSame(0, \DB::table('tbl_stripe_subscription_webhook_events')->count());
    }

    public function test_paid_invoice_creates_one_period_and_revenue_for_unique_invoice(): void
    {
        $this->ownerStudio();
        $plan = $this->plan();
        $current = $this->subscription($plan, ['stripe_subscription_id' => 'sub_123']);
        $payload = $this->event('invoice.paid', [
            'id' => 'in_123',
            'subscription' => 'sub_123',
            'customer' => 'cus_123',
            'amount_paid' => 199900,
            'currency' => 'php',
            'period_start' => Carbon::parse('2026-08-12')->timestamp,
            'period_end' => Carbon::parse('2026-09-12')->timestamp,
            'status' => 'paid',
        ]);

        $headers = ['Stripe-Signature' => $this->signature($payload)];
        $this->postJson('/webhook/stripe/subscriptions', $payload, $headers)->assertOk();
        $this->postJson('/webhook/stripe/subscriptions', $payload, $headers)->assertOk();

        $this->assertSame(2, StudioPlanModel::count());
        $period = StudioPlanModel::where('stripe_invoice_id', 'in_123')->firstOrFail();
        $this->assertSame('active', $period->status);
        $this->assertSame('paid', $period->payment_status);
        $this->assertSame(1, SystemRevenueModel::where('stripe_invoice_id', 'in_123')->count());
        $this->assertSame('2026-09-01', $current->fresh()->end_date->toDateString());
    }

    public function test_first_failed_invoice_enters_grace_without_extending_deadline_on_retry(): void
    {
        $subscription = $this->subscription($this->plan(), [
            'stripe_subscription_id' => 'sub_123',
            'end_date' => '2026-08-12',
            'next_billing_date' => '2026-08-12',
        ]);
        $payload = $this->event('invoice.payment_failed', [
            'id' => 'in_failed',
            'subscription' => 'sub_123',
            'customer' => 'cus_123',
        ]);
        $headers = ['Stripe-Signature' => $this->signature($payload)];

        $this->postJson('/webhook/stripe/subscriptions', $payload, $headers)->assertOk();
        $deadline = $subscription->fresh()->grace_ends_at;
        Carbon::setTestNow('2026-08-13 10:00:00');
        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();

        $fresh = $subscription->fresh();
        $this->assertSame('grace', $fresh->status);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertTrue($fresh->hasAccess());
        $this->assertTrue($fresh->grace_ends_at->equalTo($deadline));
        $this->assertSame('in_failed', $fresh->stripe_first_failure_invoice_id);
    }

    public function test_stale_failed_invoice_does_not_regress_a_recovered_paid_period(): void
    {
        $this->ownerStudio();
        $plan = $this->plan();
        $subscription = $this->subscription($plan, [
            'stripe_subscription_id' => 'sub_ordered',
        ]);
        $paid = $this->event('invoice.paid', [
            'id' => 'in_ordered',
            'subscription' => 'sub_ordered',
            'customer' => 'cus_ordered',
            'amount_paid' => 199900,
            'period_start' => Carbon::parse('2026-08-12')->timestamp,
            'period_end' => Carbon::parse('2026-09-12')->timestamp,
        ]);
        $failed = $this->event('invoice.payment_failed', [
            'id' => 'in_ordered',
            'subscription' => 'sub_ordered',
            'customer' => 'cus_ordered',
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $paid, [
            'Stripe-Signature' => $this->signature($paid),
        ])->assertOk();
        $this->postJson('/webhook/stripe/subscriptions', $failed, [
            'Stripe-Signature' => $this->signature($failed),
        ])->assertOk();

        $fresh = StudioPlanModel::where('stripe_invoice_id', 'in_ordered')->firstOrFail();
        $this->assertSame('active', $fresh->status);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertNull($fresh->first_failure_at);
    }

    public function test_owner_can_cancel_at_period_end_and_resume_before_termination(): void
    {
        [$owner] = $this->ownerStudio();
        Auth::setUser($owner);
        $subscription = $this->subscription($this->plan(), [
            'stripe_subscription_id' => 'sub_123',
            'stripe_customer_id' => 'cus_123',
            'end_date' => '2026-09-12',
        ]);

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('cancelSubscriptionAtPeriodEnd')->once()->with('sub_123')->andReturn(true);
        $stripe->shouldReceive('resumeSubscription')->once()->with('sub_123')->andReturn(true);
        $controller = new SubscriptionController($stripe);

        $response = $controller->cancel(request(), (string) $subscription->id);
        $this->assertSame(200, $response->status());
        $this->assertNotNull($subscription->fresh()->scheduled_cancellation_at);
        $this->assertSame('2026-09-12 23:59:59', $subscription->fresh()->scheduled_cancellation_at->format('Y-m-d H:i:s'));
        $this->assertSame('active', $subscription->fresh()->status);

        $response = $controller->resume(request(), (string) $subscription->id);
        $this->assertSame(200, $response->status());
        $this->assertNull($subscription->fresh()->scheduled_cancellation_at);
    }

    public function test_subscription_deleted_ends_access_and_resume_reactivates_previous_plan(): void
    {
        [$owner] = $this->ownerStudio();
        Auth::setUser($owner);
        $plan = $this->plan();
        $subscription = $this->subscription($plan, [
            'stripe_subscription_id' => 'sub_ended',
            'status' => 'expired',
            'end_date' => '2026-08-11',
            'next_billing_date' => '2026-08-11',
        ]);
        $payload = $this->event('customer.subscription.deleted', [
            'id' => 'sub_ended',
            'customer' => 'cus_123',
            'status' => 'canceled',
        ]);
        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();
        $this->assertSame('expired', $subscription->fresh()->status);

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createSubscriptionCheckoutSession')
            ->once()
            ->with('1999.00', Mockery::type('string'), 'Premium', 'monthly', 'PHP')
            ->andReturn(['id' => 'cs_reactivate', 'url' => 'https://checkout.test/reactivate']);
        $response = (new SubscriptionController($stripe))->resume(request(), (string) $subscription->id);

        $this->assertSame(200, $response->status());
        $new = StudioPlanModel::where('stripe_session_id', 'cs_reactivate')->firstOrFail();
        $this->assertSame($plan->id, $new->plan_id);
        $this->assertSame('pending', $new->status);
    }

    public function test_subscription_deleted_only_expires_the_current_period_row(): void
    {
        $plan = $this->plan();
        $historical = $this->subscription($plan, [
            'stripe_subscription_id' => 'sub_history',
            'status' => 'expired',
            'end_date' => '2026-08-01',
        ]);
        $current = $this->subscription($plan, [
            'stripe_subscription_id' => 'sub_history',
            'end_date' => '2026-09-01',
        ]);
        $payload = $this->event('customer.subscription.deleted', ['id' => 'sub_history', 'status' => 'canceled']);

        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();

        $this->assertSame('expired', $current->fresh()->status);
        $this->assertSame('expired', $historical->fresh()->status);
        $this->assertNull($historical->fresh()->cancelled_at);
    }

    public function test_subscription_deleted_expires_all_active_period_rows_for_the_subscription(): void
    {
        $plan = $this->plan();
        $older = $this->subscription($plan, [
            'stripe_subscription_id' => 'sub_multi_period',
            'status' => 'active',
            'end_date' => '2026-08-20',
        ]);
        $newer = $this->subscription($plan, [
            'stripe_subscription_id' => 'sub_multi_period',
            'status' => 'active',
            'end_date' => '2026-09-20',
        ]);
        $payload = $this->event('customer.subscription.deleted', ['id' => 'sub_multi_period', 'status' => 'canceled']);

        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();

        $this->assertSame('expired', $older->fresh()->status);
        $this->assertSame('expired', $newer->fresh()->status);
    }

    public function test_subscription_updates_and_deletes_before_checkout_are_reconciled(): void
    {
        $plan = $this->plan();
        $subscription = $this->subscription($plan, [
            'stripe_session_id' => 'cs_late_events',
            'stripe_subscription_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $updated = $this->event('customer.subscription.updated', [
            'id' => 'sub_late_events',
            'customer' => 'cus_late_events',
            'status' => 'active',
            'current_period_end' => Carbon::parse('2026-09-12 12:00:00')->timestamp,
            'cancel_at_period_end' => true,
        ]);
        $deleted = $this->event('customer.subscription.deleted', [
            'id' => 'sub_late_events',
            'customer' => 'cus_late_events',
            'status' => 'canceled',
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $updated, [
            'Stripe-Signature' => $this->signature($updated),
        ])->assertOk();
        $this->postJson('/webhook/stripe/subscriptions', $deleted, [
            'Stripe-Signature' => $this->signature($deleted),
        ])->assertOk();
        $this->assertSame(2, \DB::table('tbl_stripe_subscription_webhook_events')->count());

        $checkout = $this->event('checkout.session.completed', [
            'id' => 'cs_late_events',
            'metadata' => ['subscription_reference' => $subscription->subscription_reference],
            'subscription' => 'sub_late_events',
            'customer' => 'cus_late_events',
            'payment_status' => 'paid',
        ]);
        $this->postJson('/webhook/stripe/subscriptions', $checkout, [
            'Stripe-Signature' => $this->signature($checkout),
        ])->assertOk();

        $this->assertSame('expired', $subscription->fresh()->status);
        $this->assertSame(0, \DB::table('tbl_stripe_subscription_webhook_events')->count());
    }

    public function test_older_failed_invoice_does_not_regress_a_newer_paid_period(): void
    {
        $this->ownerStudio();
        $plan = $this->plan();
        $subscription = $this->subscription($plan, ['stripe_subscription_id' => 'sub_period_order']);
        $newerPaid = $this->event('invoice.paid', [
            'id' => 'in_newer',
            'subscription' => 'sub_period_order',
            'customer' => 'cus_period_order',
            'amount_paid' => 199900,
            'period_start' => Carbon::parse('2026-09-12')->timestamp,
            'period_end' => Carbon::parse('2026-10-12')->timestamp,
        ]);
        $olderFailed = $this->event('invoice.payment_failed', [
            'id' => 'in_older',
            'subscription' => 'sub_period_order',
            'customer' => 'cus_period_order',
            'period_end' => Carbon::parse('2026-09-12')->timestamp,
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $newerPaid, [
            'Stripe-Signature' => $this->signature($newerPaid),
        ])->assertOk();
        $this->postJson('/webhook/stripe/subscriptions', $olderFailed, [
            'Stripe-Signature' => $this->signature($olderFailed),
        ])->assertOk();

        $fresh = StudioPlanModel::where('stripe_invoice_id', 'in_newer')->firstOrFail();
        $this->assertSame('active', $fresh->status);
        $this->assertSame('paid', $fresh->payment_status);
    }

    public function test_active_subscription_update_does_not_clear_first_failure_grace(): void
    {
        $subscription = $this->subscription($this->plan(), [
            'stripe_subscription_id' => 'sub_grace_update',
            'status' => 'grace',
            'first_failure_at' => '2026-08-12 09:00:00',
            'grace_ends_at' => '2026-08-19 09:00:00',
        ]);
        $payload = $this->event('customer.subscription.updated', [
            'id' => 'sub_grace_update',
            'customer' => 'cus_grace_update',
            'status' => 'active',
            'current_period_end' => Carbon::parse('2026-09-12 14:37:22')->timestamp,
            'cancel_at_period_end' => true,
        ]);

        $this->postJson('/webhook/stripe/subscriptions', $payload, [
            'Stripe-Signature' => $this->signature($payload),
        ])->assertOk();

        $fresh = $subscription->fresh();
        $this->assertSame('grace', $fresh->status);
        $this->assertSame('2026-09-12 14:37:22', $fresh->scheduled_cancellation_at->format('Y-m-d H:i:s'));
        $this->assertTrue($fresh->hasAccess());
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

    private function subscription(SubscriptionPlanModel $plan, array $overrides = []): StudioPlanModel
    {
        return StudioPlanModel::create(array_merge([
            'studio_id' => 1, 'plan_id' => $plan->id, 'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => '2026-08-01', 'end_date' => '2026-09-01', 'next_billing_date' => '2026-09-01',
            'amount_paid' => 1999, 'payment_status' => 'paid', 'status' => 'active',
        ], $overrides));
    }

    private function event(string $type, array $object): array
    {
        return ['id' => 'evt_'.str()->random(12), 'object' => 'event', 'type' => $type, 'data' => ['object' => $object]];
    }

    private function signature(array $payload): string
    {
        $timestamp = time();
        $raw = json_encode($payload);

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$raw, 'whsec_subscription_test');
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
            
            $table->softDeletes();$table->timestamps();
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
            
            $table->softDeletes();$table->timestamps();
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
        Schema::create('tbl_system_revenue', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('revenue_type')->default('subscription');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('platform_fee_percentage', 5, 2);
            $table->decimal('platform_fee_amount', 12, 2);
            $table->decimal('provider_amount', 12, 2);
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('client_id');
            $table->string('status');
            $table->json('breakdown')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_stripe_subscription_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }
}
