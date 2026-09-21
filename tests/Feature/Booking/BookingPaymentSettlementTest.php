<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\BookingController;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingPaymentSettlementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::post('/test/webhook/paymongo', [BookingController::class, 'handleWebhook']);

        config([
            'services.paymongo.webhook_secret' => 'test-webhook-secret',
            'services.paymongo.mode' => 'test',
        ]);
    }

    public function test_full_payment_webhook_settles_as_paid_and_confirmed(): void
    {
        [$booking, $payment] = $this->createBookingWithPayment('full_payment', 10000);

        $this->postWebhook($booking, 'paid')
            ->assertOk()
            ->assertJson(['success' => true]);

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(BookingModel::PAYMENT_PAID, $booking->payment_status);
        $this->assertSame(BookingModel::STATUS_CONFIRMED, $booking->status);
    }

    public function test_downpayment_webhook_settles_as_partially_paid_and_confirmed(): void
    {
        [$booking, $payment] = $this->createBookingWithPayment('downpayment', 3000, 10000);

        $this->postWebhook($booking, 'paid')
            ->assertOk()
            ->assertJson(['success' => true]);

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(BookingModel::PAYMENT_PARTIALLY_PAID, $booking->payment_status);
        $this->assertSame(BookingModel::STATUS_CONFIRMED, $booking->status);
    }

    public function test_repeated_full_payment_webhook_stays_settled(): void
    {
        [$booking, $payment] = $this->createBookingWithPayment('full_payment', 10000);

        $this->postWebhook($booking, 'paid')->assertOk();
        $this->postWebhook($booking, 'paid')->assertOk();

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(BookingModel::PAYMENT_PAID, $booking->payment_status);
        $this->assertSame(BookingModel::STATUS_CONFIRMED, $booking->status);
        $this->assertSame(1, PaymentModel::where('status', 'succeeded')->count());
    }

    private function postWebhook(BookingModel $booking, string $status)
    {
        $payload = [
            'data' => [
                'id' => 'cs_test_' . $booking->id,
                'attributes' => [
                    'type' => 'checkout_session.completed',
                    'status' => $status,
                    'payment_method_used' => 'card',
                ],
            ],
        ];

        return $this->postJson('/test/webhook/paymongo', $payload, [
            'Paymongo-Signature' => $this->paymongoSignature($payload),
        ]);
    }

    private function paymongoSignature(array $payload): string
    {
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . json_encode($payload), 'test-webhook-secret');

        return "t={$timestamp},te={$signature}";
    }

    private function createBookingWithPayment(string $paymentType, float $paymentAmount, ?float $totalAmount = null): array
    {
        $totalAmount = $totalAmount ?? $paymentAmount;

        $booking = BookingModel::create([
            'booking_reference' => 'BK-SETTLE-' . strtoupper(str()->random(6)),
            'client_id' => 1,
            'booking_type' => 'studio',
            'provider_id' => 1,
            'category_id' => null,
            'event_date' => '2026-09-01',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'location_type' => 'in-studio',
            'total_amount' => $totalAmount,
            'down_payment' => $paymentAmount,
            'remaining_balance' => $totalAmount - $paymentAmount,
            'payment_type' => $paymentType,
            'status' => BookingModel::STATUS_PENDING,
            'payment_status' => BookingModel::PAYMENT_PENDING,
        ]);

        $payment = PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-SETTLE-' . strtoupper(str()->random(6)),
            'paymongo_source_id' => 'cs_test_' . $booking->id,
            'amount' => $paymentAmount,
            'payment_method' => 'pending',
            'status' => 'pending',
        ]);

        return [$booking, $payment];
    }

    private function createSchema(): void
    {
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
            $table->string('venue_name')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->json('multiple_locations')->nullable();
            $table->text('special_requests')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->decimal('remaining_balance', 10, 2)->nullable();
            $table->string('deposit_policy')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('status');
            $table->string('payment_status');
            $table->timestamp('expires_at')->nullable();
            $table->string('booking_frequency')->default('one_time');
            $table->json('recurrence_pattern')->nullable();
            $table->unsignedBigInteger('parent_booking_id')->nullable();
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
            $table->string('refund_reference')->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->text('refund_notes')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }
}
