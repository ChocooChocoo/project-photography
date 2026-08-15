<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\MyBookingsController;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientRefundsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::get('/_test/client/refunds', [MyBookingsController::class, 'refunds']);
    }

    public function test_client_sees_only_their_own_refunds_with_statuses(): void
    {
        $clientA = $this->createUser('client', 'refunds-client-a@example.com');
        $clientB = $this->createUser('client', 'refunds-client-b@example.com');
        $studio = $this->createStudio();

        $pendingBooking = $this->createBooking($studio, $clientA, 'BK-REF-PENDING');
        $this->createSucceededPayment($pendingBooking, 'PAY-REF-PENDING', 1000);
        BookingCancellationRecoveryModel::create([
            'booking_id' => $pendingBooking->id,
            'studio_id' => $studio->id,
            'status' => 'refund_pending',
            'outcome_reason' => 'Client cancelled and requested a full refund.',
        ]);

        $refundedBooking = $this->createBooking($studio, $clientA, 'BK-REF-DONE');
        $this->createSucceededPayment($refundedBooking, 'PAY-REF-DONE', 2500);
        $this->createRefundedPayment($refundedBooking, 'PAY-REF-DONE-REFUND', 2500);
        BookingCancellationRecoveryModel::create([
            'booking_id' => $refundedBooking->id,
            'studio_id' => $studio->id,
            'status' => 'refunded',
            'outcome_reason' => 'Studio cancelled the session.',
            'resolved_at' => now(),
        ]);

        $otherBooking = $this->createBooking($studio, $clientB, 'BK-REF-OTHER');
        $this->createSucceededPayment($otherBooking, 'PAY-REF-OTHER', 800);
        BookingCancellationRecoveryModel::create([
            'booking_id' => $otherBooking->id,
            'studio_id' => $studio->id,
            'status' => 'refund_pending',
            'outcome_reason' => 'Another client refund request.',
        ]);

        $response = $this->actingAs($clientA)->get('/_test/client/refunds');

        $response->assertOk();
        $response->assertSee('BK-REF-PENDING');
        $response->assertSee('Refund Pending');
        $response->assertSee('BK-REF-DONE');
        $response->assertSee('Refunded');
        $response->assertDontSee('BK-REF-OTHER');
        $response->assertSee('Client cancelled and requested a full refund.');
    }

    public function test_client_with_no_refunds_sees_empty_state(): void
    {
        $client = $this->createUser('client', 'refunds-empty-client@example.com');

        $this->actingAs($client)
            ->get('/_test/client/refunds')
            ->assertOk()
            ->assertSee('No refunds found');
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Refund',
            'last_name' => 'Client',
            'email' => $email,
            'mobile_number' => '09170000008',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->createUser('owner', 'refunds-owner@example.com')->id,
            'studio_name' => 'Refund Test Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(StudiosModel $studio, UserModel $client, string $reference): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => $reference,
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'category_id' => 1,
            'event_name' => 'Test Event',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 2500,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_CANCELLED,
            'payment_status' => BookingModel::PAYMENT_REFUND_PENDING,
        ]);
    }

    private function createSucceededPayment(BookingModel $booking, string $reference, float $amount): void
    {
        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => $reference,
            'amount' => $amount,
            'payment_method' => 'card',
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
    }

    private function createRefundedPayment(BookingModel $booking, string $reference, float $amount): void
    {
        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => $reference,
            'amount' => $amount,
            'payment_method' => 'card',
            'status' => 'refunded',
            'refund_reference' => 'STR-REF-'.$reference,
            'refunded_at' => now(),
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
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('slug')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('category_id')->nullable();
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
            $table->timestamp('paid_at')->nullable();
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
    }
}
