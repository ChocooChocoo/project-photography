<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Admin\BookingRefundController;
use App\Http\Controllers\Client\MyBookingsController;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrdinaryCancellationTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'ordinary-owner@example.com');

        Route::post('/_test/client/booking/{id}/cancel', [MyBookingsController::class, 'cancelBooking']);
        Route::get('/_test/admin/refunds', [BookingRefundController::class, 'index']);
        Route::post('/_test/admin/refunds/{recoveryId}/complete', [BookingRefundController::class, 'complete']);
    }

    public function test_client_cancels_unpaid_pending_booking_and_payment_status_is_not_cancelled(): void
    {
        $client = $this->createUser('client', 'ordinary-pending-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_PENDING, BookingModel::PAYMENT_PENDING);
        $pendingPayment = PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-ORD-PENDING',
            'amount' => 500,
            'payment_method' => 'gcash',
            'status' => 'pending',
        ]);

        $reason = 'Schedule conflict with another important event I must attend.';

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => $reason])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        $this->assertSame('client', $booking->cancelled_by);
        $this->assertSame($reason, $booking->cancellation_reason);
        $this->assertSame(BookingModel::PAYMENT_PENDING, $booking->payment_status);
        $this->assertSame('cancelled', $pendingPayment->fresh()->status);
        $this->assertSame(0, BookingCancellationRecoveryModel::where('booking_id', $booking->id)->count());
    }

    public function test_client_cancels_unpaid_confirmed_booking(): void
    {
        $client = $this->createUser('client', 'ordinary-confirmed-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PENDING);

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'The event venue fell through so we must postpone everything.'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        $this->assertSame('client', $booking->cancelled_by);
    }

    public function test_client_cancels_paid_booking_queues_refund_and_admin_completes_with_evidence(): void
    {
        $client = $this->createUser('client', 'ordinary-paid-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'ordinary-paid-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PAID);
        $this->createAssignment($booking, $studio, $photographer, 'confirmed');
        $payment = PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-ORD-PAID',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);
        $revenue = SystemRevenueModel::create([
            'transaction_reference' => 'REV-ORD-PAID',
            'booking_id' => $booking->id,
            'payment_id' => $payment->id,
            'revenue_type' => 'booking',
            'total_amount' => 1000,
            'platform_fee_percentage' => 10,
            'platform_fee_amount' => 100,
            'provider_amount' => 900,
            'provider_type' => 'studio',
            'provider_id' => $studio->id,
            'client_id' => $client->id,
            'status' => 'completed',
        ]);

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'Our family decided to relocate so the event cannot push through.'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->payment_status);
        $recovery = BookingCancellationRecoveryModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('refund_pending', $recovery->status);
        $this->assertSame($booking->id, $recovery->booking_id);
        $this->assertNull($recovery->original_assignment_id);

        $this->actingAs($this->owner)
            ->getJson('/_test/admin/refunds')
            ->assertOk()
            ->assertJsonPath('recoveries.0.id', $recovery->id)
            ->assertJsonPath('recoveries.0.status', 'refund_pending');

        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$recovery->id}/complete", ['provider_refund_reference' => 'stripe_refund_ordinary_1'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('refunded', $revenue->fresh()->status);
        $this->assertSame(BookingModel::PAYMENT_REFUNDED, $booking->fresh()->payment_status);
        $this->assertSame('refunded', $recovery->fresh()->status);
    }

    public function test_cancellation_less_than_24_hours_before_event_is_rejected(): void
    {
        $client = $this->createUser('client', 'ordinary-late-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PENDING, now()->addHours(23)->toDateString());

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'An emergency came up at the last minute and we must cancel.'])
            ->assertJsonPath('success', false);

        $this->assertSame(BookingModel::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    public function test_cancellation_reason_shorter_than_20_characters_is_rejected(): void
    {
        $client = $this->createUser('client', 'ordinary-short-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_PENDING, BookingModel::PAYMENT_PENDING);

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'Too short.'])
            ->assertStatus(422);

        $this->assertSame(BookingModel::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_in_progress_completed_and_cancelled_bookings_cannot_be_cancelled(): void
    {
        $client = $this->createUser('client', 'ordinary-locked-client@example.com');
        $studio = $this->createStudio();
        $inProgress = $this->createBooking($studio, $client, BookingModel::STATUS_IN_PROGRESS, BookingModel::PAYMENT_PAID);
        $completed = $this->createBooking($studio, $client, BookingModel::STATUS_COMPLETED, BookingModel::PAYMENT_PAID);
        $cancelled = $this->createBooking($studio, $client, BookingModel::STATUS_CANCELLED, BookingModel::PAYMENT_PENDING);

        foreach ([$inProgress, $completed, $cancelled] as $booking) {
            $this->actingAs($client)
                ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'This booking should not be cancellable anymore at all.'])
                ->assertJsonPath('success', false);

            $this->assertSame($booking->status, $booking->fresh()->status);
        }
    }

    public function test_client_cancellation_cancels_open_assignments_but_keeps_completed_ones(): void
    {
        $client = $this->createUser('client', 'ordinary-assign-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'ordinary-assign-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PENDING);
        $open = $this->createAssignment($booking, $studio, $photographer, 'confirmed');
        $completed = $this->createAssignment($booking, $studio, $photographer, 'completed');

        $reason = 'The event location changed to a venue outside our service area.';

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => $reason])
            ->assertOk();

        $open->refresh();
        $this->assertSame('cancelled', $open->status);
        $this->assertNotNull($open->cancelled_at);
        $this->assertSame($reason, $open->cancellation_reason);

        $this->assertSame('completed', $completed->fresh()->status);
    }

    public function test_client_cancellation_notifies_owner_and_assigned_photographer(): void
    {
        $client = $this->createUser('client', 'ordinary-notify-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'ordinary-notify-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PENDING);
        $this->createAssignment($booking, $studio, $photographer, 'confirmed');

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'A family obligation now conflicts with the scheduled event date.'])
            ->assertOk();

        $ownerNotification = NotificationModel::where('user_id', $this->owner->id)
            ->where('type', 'booking_cancelled_by_client')
            ->where('data->booking_id', $booking->id)
            ->first();
        $this->assertNotNull($ownerNotification);

        $photographerNotification = NotificationModel::where('user_id', $photographer->id)
            ->where('type', 'booking_cancelled_by_client')
            ->where('data->booking_id', $booking->id)
            ->first();
        $this->assertNotNull($photographerNotification);
    }

    public function test_client_cancels_paid_freelancer_booking_queues_refund_without_studio(): void
    {
        $client = $this->createUser('client', 'ordinary-freelance-client@example.com');
        $freelancer = $this->createUser('freelancer', 'ordinary-freelancer@example.com');
        $booking = BookingModel::create([
            'booking_reference' => 'BK-ORD-FREELANCE',
            'client_id' => $client->id,
            'booking_type' => 'freelancer',
            'provider_id' => $freelancer->id,
            'event_name' => 'Freelance Event',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 800,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_CONFIRMED,
            'payment_status' => BookingModel::PAYMENT_PAID,
        ]);
        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-ORD-FREELANCE',
            'amount' => 800,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", ['cancellation_reason' => 'The freelancer service is no longer needed for this project.'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->payment_status);
        $recovery = BookingCancellationRecoveryModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('refund_pending', $recovery->status);
        $this->assertNull($recovery->studio_id);

        $this->actingAs($this->owner)
            ->getJson('/_test/admin/refunds')
            ->assertOk()
            ->assertJsonPath('recoveries.0.id', $recovery->id);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Ordinary',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000006',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Ordinary Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(StudiosModel $studio, UserModel $client, string $status, string $paymentStatus, ?string $eventDate = null): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => $eventDate ?? now()->addDays(2)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function createAssignment(BookingModel $booking, StudiosModel $studio, UserModel $photographer, string $status): BookingAssignedPhotographerModel
    {
        return BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'photographer_id' => $photographer->id,
            'assigned_by' => $this->owner->id,
            'status' => $status,
            'assigned_at' => now(),
            'response_deadline' => now()->addDay(),
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
            
            $table->softDeletes();$table->timestamps();
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
