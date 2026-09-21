<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Admin\BookingRefundController;
use App\Http\Controllers\Client\MyBookingsController;
use App\Http\Controllers\StudioOwner\BookingController;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use App\Services\BookingCancellationRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientCancellationRefundFlowTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'client-cancel-owner@example.com');

        Route::post('/_test/client/booking/{id}/cancel', [MyBookingsController::class, 'cancelBooking']);
        Route::post('/_test/owner/recoveries/{recoveryId}/escalate', [BookingController::class, 'escalatePhotographerCancellation']);
        Route::get('/_test/admin/refunds', [BookingRefundController::class, 'index']);
        Route::post('/_test/admin/refunds/{recoveryId}/complete', [BookingRefundController::class, 'complete']);
    }

    public function test_freelancer_down_payment_client_cancel_queues_no_refund_target(): void
    {
        $client = $this->createUser('client', 'client-cancel-freelancer-client@example.com');
        $freelancer = $this->createUser('freelancer', 'client-cancel-freelancer@example.com');
        $booking = $this->createBooking($freelancer->id, 'freelancer', $client, [
            'total_amount' => 1000,
            'down_payment' => 300,
            'remaining_balance' => 700,
            'payment_type' => 'downpayment',
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ]);
        $payment = $this->createPayment($booking, 300, 'PAY-CLIENT-FREELANCER-DOWN');

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'The freelancer service is no longer needed for this project.'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        // The down payment is kept, so no refund is queued and the payment stands.
        $this->assertSame(BookingModel::PAYMENT_PARTIALLY_PAID, $booking->payment_status);
        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame(0, BookingCancellationRecoveryModel::where('booking_id', $booking->id)->count());

        $this->actingAs($this->owner)
            ->getJson('/_test/admin/refunds')
            ->assertOk()
            ->assertJsonCount(0, 'recoveries');
    }

    public function test_studio_down_payment_client_cancel_queues_no_refund_target(): void
    {
        $client = $this->createUser('client', 'client-cancel-studio-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio->id, 'studio', $client, [
            'total_amount' => 1000,
            'down_payment' => 300,
            'remaining_balance' => 700,
            'payment_type' => 'downpayment',
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ]);
        $this->createPayment($booking, 300, 'PAY-CLIENT-STUDIO-DOWN');

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'The venue is no longer available for the chosen date.'])
            ->assertOk();

        $this->assertSame(BookingModel::PAYMENT_PARTIALLY_PAID, $booking->fresh()->payment_status);
        $this->assertSame(0, BookingCancellationRecoveryModel::where('booking_id', $booking->id)->count());
    }

    public function test_studio_full_payment_client_cancel_defers_refund_to_the_owner(): void
    {
        $client = $this->createUser('client', 'client-cancel-full-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio->id, 'studio', $client, [
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'payment_status' => BookingModel::PAYMENT_PAID,
        ]);
        $payment = $this->createPayment($booking, 1000, 'PAY-CLIENT-STUDIO-FULL');

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'Our family has decided to relocate and cancel the event.'])
            ->assertOk();

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->payment_status);

        $recovery = BookingCancellationRecoveryModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('refund_pending', $recovery->status);
        // No automatic split: the target stays unset until the owner chooses.
        $this->assertNull($recovery->refund_amount);
        $this->assertNull($recovery->refund_percentage);

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$recovery->id}/escalate", ['refund_amount' => 250])
            ->assertOk()
            ->assertJsonPath('success', true);

        $recovery->refresh();
        $this->assertSame('250.00', $recovery->refund_amount);
        $this->assertSame('25.00', $recovery->refund_percentage);

        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$recovery->id}/complete", ['provider_refund_reference' => 'stripe_refund_client_full'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('partially_refunded', $payment->fresh()->status);
        $this->assertSame('250.00', $payment->fresh()->refunded_amount);
        // Only part of the paid total went back, so the booking is not fully refunded.
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->fresh()->payment_status);
    }

    public function test_freelancer_full_payment_client_cancel_also_defers_to_the_owner(): void
    {
        $client = $this->createUser('client', 'client-cancel-freelancer-full-client@example.com');
        $freelancer = $this->createUser('freelancer', 'client-cancel-freelancer-full@example.com');
        $booking = $this->createBooking($freelancer->id, 'freelancer', $client, [
            'total_amount' => 800,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'payment_status' => BookingModel::PAYMENT_PAID,
        ]);
        $this->createPayment($booking, 800, 'PAY-CLIENT-FREELANCER-FULL');

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'The freelancer is unavailable and we canceled the shoot.'])
            ->assertOk();

        $recovery = BookingCancellationRecoveryModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('refund_pending', $recovery->status);
        $this->assertNull($recovery->refund_amount);
        $this->assertNull($recovery->studio_id);
    }

    public function test_business_cancellation_still_targets_a_full_refund(): void
    {
        $client = $this->createUser('client', 'client-cancel-business-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio->id, 'studio', $client, [
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'payment_status' => BookingModel::PAYMENT_PAID,
        ]);
        $this->createPayment($booking, 1000, 'PAY-BUSINESS-CANCEL');

        $recovery = BookingCancellationRecoveryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'status' => BookingCancellationRecoveryService::STATUS_AWAITING_CLIENT,
            'deadline' => now()->addDay(),
        ]);

        app(BookingCancellationRecoveryService::class)->queueRefund($recovery, 'The photographer could not make it.');

        $recovery->refresh();
        $this->assertSame('refund_pending', $recovery->status);
        $this->assertSame('1000.00', $recovery->refund_amount);
        $this->assertSame('100.00', $recovery->refund_percentage);
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->fresh()->payment_status);
    }

    public function test_business_cancellation_refund_cannot_be_reduced_via_the_owner_endpoint(): void
    {
        $client = $this->createUser('client', 'client-cancel-business-reduce-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio->id, 'studio', $client, [
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_CANCELLED,
            'payment_status' => BookingModel::PAYMENT_REFUND_PENDING,
            'cancelled_by' => 'studio',
            'cancellation_reason' => 'The studio cancelled the session.',
        ]);
        $payment = $this->createPayment($booking, 1000, 'PAY-BUSINESS-REDUCE');

        $recovery = BookingCancellationRecoveryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'status' => BookingCancellationRecoveryService::STATUS_REFUND_PENDING,
            'outcome_reason' => 'The studio cancelled the session.',
        ]);

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$recovery->id}/escalate", ['refund_amount' => 100])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        // The owner's lower amount is rejected and nothing is stored.
        $this->assertNull($recovery->fresh()->refund_amount);

        // The admin queue still executes the full paid total by default.
        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$recovery->id}/complete", ['provider_refund_reference' => 'stripe_refund_business_full'])
            ->assertOk();

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('1000.00', $payment->fresh()->refunded_amount);
        $this->assertSame(BookingModel::PAYMENT_REFUNDED, $booking->fresh()->payment_status);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'ClientCancel',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000009',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Client Cancel Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(int $providerId, string $bookingType, UserModel $client, array $overrides = []): BookingModel
    {
        return BookingModel::create(array_merge([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => $bookingType,
            'provider_id' => $providerId,
            'event_name' => 'Client Cancel Event',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_CONFIRMED,
            'payment_status' => BookingModel::PAYMENT_PAID,
        ], $overrides));
    }

    private function createPayment(BookingModel $booking, float $amount, string $reference): PaymentModel
    {
        return PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => $reference,
            'amount' => $amount,
            'payment_method' => 'card',
            'status' => 'succeeded',
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
            $table->string('mobile_number')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->boolean('email_verified')->default(true);
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
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name');
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location_type');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->text('cancellation_reason')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('studio_id');
            $table->foreignId('photographer_id');
            $table->foreignId('assigned_by');
            $table->string('status')->default('assigned');
            $table->text('assignment_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('response_deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('recovery_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->string('refund_reference')->nullable()->unique();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->text('refund_notes')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_system_revenue', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('payment_id');
            $table->string('revenue_type')->default('booking');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('platform_fee_percentage', 5, 2)->default(10);
            $table->decimal('platform_fee_amount', 12, 2);
            $table->decimal('provider_amount', 12, 2);
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('client_id');
            $table->string('status')->default('completed');
            $table->json('breakdown')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique();
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('original_assignment_id')->nullable();
            $table->unsignedBigInteger('replacement_assignment_id')->nullable();
            $table->string('status');
            $table->decimal('refund_percentage', 5, 2)->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('replacement_proposed_at')->nullable();
            $table->timestamp('replacement_confirmed_at')->nullable();
            $table->timestamp('client_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('outcome_reason')->nullable();
            $table->text('photographer_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('icon')->default('bell');
            $table->string('color')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
