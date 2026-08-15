<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Admin\BookingRefundController;
use App\Http\Controllers\StudioOwner\BookingController;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerCancellationRefundTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'owner-cancel-owner@example.com');

        Route::put('/_test/owner/booking/{id}/status', [BookingController::class, 'updateStatus']);
        Route::get('/_test/admin/refunds', [BookingRefundController::class, 'index']);
        Route::post('/_test/admin/refunds/{recoveryId}/complete', [BookingRefundController::class, 'complete']);
    }

    public function test_owner_cancels_paid_booking_queues_refund_and_admin_completes(): void
    {
        $client = $this->createUser('client', 'owner-cancel-paid-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'owner-cancel-paid-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PAID);
        $assignment = $this->createAssignment($booking, $studio, $photographer, 'confirmed');
        $payment = PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-OWN-CANCEL',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);
        $revenue = SystemRevenueModel::create([
            'transaction_reference' => 'REV-OWN-CANCEL',
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

        $reason = 'Studio cannot accommodate this date due to an equipment issue.';

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/booking/{$booking->id}/status", [
                'status' => BookingModel::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);
        $response->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        $this->assertSame('studio', $booking->cancelled_by);
        $this->assertSame($reason, $booking->cancellation_reason);
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->payment_status);

        $this->assertSame('cancelled', $assignment->fresh()->status);

        $recovery = BookingCancellationRecoveryModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('refund_pending', $recovery->status);
        $this->assertSame($reason, $recovery->outcome_reason);
        $this->assertSame($studio->id, $recovery->studio_id);

        $this->actingAs($this->owner)
            ->getJson('/_test/admin/refunds')
            ->assertOk()
            ->assertJsonPath('recoveries.0.id', $recovery->id)
            ->assertJsonPath('recoveries.0.status', 'refund_pending');

        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$recovery->id}/complete", ['provider_refund_reference' => 'stripe_refund_owner_1'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('refunded', $revenue->fresh()->status);
        $this->assertSame(BookingModel::PAYMENT_REFUNDED, $booking->fresh()->payment_status);
        $this->assertSame('refunded', $recovery->fresh()->status);
    }

    public function test_owner_cancels_unpaid_booking_does_not_queue_refund(): void
    {
        $client = $this->createUser('client', 'owner-cancel-unpaid-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PENDING);
        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-OWN-CANCEL-UNPAID',
            'amount' => 500,
            'payment_method' => 'gcash',
            'status' => 'pending',
        ]);

        $this->actingAs($this->owner)
            ->putJson("/_test/owner/booking/{$booking->id}/status", [
                'status' => BookingModel::STATUS_CANCELLED,
                'cancellation_reason' => 'The studio is closing for renovation on the requested date.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        $this->assertSame('studio', $booking->cancelled_by);
        $this->assertSame(BookingModel::PAYMENT_PENDING, $booking->payment_status);
        $this->assertSame(0, BookingCancellationRecoveryModel::where('booking_id', $booking->id)->count());
    }

    public function test_owner_cancelling_booking_with_existing_recovery_requeues_it(): void
    {
        $client = $this->createUser('client', 'owner-cancel-recovery-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'owner-cancel-recovery-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, BookingModel::STATUS_CONFIRMED, BookingModel::PAYMENT_PAID);
        $this->createAssignment($booking, $studio, $photographer, 'cancelled');
        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-OWN-CANCEL-RECOVERY',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);
        $existing = BookingCancellationRecoveryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'status' => 'awaiting_replacement',
            'photographer_reason' => 'Photographer had a family emergency.',
        ]);

        $this->actingAs($this->owner)
            ->putJson("/_test/owner/booking/{$booking->id}/status", [
                'status' => BookingModel::STATUS_CANCELLED,
                'cancellation_reason' => 'Studio must cancel this booking after the photographer dropped out.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('refund_pending', $existing->fresh()->status);
        $this->assertSame(BookingModel::PAYMENT_REFUND_PENDING, $booking->fresh()->payment_status);
        $this->assertSame(1, BookingCancellationRecoveryModel::where('booking_id', $booking->id)->count());
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Owner',
            'last_name' => 'Cancel',
            'email' => $email,
            'mobile_number' => '09170000007',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Owner Cancel Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(StudiosModel $studio, UserModel $client, string $status, string $paymentStatus): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => now()->addDays(2)->toDateString(),
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
        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_type')->nullable();
            $table->string('package_name')->nullable();
            $table->decimal('package_price', 10, 2)->nullable();
            $table->json('package_inclusions')->nullable();
            $table->string('duration')->nullable();
            $table->unsignedInteger('maximum_edited_photos')->nullable();
            $table->json('coverage_scope')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('status')->default('draft');
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
