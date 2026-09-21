<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Admin\BookingRefundController;
use App\Http\Controllers\StudioOwner\BookingController;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefundTargetDiscretionTest extends TestCase
{
    private UserModel $owner;

    private BookingModel $booking;

    private PaymentModel $payment;

    private BookingCancellationRecoveryModel $recovery;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        $this->owner = $this->createUser('owner', 'discretion-owner@example.com');
        $client = $this->createUser('client', 'discretion-client@example.com');
        $studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Discretion Studio',
            'status' => 'verified',
        ]);

        $this->booking = BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Discretion Event',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 300,
            'remaining_balance' => 700,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_CANCELLED,
            'payment_status' => BookingModel::PAYMENT_REFUND_PENDING,
            'cancelled_by' => 'client',
            'cancellation_reason' => 'The client cancelled after paying in full.',
        ]);

        $this->payment = PaymentModel::create([
            'booking_id' => $this->booking->id,
            'payment_reference' => 'PAY-DISCRETION',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        SystemRevenueModel::create([
            'transaction_reference' => 'REV-DISCRETION',
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
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

        $this->recovery = BookingCancellationRecoveryModel::create([
            'booking_id' => $this->booking->id,
            'studio_id' => $studio->id,
            'status' => 'refund_pending',
            'outcome_reason' => 'Client cancelled the fully paid booking.',
        ]);

        Route::post('/_test/owner/recoveries/{recoveryId}/escalate', [BookingController::class, 'escalatePhotographerCancellation']);
        Route::post('/_test/admin/refunds/{recoveryId}/complete', [BookingRefundController::class, 'complete']);
    }

    public function test_owner_sets_refund_target_and_admin_queue_uses_it_by_default(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$this->recovery->id}/escalate", ['refund_amount' => 250])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->recovery->refresh();
        $this->assertSame('250.00', $this->recovery->refund_amount);
        $this->assertSame('25.00', $this->recovery->refund_percentage);

        // No override is sent, so the admin queue executes the owner's amount.
        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$this->recovery->id}/complete", [
                'provider_refund_reference' => 'stripe_refund_owner_target',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        // A partial target refunds each payment only to its own share.
        $this->assertSame('partially_refunded', $this->payment->fresh()->status);
        $this->assertSame('250.00', $this->payment->fresh()->refunded_amount);
        $this->assertSame('refunded', $this->recovery->fresh()->status);
        $this->assertSame('refund_pending', $this->booking->fresh()->payment_status);
    }

    public function test_partial_target_allocates_across_payments_without_zeroing_the_paid_total(): void
    {
        $secondPayment = PaymentModel::create([
            'booking_id' => $this->booking->id,
            'payment_reference' => 'PAY-DISCRETION-SECOND',
            'amount' => 500,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        // Target 700 of the 1500 paid.
        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$this->recovery->id}/escalate", ['refund_amount' => 700])
            ->assertOk();

        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$this->recovery->id}/complete", [
                'provider_refund_references' => [
                    $this->payment->id => 'stripe_refund_partial_multi_1',
                    $secondPayment->id => 'stripe_refund_partial_multi_2',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->recovery->refresh();
        $this->assertSame('refunded', $this->recovery->status);

        // The first payment absorbs the whole 700 target and is partially refunded.
        $this->assertSame('partially_refunded', $this->payment->fresh()->status);
        $this->assertSame('700.00', $this->payment->fresh()->refunded_amount);
        // The second payment got nothing, so it is untouched rather than zeroed.
        $this->assertSame('succeeded', $secondPayment->fresh()->status);
        $this->assertSame('0.00', $secondPayment->fresh()->refunded_amount);

        // The paid total (1500) is not wiped out; only the target (700) is refunded.
        $this->assertSame('refund_pending', $this->booking->fresh()->payment_status);
        $this->assertSame(
            700.0,
            (float) PaymentModel::where('booking_id', $this->booking->id)->sum('refunded_amount')
        );
    }

    public function test_full_target_marks_payment_booking_and_revenue_refunded(): void
    {
        // No override and no stored target, so the full paid total is refunded.
        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$this->recovery->id}/complete", [
                'provider_refund_reference' => 'stripe_refund_full_default',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('refunded', $this->payment->fresh()->status);
        $this->assertSame('1000.00', $this->payment->fresh()->refunded_amount);
        $this->assertSame('refunded', $this->booking->fresh()->payment_status);
        $this->assertSame('refunded', SystemRevenueModel::where('payment_id', $this->payment->id)->value('status'));
    }

    public function test_owner_refund_target_cannot_exceed_the_total_paid(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$this->recovery->id}/escalate", ['refund_amount' => 1500])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertNull($this->recovery->fresh()->refund_amount);
    }

    public function test_admin_can_override_the_owner_amount_at_or_below_the_total_paid(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$this->recovery->id}/escalate", ['refund_amount' => 250])
            ->assertOk();

        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$this->recovery->id}/complete", [
                'provider_refund_reference' => 'stripe_refund_admin_override',
                'refund_amount' => 400,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->recovery->refresh();
        $this->assertSame('400.00', $this->recovery->refund_amount);
        $this->assertSame('40.00', $this->recovery->refund_percentage);
        $this->assertSame('400.00', $this->payment->fresh()->refunded_amount);
    }

    public function test_admin_override_above_the_total_paid_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/_test/admin/refunds/{$this->recovery->id}/complete", [
                'provider_refund_reference' => 'stripe_refund_too_high',
                'refund_amount' => 1500,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('succeeded', $this->payment->fresh()->status);
        $this->assertSame('refund_pending', $this->recovery->fresh()->status);
    }

    public function test_admin_can_refund_zero_when_the_owner_returns_nothing(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/_test/owner/recoveries/{$this->recovery->id}/escalate", ['refund_amount' => 0])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('0.00', $this->recovery->fresh()->refund_amount);
        $this->assertSame('0.00', $this->recovery->fresh()->refund_percentage);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Discretion',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000008',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
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
    }
}
