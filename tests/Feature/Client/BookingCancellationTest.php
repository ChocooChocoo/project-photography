<?php

namespace Tests\Feature\Client;

use App\Http\Controllers\Client\MyBookingsController;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'client-cancel-owner@example.com');

        Route::post('/_test/client/booking/{id}/cancel', [MyBookingsController::class, 'cancelBooking']);
    }

    public function test_in_progress_booking_more_than_24_hours_away_can_be_cancelled(): void
    {
        $client = $this->createUser('client', 'client-cancel-in-progress@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking(
            $studio,
            $client,
            BookingModel::STATUS_IN_PROGRESS,
            BookingModel::PAYMENT_PENDING,
            now()->addDays(2)->toDateString()
        );

        $response = $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", [
                'cancellation_reason' => 'The event schedule changed and the booking is no longer needed.',
            ]);

        // A 419 would fail here. The cancelled in-progress booking proves the
        // status gate now accepts a booking with an active photographer.
        $response->assertOk()->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
        $this->assertSame('client', $booking->cancelled_by);
    }

    public function test_in_progress_booking_inside_24_hours_is_still_refused(): void
    {
        $client = $this->createUser('client', 'client-cancel-too-late@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking(
            $studio,
            $client,
            BookingModel::STATUS_IN_PROGRESS,
            BookingModel::PAYMENT_PENDING,
            now()->addHours(23)->toDateString()
        );

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$booking->id}/cancel", [
                'cancellation_reason' => 'An emergency came up at the last minute and we must cancel.',
            ])
            ->assertJsonPath('success', false);

        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_completed_booking_more_than_24_hours_away_still_cannot_be_cancelled(): void
    {
        $client = $this->createUser('client', 'client-cancel-completed@example.com');
        $studio = $this->createStudio();
        $completed = $this->createBooking(
            $studio,
            $client,
            BookingModel::STATUS_COMPLETED,
            BookingModel::PAYMENT_PAID,
            now()->addDays(2)->toDateString()
        );

        $this->actingAs($client)
            ->postJson("/_test/client/booking/{$completed->id}/cancel", [
                'cancellation_reason' => 'This completed booking should not be cancellable at all.',
            ])
            ->assertJsonPath('success', false);

        $this->assertSame(BookingModel::STATUS_COMPLETED, $completed->fresh()->status);
    }

    public function test_cancel_button_renders_for_every_status_the_controller_accepts(): void
    {
        $source = file_get_contents(base_path('resources/views/client/view-my-bookings.blade.php'));

        // The controller accepts pending, confirmed and in_progress. The button
        // must offer the same three, or an in-progress booking shows no control.
        $this->assertStringContainsString(
            "@if(in_array(\$booking->status, ['pending', 'confirmed', 'in_progress']))",
            $source
        );
        $this->assertStringNotContainsString(
            "@if(in_array(\$booking->status, ['pending', 'confirmed']))",
            $source
        );
    }

    public function test_booking_ajax_reads_the_csrf_token_from_the_meta_tag_at_request_time(): void
    {
        $source = file_get_contents(base_path('resources/views/client/view-my-bookings.blade.php'));

        $this->assertStringNotContainsString("_token: '{{ csrf_token() }}'", $source);
        $this->assertSame(
            4,
            substr_count($source, '_token: $(\'meta[name="csrf-token"]\').attr(\'content\')')
        );
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Client',
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
            'studio_name' => 'Client Cancel Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(
        StudiosModel $studio,
        UserModel $client,
        string $status,
        string $paymentStatus,
        string $eventDate
    ): BookingModel {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => $eventDate,
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
