<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use App\Services\BookingCancellationRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaidPhotographerCancellationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('mobile_number')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->boolean('email_verified')->default(true);
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name')->nullable();
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
            $table->timestamp('deleted_at')->nullable();
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
            $table->foreignId('studio_id');
            $table->foreignId('original_assignment_id');
            $table->foreignId('replacement_assignment_id')->nullable();
            $table->string('status');
            $table->timestamp('deadline');
            $table->timestamp('replacement_proposed_at')->nullable();
            $table->timestamp('replacement_confirmed_at')->nullable();
            $table->timestamp('client_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('outcome_reason')->nullable();
            $table->text('photographer_reason')->nullable();
            $table->timestamps();
        });
    }

    public function test_photographer_cancellation_creates_one_recovery_and_excludes_assignment(): void
    {
        [$booking, $assignment] = $this->paidBooking();
        $recovery = app(BookingCancellationRecoveryService::class)->photographerCancelled($assignment, 'Family emergency.');

        $this->assertSame('awaiting_replacement', $recovery->status);
        $this->assertSame('cancelled', $assignment->fresh()->status);
        $this->assertSame('Family emergency.', $assignment->fresh()->cancellation_reason);
        $this->assertSame(0, $booking->assignedPhotographers()->whereIn('status', ['assigned', 'confirmed', 'on_site', 'in_progress'])->count());
        $this->assertEqualsWithDelta(24 * 60, now('Asia/Manila')->diffInMinutes($recovery->deadline), 2);
    }

    public function test_second_photographer_cancellation_reuses_recovery_and_is_excluded(): void
    {
        [$booking, $assignment] = $this->paidBooking();
        $second = BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $assignment->studio_id,
            'photographer_id' => UserModel::create($this->user('second@example.com', 'studio-photographer'))->id,
            'assigned_by' => $booking->provider_id,
            'status' => 'confirmed',
            'assigned_at' => now(),
        ]);

        $service = app(BookingCancellationRecoveryService::class);
        $firstRecovery = $service->photographerCancelled($assignment, 'Unavailable.');
        $secondRecovery = $service->photographerCancelled($second, 'Another emergency.');

        $this->assertSame($firstRecovery->id, $secondRecovery->id);
        $this->assertSame('cancelled', $second->fresh()->status);
        $this->assertSame($firstRecovery->id, $second->fresh()->recovery_id);
        $this->assertSame(1, \App\Models\BookingCancellationRecoveryModel::where('booking_id', $booking->id)->count());
    }

    public function test_photographer_cancellation_rejects_cross_studio_assignment(): void
    {
        [$booking, $assignment] = $this->paidBooking();
        $assignment->update(['studio_id' => $assignment->studio_id + 100]);

        $this->expectException(\DomainException::class);
        app(BookingCancellationRecoveryService::class)->photographerCancelled($assignment, 'Unavailable.');
    }

    public function test_replacement_requires_confirmation_before_client_acceptance(): void
    {
        [$booking, $assignment] = $this->paidBooking();
        $replacement = BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id, 'studio_id' => $assignment->studio_id, 'photographer_id' => UserModel::create($this->user('replacement@example.com', 'studio-photographer'))->id,
            'assigned_by' => $booking->provider_id, 'status' => 'assigned', 'assigned_at' => now(),
        ]);
        $service = app(BookingCancellationRecoveryService::class);
        $recovery = $service->photographerCancelled($assignment, 'Unavailable.');
        $service->proposeReplacement($recovery, $replacement);

        $this->assertSame('replacement_proposed', $recovery->fresh()->status);
        $blocked = false;
        try {
            $service->clientRespond($recovery->fresh(), true);
        } catch (\DomainException) {
            $blocked = true;
        }
        $this->assertTrue($blocked);
        $service->replacementConfirmed($recovery->fresh());
        $this->assertTrue($service->clientRespond($recovery->fresh(), true));
        $this->assertSame('accepted', $recovery->fresh()->status);
        $this->assertNotSame('cancelled', $booking->fresh()->status);
    }

    public function test_rejection_queues_refund_and_admin_evidence_marks_everything_refunded(): void
    {
        [$booking, $assignment, $payment, $revenue] = $this->paidBooking(true);
        $service = app(BookingCancellationRecoveryService::class);
        $recovery = $service->photographerCancelled($assignment, 'Unavailable.');
        $replacement = BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id, 'studio_id' => $assignment->studio_id, 'photographer_id' => UserModel::create($this->user('replacement-refund@example.com', 'studio-photographer'))->id,
            'assigned_by' => $booking->provider_id, 'status' => 'confirmed', 'assigned_at' => now(),
        ]);
        $service->proposeReplacement($recovery, $replacement);
        $service->replacementConfirmed($recovery->fresh());
        $service->clientRespond($recovery->fresh(), false);

        $this->assertSame('refund_pending', $recovery->fresh()->status);
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->fresh()->payment_status);
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertTrue($service->completeRefund($recovery->fresh(), 'stripe_refund_123', 'Processor fee retained as note.'));
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('refunded', $revenue->fresh()->status);
        $this->assertSame(BookingModel::PAYMENT_REFUNDED, $booking->fresh()->payment_status);
        $this->assertSame('refunded', $recovery->fresh()->status);
    }

    public function test_expired_deadline_queues_refund_once(): void
    {
        [$booking, $assignment] = $this->paidBooking();
        $service = app(BookingCancellationRecoveryService::class);
        $recovery = $service->photographerCancelled($assignment, 'Unavailable.');
        $recovery->update(['deadline' => now('Asia/Manila')->subMinute()]);
        $this->assertSame(1, $service->escalateExpired());
        $this->assertSame('refund_pending', $recovery->fresh()->status);
        $this->assertSame(0, $service->escalateExpired());
    }

    public function test_exact_deadline_is_not_accepting_a_client_response(): void
    {
        [$booking, $assignment] = $this->paidBooking();
        $service = app(BookingCancellationRecoveryService::class);
        $recovery = $service->photographerCancelled($assignment, 'Unavailable.');
        $replacement = BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id, 'studio_id' => $assignment->studio_id,
            'photographer_id' => UserModel::create($this->user('boundary-replacement@example.com', 'studio-photographer'))->id,
            'assigned_by' => $booking->provider_id, 'status' => 'confirmed', 'assigned_at' => now(),
        ]);
        $service->proposeReplacement($recovery, $replacement);
        $service->replacementConfirmed($recovery->fresh());
        $recovery->update(['deadline' => now()]);

        $this->expectException(\DomainException::class);
        $service->clientRespond($recovery->fresh(), true);
    }

    public function test_multi_payment_refund_requires_explicit_unique_provider_references(): void
    {
        [$booking, $assignment, $payment, $revenue] = $this->paidBooking(true);
        $secondPayment = PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-SECOND-'.str()->random(8),
            'amount' => 250,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);
        SystemRevenueModel::create([
            'transaction_reference' => 'REV-SECOND-'.str()->random(8), 'booking_id' => $booking->id, 'payment_id' => $secondPayment->id,
            'revenue_type' => 'booking', 'total_amount' => 250, 'platform_fee_percentage' => 10, 'platform_fee_amount' => 25,
            'provider_amount' => 225, 'provider_type' => 'studio', 'provider_id' => $booking->provider_id, 'client_id' => $booking->client_id, 'status' => 'completed',
        ]);
        $service = app(BookingCancellationRecoveryService::class);
        $recovery = $service->photographerCancelled($assignment, 'Unavailable.');
        $replacement = BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id, 'studio_id' => $assignment->studio_id,
            'photographer_id' => UserModel::create($this->user('multi-replacement@example.com', 'studio-photographer'))->id,
            'assigned_by' => $booking->provider_id, 'status' => 'confirmed', 'assigned_at' => now(),
        ]);
        $service->proposeReplacement($recovery, $replacement);
        $service->replacementConfirmed($recovery->fresh());
        $service->clientRespond($recovery->fresh(), false);

        $this->assertFalse($service->completeRefund($recovery->fresh(), 'single-reference'));
        $this->assertTrue($service->completeRefund($recovery->fresh(), [
            $payment->id => 'refund-first',
            $secondPayment->id => 'refund-second',
        ]));
    }

    private function paidBooking(bool $withRevenue = false): array
    {
        $owner = UserModel::create($this->user('owner@example.com', 'owner'));
        $client = UserModel::create($this->user('client@example.com', 'client'));
        $photographer = UserModel::create($this->user('photographer@example.com', 'studio-photographer'));
        $studio = \App\Models\StudioOwner\StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'Studio', 'status' => 'verified']);
        $booking = BookingModel::create([
            'booking_reference' => 'BK-'.str()->random(8), 'client_id' => $client->id, 'booking_type' => 'studio', 'provider_id' => $studio->id,
            'event_name' => 'Event', 'event_date' => now('Asia/Manila')->addDays(2)->toDateString(), 'start_time' => '12:00:00', 'end_time' => '14:00:00',
            'location_type' => 'in-studio', 'total_amount' => 1000, 'down_payment' => 0, 'remaining_balance' => 0, 'payment_type' => 'full_payment',
            'status' => 'confirmed', 'payment_status' => 'paid',
        ]);
        $assignment = BookingAssignedPhotographerModel::create(['booking_id' => $booking->id, 'studio_id' => $studio->id, 'photographer_id' => $photographer->id, 'assigned_by' => $owner->id, 'status' => 'confirmed', 'assigned_at' => now()]);
        $payment = PaymentModel::create(['booking_id' => $booking->id, 'payment_reference' => 'PAY-'.str()->random(8), 'amount' => 1000, 'payment_method' => 'card', 'status' => 'succeeded']);
        $revenue = SystemRevenueModel::create(['transaction_reference' => 'REV-'.str()->random(8), 'booking_id' => $booking->id, 'payment_id' => $payment->id, 'revenue_type' => 'booking', 'total_amount' => 1000, 'platform_fee_percentage' => 10, 'platform_fee_amount' => 100, 'provider_amount' => 900, 'provider_type' => 'studio', 'provider_id' => $studio->id, 'client_id' => $client->id, 'status' => 'completed']);

        return $withRevenue ? [$booking, $assignment, $payment, $revenue] : [$booking, $assignment];
    }

    private function user(string $email, string $role): array
    {
        return ['role' => $role, 'user_type' => $role === 'client' ? 'customer' : 'photographer', 'first_name' => 'Test', 'last_name' => 'User', 'email' => $email, 'mobile_number' => '09170000000', 'password' => 'secret', 'status' => 'active', 'email_verified' => true];
    }
}
