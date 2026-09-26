<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\StudioOwner\BookingController;
use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingAssignmentDomainTest extends TestCase
{
    private UserModel $owner;

    private UserModel $client;

    private StudiosModel $studio;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        $this->owner = $this->createUser('owner', 'domain-owner@example.com');
        $this->client = $this->createUser('client', 'domain-client@example.com');
        $this->studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Domain Studio',
            'status' => 'verified',
        ]);

        Route::post('/_test/owner/booking/{bookingId}/assign', [BookingController::class, 'assignPhotographers']);
        Route::put('/_test/owner/bookings/{id}/complete', [BookingController::class, 'completeBooking']);
        Route::get('/_test/owner/bookings/{id}/details', [BookingController::class, 'getBookingDetails']);
    }

    public function test_assignment_is_refused_for_a_completed_booking(): void
    {
        $photographer = $this->createUser('studio-photographer', 'completed-target@example.com');
        $booking = $this->createBooking('completed', 1);

        $response = $this->actingAs($this->owner)
            ->postJson("/_test/owner/booking/{$booking->id}/assign", [
                'photographer_ids' => [$photographer->id],
            ]);

        $response->assertOk()->assertJsonPath('success', false);
        $this->assertSame(
            'Cannot assign photographers to a booking that is already completed or cancelled.',
            $response->json('message')
        );
        $this->assertSame(0, BookingAssignedPhotographerModel::where('booking_id', $booking->id)->count());
    }

    public function test_assignment_is_refused_for_a_cancelled_booking(): void
    {
        $photographer = $this->createUser('studio-photographer', 'cancelled-target@example.com');
        $booking = $this->createBooking('cancelled', 1);

        $response = $this->actingAs($this->owner)
            ->postJson("/_test/owner/booking/{$booking->id}/assign", [
                'photographer_ids' => [$photographer->id],
            ]);

        $response->assertOk()->assertJsonPath('success', false);
        $this->assertSame(
            'Cannot assign photographers to a booking that is already completed or cancelled.',
            $response->json('message')
        );
        $this->assertSame(0, BookingAssignedPhotographerModel::where('booking_id', $booking->id)->count());
    }

    public function test_assignment_is_allowed_for_an_in_progress_booking_with_a_free_slot(): void
    {
        $declined = $this->createUser('studio-photographer', 'declined@example.com');
        $replacement = $this->createUser('studio-photographer', 'replacement@example.com');
        $booking = $this->createBooking('in_progress', 1);
        $this->createAssignment($booking, $declined, 'cancelled');

        $response = $this->actingAs($this->owner)
            ->postJson("/_test/owner/booking/{$booking->id}/assign", [
                'photographer_ids' => [$replacement->id],
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('tbl_booking_assigned_photographers', [
            'booking_id' => $booking->id,
            'photographer_id' => $replacement->id,
            'status' => 'assigned',
        ]);
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_a_cancelled_assignment_does_not_consume_a_slot_or_block_reassignment(): void
    {
        $photographer = $this->createUser('studio-photographer', 'cancel-then-return@example.com');
        $booking = $this->createBooking('confirmed', 1);
        $this->createAssignment($booking, $photographer, 'cancelled');

        $details = $this->actingAs($this->owner)
            ->getJson("/_test/owner/bookings/{$booking->id}/details")
            ->assertOk();
        $this->assertSame(0, $details->json('current_assigned_count'));

        $response = $this->actingAs($this->owner)
            ->postJson("/_test/owner/booking/{$booking->id}/assign", [
                'photographer_ids' => [$photographer->id],
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSame(
            1,
            BookingAssignedPhotographerModel::where('booking_id', $booking->id)
                ->where('photographer_id', $photographer->id)
                ->where('status', 'assigned')
                ->count()
        );
    }

    public function test_assignment_notification_targets_a_page_not_the_json_details_endpoint(): void
    {
        $photographer = $this->createUser('studio-photographer', 'route-target@example.com');
        $booking = $this->createBooking('confirmed', 1);

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/booking/{$booking->id}/assign", [
                'photographer_ids' => [$photographer->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $notification = NotificationModel::query()
            ->where('user_id', $this->owner->id)
            ->where('type', 'photographer_assigned')
            ->latest('id')
            ->first();

        $this->assertNotNull($notification, 'The owner should receive an assignment notification.');

        // Clicking the notification navigates the browser, so the stored target
        // must be a page. The details endpoint answers with JSON for the modal.
        $route = $notification->data['route'];
        $this->assertSame(route('owner.booking.index', [], false), $route);
        $this->assertStringNotContainsString('/details', $route);
    }

    public function test_completion_is_refused_with_the_model_gallery_message_through_the_controller(): void
    {
        $photographer = $this->createUser('studio-photographer', 'gallery-target@example.com');
        $booking = $this->createBooking('in_progress', 1, onlineGallery: true);
        $this->createAssignment($booking, $photographer, 'completed', now());
        $this->createPayment($booking, 1000);
        StudioOnlineGalleryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $this->studio->id,
            'client_id' => $this->client->id,
            'images' => [],
            'total_photos' => 0,
            'status' => 'active',
            'gallery_status' => 'draft',
        ]);

        $expected = $booking->fresh()->getGalleryCompletionBlockReason();

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete");

        $response->assertStatus(403)->assertJsonPath('success', false);
        $this->assertNotNull($expected);
        $this->assertSame(
            "Cannot mark as completed until at least one image is uploaded to the client's online gallery.",
            $expected
        );
        $this->assertSame($expected, $response->json('message'));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_current_assigned_count_counts_active_assignments_only(): void
    {
        $cancelled = $this->createUser('studio-photographer', 'count-cancelled@example.com');
        $completed = $this->createUser('studio-photographer', 'count-completed@example.com');
        $confirmed = $this->createUser('studio-photographer', 'count-confirmed@example.com');
        $booking = $this->createBooking('confirmed', 3);

        $this->createAssignment($booking, $cancelled, 'cancelled');
        $this->createAssignment($booking, $completed, 'completed', now());
        $this->createAssignment($booking, $confirmed, 'confirmed');

        $response = $this->actingAs($this->owner)
            ->getJson("/_test/owner/bookings/{$booking->id}/details")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $response->json('current_assigned_count'));
    }

    private function createBooking(string $status, int $photographerCount, bool $onlineGallery = false): BookingModel
    {
        $booking = BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $this->client->id,
            'booking_type' => 'studio',
            'provider_id' => $this->studio->id,
            'event_name' => 'Domain Event',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 300,
            'remaining_balance' => 700,
            'payment_type' => 'downpayment',
            'status' => $status,
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ]);

        $package = PackagesModel::create([
            'studio_id' => $this->studio->id,
            'category_id' => null,
            'package_name' => 'Domain Package '.str()->random(4),
            'package_price' => 1000,
            'online_gallery' => $onlineGallery,
            'photographer_count' => $photographerCount,
            'status' => 'active',
        ]);

        BookingPackageModel::create([
            'booking_id' => $booking->id,
            'package_id' => $package->id,
            'package_type' => 'studio',
            'package_name' => $package->package_name,
            'package_price' => 1000,
        ]);

        return $booking;
    }

    private function createAssignment(BookingModel $booking, UserModel $photographer, string $status, $completedAt = null): BookingAssignedPhotographerModel
    {
        return BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $this->studio->id,
            'photographer_id' => $photographer->id,
            'assigned_by' => $this->owner->id,
            'status' => $status,
            'assigned_at' => now(),
            'response_deadline' => now()->addDay(),
            'completed_at' => $completedAt,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ]);
    }

    private function createPayment(BookingModel $booking, float $amount): PaymentModel
    {
        return PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-'.str()->upper(str()->random(10)),
            'amount' => $amount,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Domain',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000011',
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
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('status')->default('active');
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
            $table->timestamp('revision_requested_at')->nullable();
            $table->timestamp('revision_deadline')->nullable();
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
            $table->timestamp('on_site_at')->nullable();
            $table->timestamp('client_confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('recovery_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_photographers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('photographer_id');
            $table->string('position')->nullable();
            $table->string('specialization')->nullable();
            $table->unsignedInteger('years_of_experience')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->nullable();
            $table->boolean('online_gallery')->default(false);
            $table->unsignedInteger('photographer_count')->default(1);
            $table->string('status')->default('active');
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
            $table->text('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('gallery_reference')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('active');
            $table->integer('total_photos')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->string('gallery_status')->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'cancelled'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('leave_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_days', 5, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
