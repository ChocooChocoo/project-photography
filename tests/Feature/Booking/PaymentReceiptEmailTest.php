<?php

namespace Tests\Feature\Booking;

use App\Mail\PaymentReceiptMail;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentReceiptEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        \Illuminate\Support\Facades\Route::get('/_receipt-success/{reference}', [\App\Http\Controllers\Client\BookingController::class, 'paymentSuccess']);
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->unsignedBigInteger('client_id');
            $table->string('event_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->decimal('remaining_balance', 10, 2)->nullable();
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('status')->default('active');
            $table->string('uuid')->nullable();
            $table->string('user_type')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('status');
            $table->text('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_receipt_message_contains_the_verified_payment_and_balance(): void
    {
        $booking = BookingModel::create([
            'booking_reference' => 'BK-RECEIPT-1', 'client_id' => 1, 'event_date' => '2026-12-01',
            'total_amount' => 4000, 'down_payment' => 1200, 'remaining_balance' => 2800,
            'payment_type' => 'downpayment', 'status' => BookingModel::STATUS_CONFIRMED,
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ]);
        $payment = PaymentModel::create([
            'booking_id' => $booking->id, 'payment_reference' => 'PAY-RECEIPT-1',
            'amount' => 1200, 'payment_method' => 'card', 'status' => 'succeeded',
        ]);

        $message = (new PaymentReceiptMail($booking, $payment))->render();

        $this->assertStringContainsString('BK-RECEIPT-1', $message);
        $this->assertStringContainsString('PAY-RECEIPT-1', $message);
        $this->assertStringContainsString('&#8369;1,200.00', $message);
        $this->assertStringContainsString('&#8369;2,800.00', $message);
    }

    public function test_payment_success_route_sends_one_receipt_to_the_booking_client(): void
    {
        Mail::fake();
        $client = \App\Models\UserModel::create([
            'first_name' => 'Receipt', 'last_name' => 'Client', 'email' => 'receipt-client@example.test',
            'password' => 'test', 'role' => 'client', 'status' => 'active',
        ]);
        $booking = BookingModel::create([
            'booking_reference' => 'BK-RECEIPT-ROUTE', 'client_id' => $client->id, 'event_date' => '2026-12-02',
            'total_amount' => 100, 'down_payment' => 30, 'remaining_balance' => 70,
            'payment_type' => 'downpayment', 'status' => BookingModel::STATUS_CONFIRMED,
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ]);
        PaymentModel::create([
            'booking_id' => $booking->id, 'payment_reference' => 'PAY-RECEIPT-ROUTE',
            'amount' => 30, 'payment_method' => 'card', 'status' => 'succeeded', 'paid_at' => now(),
        ]);

        $this->get("/_receipt-success/{$booking->booking_reference}")->assertOk();

        Mail::assertSent(PaymentReceiptMail::class, 1);
        Mail::assertSent(PaymentReceiptMail::class, fn (PaymentReceiptMail $mail) => $mail->payment->payment_reference === 'PAY-RECEIPT-ROUTE');
    }

    public function test_receipt_is_not_resent_when_the_success_page_is_reloaded(): void
    {
        Mail::fake();
        $client = \App\Models\UserModel::create([
            'first_name' => 'Receipt', 'last_name' => 'Reload', 'email' => 'receipt-reload@example.test',
            'password' => 'test', 'role' => 'client', 'status' => 'active',
        ]);
        $booking = BookingModel::create([
            'booking_reference' => 'BK-RECEIPT-RELOAD', 'client_id' => $client->id, 'event_date' => '2026-12-03',
            'total_amount' => 100, 'down_payment' => 30, 'remaining_balance' => 70,
            'payment_type' => 'downpayment', 'status' => BookingModel::STATUS_CONFIRMED,
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ]);
        $payment = PaymentModel::create([
            'booking_id' => $booking->id, 'payment_reference' => 'PAY-RECEIPT-RELOAD',
            'amount' => 30, 'payment_method' => 'card', 'status' => 'succeeded', 'paid_at' => now(),
        ]);

        $this->get("/_receipt-success/{$booking->booking_reference}")->assertOk();
        $this->get("/_receipt-success/{$booking->booking_reference}")->assertOk();

        Mail::assertSent(PaymentReceiptMail::class, 1);
        $this->assertNotNull($payment->fresh()->payment_details['receipt_emailed_at'] ?? null);
    }

    public function test_success_page_does_not_claim_delivery_when_the_attempt_failed(): void
    {
        $booking = new BookingModel(['booking_reference' => 'BK-RECEIPT-2', 'event_date' => '2026-12-02', 'total_amount' => 100, 'down_payment' => 30, 'payment_type' => 'downpayment', 'status' => 'confirmed', 'payment_status' => 'partially_paid']);
        $payment = new PaymentModel(['amount' => 30, 'payment_method' => 'card']);

        $html = view('client.payment-success', compact('booking', 'payment') + ['receiptEmailSent' => false])->render();

        $this->assertStringNotContainsString('A payment receipt email was sent', $html);
        $this->assertStringContainsString('We could not confirm an email receipt', $html);
    }

    public function test_page_claims_delivery_only_from_recorded_attempt_result(): void
    {
        $view = file_get_contents(resource_path('views/client/payment-success.blade.php'));

        $this->assertStringNotContainsString('A confirmation email has been sent', $view);
        $this->assertStringContainsString('$receiptEmailSent', $view);
    }
}
