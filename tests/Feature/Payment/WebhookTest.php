<?php

namespace Tests\Feature\Payment;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        config([
            'services.stripe.webhook_secret' => 'whsec_test',
            'services.paymongo.webhook_secret' => 'whsec_test',
            'services.paymongo.mode' => 'test',
        ]);
    }

    /**
     * It registers unauthenticated, CSRF-exempt webhook routes.
     */
    public function test_webhook_routes_are_registered_and_csrf_exempt(): void
    {
        $this->assertTrue(Route::has('webhook.paymongo'));
        $this->assertTrue(Route::has('webhook.stripe'));

        // No CSRF token sent — a 419 would mean the exemption isn't wired.
        $response = $this->postJson('/webhook/paymongo', ['data' => ['attributes' => ['type' => 'unknown']]]);
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * It rejects a Paymongo webhook with a missing/invalid signature.
     */
    public function test_paymongo_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/webhook/paymongo', [
            'data' => ['id' => 'evt_test', 'attributes' => ['type' => 'checkout_session.paid']],
        ], ['Paymongo-Signature' => 't=123,te=bogus']);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    /**
     * It rejects a Stripe webhook with no signature header.
     */
    public function test_stripe_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/webhook/stripe', [
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123', 'object' => 'checkout.session']],
        ]);

        $response->assertStatus(400);
    }

    /**
     * A valid Stripe checkout.session.completed event marks the payment
     * succeeded, the booking paid, and creates one revenue record.
     */
    public function test_stripe_webhook_processes_checkout_session_completed(): void
    {
        $booking = $this->createBooking();
        $payment = $this->createPayment($booking, ['stripe_session_id' => 'cs_test_123']);

        $payload = [
            'id' => 'evt_test_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'amount_total' => 100000,
                    'payment_status' => 'paid',
                ],
            ],
        ];

        $this->postJson('/webhook/stripe', $payload, [
            'Stripe-Signature' => $this->stripeSignature($payload),
        ])->assertOk();

        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, SystemRevenueModel::where('payment_id', $payment->id)->count());
    }

    public function test_stripe_balance_payment_marks_downpayment_booking_paid_without_resetting_progress(): void
    {
        $booking = $this->createBooking([
            'payment_type' => 'downpayment',
            'payment_status' => 'partially_paid',
            'status' => 'in_progress',
        ]);
        $this->createPayment($booking, ['status' => 'succeeded', 'amount' => 300]);
        $balance = $this->createPayment($booking, [
            'amount' => 700,
            'stripe_session_id' => 'cs_test_balance',
        ]);

        $payload = [
            'id' => 'evt_test_balance',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_balance',
                'object' => 'checkout.session',
                'amount_total' => 70000,
                'payment_status' => 'paid',
            ]],
        ];

        $this->postJson('/webhook/stripe', $payload, [
            'Stripe-Signature' => $this->stripeSignature($payload),
        ])->assertOk();

        $this->assertSame('succeeded', $balance->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('in_progress', $booking->fresh()->status);
    }

    /**
     * A valid Paymongo checkout_session.paid event marks the payment
     * succeeded, the booking paid, and creates one revenue record.
     */
    public function test_paymongo_webhook_processes_checkout_session_paid_with_valid_signature(): void
    {
        $booking = $this->createBooking();
        $payment = $this->createPayment($booking, ['paymongo_source_id' => 'cs_test_pm_1']);

        $payload = [
            'data' => [
                'id' => 'cs_test_pm_1',
                'attributes' => [
                    'type' => 'checkout_session.paid',
                    'status' => 'paid',
                    'payment_method_used' => 'card',
                ],
            ],
        ];

        $this->postJson('/webhook/paymongo', $payload, [
            'Paymongo-Signature' => $this->paymongoSignature($payload),
        ])->assertOk();

        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, SystemRevenueModel::where('payment_id', $payment->id)->count());
    }

    /**
     * Replaying the same successful webhook must not double-process.
     */
    public function test_webhook_processing_is_idempotent(): void
    {
        $booking = $this->createBooking();
        $payment = $this->createPayment($booking, ['paymongo_source_id' => 'cs_test_pm_2']);

        $payload = [
            'data' => [
                'id' => 'cs_test_pm_2',
                'attributes' => [
                    'type' => 'checkout_session.paid',
                    'status' => 'paid',
                    'payment_method_used' => 'card',
                ],
            ],
        ];

        $headers = ['Paymongo-Signature' => $this->paymongoSignature($payload)];

        $this->postJson('/webhook/paymongo', $payload, $headers)->assertOk();
        $this->postJson('/webhook/paymongo', $payload, $headers)->assertOk();

        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame(1, SystemRevenueModel::where('payment_id', $payment->id)->count());
    }

    /**
     * BookingModel::updatePaymentStatus() transitions pending -> paid once
     * a succeeded payment covers the full amount.
     */
    public function test_payment_status_transition_to_paid(): void
    {
        $booking = $this->createBooking();

        $booking->updatePaymentStatus();
        $this->assertSame('pending', $booking->fresh()->payment_status);

        $this->createPayment($booking, ['status' => 'succeeded', 'amount' => 1000]);
        $booking->updatePaymentStatus();

        $this->assertSame('paid', $booking->fresh()->payment_status);
    }

    private function stripeSignature(array $payload): string
    {
        $raw = json_encode($payload);
        $timestamp = time();

        return "t={$timestamp},v1=" . hash_hmac('sha256', $timestamp . '.' . $raw, 'whsec_test');
    }

    private function paymongoSignature(array $payload): string
    {
        $raw = json_encode($payload);
        $timestamp = time();

        return "t={$timestamp},te=" . hash_hmac('sha256', $timestamp . '.' . $raw, 'whsec_test');
    }

    private function createBooking(array $overrides = []): BookingModel
    {
        $client = UserModel::create([
            'role' => 'client',
            'user_type' => 'client',
            'first_name' => 'Webhook',
            'last_name' => 'Client',
            'email' => 'webhook-client@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);

        return BookingModel::create(array_merge([
            'booking_reference' => 'BK-' . str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => 1,
            'category_id' => null,
            'event_name' => 'Test Event',
            'event_date' => '2026-09-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 1000,
            'payment_type' => 'full_payment',
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ], $overrides));
    }

    private function createPayment(BookingModel $booking, array $overrides = []): PaymentModel
    {
        return PaymentModel::create(array_merge([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-' . str()->upper(str()->random(10)),
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'pending',
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
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('event_name')->nullable();
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location_type')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('payment_reference')->unique();
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('paymongo_payment_id')->nullable();
            $table->string('paymongo_source_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_system_revenue', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('payment_id');
            $table->string('revenue_type');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('platform_fee_percentage', 5, 2)->default(10.00);
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
    }
}
