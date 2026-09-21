<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\StudioOwner\BookingController;
use App\Models\BookingModel;
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

class OwnerCompletionGateTest extends TestCase
{
    private UserModel $owner;

    private UserModel $client;

    private UserModel $photographer;

    private StudiosModel $studio;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        $this->owner = $this->createUser('owner', 'completion-owner@example.com');
        $this->client = $this->createUser('client', 'completion-client@example.com');
        $this->photographer = $this->createUser('studio-photographer', 'completion-photographer@example.com');
        $this->studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Completion Studio',
            'status' => 'verified',
        ]);

        Route::put('/_test/owner/bookings/{id}/complete', [BookingController::class, 'completeBooking']);
        Route::put('/_test/owner/bookings/{id}/status', [BookingController::class, 'updateStatus']);
        Route::get('/_test/owner/bookings/{id}/details', [BookingController::class, 'getBookingDetails']);
    }

    public function test_completion_is_allowed_when_fully_paid_and_assignments_delivered(): void
    {
        $booking = $this->createBooking(['event_date' => now()->addDays(2)->toDateString()]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 1000);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete");
        $response->assertOk()->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame(BookingModel::STATUS_COMPLETED, $booking->status);
        $this->assertNotNull($booking->completed_at);
    }

    public function test_completion_is_blocked_when_an_outstanding_balance_remains(): void
    {
        $booking = $this->createBooking(['event_date' => now()->addDays(2)->toDateString()]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 400);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('not fully paid', $response->json('message'));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_completion_is_blocked_when_no_photographer_is_assigned(): void
    {
        $booking = $this->createBooking(['event_date' => now()->addDays(2)->toDateString()]);
        $this->createPayment($booking, 1000);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('Assign at least one photographer', $response->json('message'));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_completion_is_blocked_when_the_balance_is_overdue(): void
    {
        $booking = $this->createBooking(['event_date' => now()->subDays(2)->toDateString()]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 300);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('overdue', $response->json('message'));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_completion_is_blocked_when_the_gallery_is_uploaded_but_still_a_draft(): void
    {
        $booking = $this->createBooking(['online_gallery' => true]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 1000);

        // Uploaded content, but the client cannot see it yet.
        StudioOnlineGalleryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $this->studio->id,
            'client_id' => $this->client->id,
            'total_photos' => 5,
            'images' => [],
            'status' => 'active',
            'gallery_status' => 'draft',
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('published', strtolower((string) $response->json('message')));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_completion_is_allowed_once_the_gallery_is_published(): void
    {
        $booking = $this->createBooking(['online_gallery' => true]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 1000);

        StudioOnlineGalleryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $this->studio->id,
            'client_id' => $this->client->id,
            'total_photos' => 5,
            'images' => [],
            'status' => 'active',
            'gallery_status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(BookingModel::STATUS_COMPLETED, $booking->fresh()->status);
    }

    public function test_completion_is_blocked_until_assignments_are_delivered(): void
    {
        $booking = $this->createBooking();
        $assignment = $this->createAssignment($booking, 'confirmed');
        $this->createPayment($booking, 1000);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('completed', $response->json('message'));

        $assignment->update(['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_status_update_completes_when_fully_paid(): void
    {
        $booking = $this->createBooking(['event_date' => now()->addDays(3)->toDateString()]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 1000);

        $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/status", [
                'status' => BookingModel::STATUS_COMPLETED,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(BookingModel::STATUS_COMPLETED, $booking->fresh()->status);
    }

    public function test_status_update_is_blocked_when_an_outstanding_balance_remains(): void
    {
        $booking = $this->createBooking(['event_date' => now()->addDays(3)->toDateString()]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 400);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/status", [
                'status' => BookingModel::STATUS_COMPLETED,
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('not fully paid', $response->json('message'));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_status_update_is_blocked_when_no_photographer_is_assigned(): void
    {
        $booking = $this->createBooking(['event_date' => now()->addDays(3)->toDateString()]);
        $this->createPayment($booking, 1000);

        $response = $this->actingAs($this->owner)
            ->putJson("/_test/owner/bookings/{$booking->id}/status", [
                'status' => BookingModel::STATUS_COMPLETED,
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('Assign at least one photographer', $response->json('message'));
        $this->assertSame(BookingModel::STATUS_IN_PROGRESS, $booking->fresh()->status);
    }

    public function test_booking_details_reports_the_overdue_payment_blocker(): void
    {
        $booking = $this->createBooking(['event_date' => now()->subDay()->toDateString()]);
        $this->createAssignment($booking, 'completed');
        $this->createPayment($booking, 300);

        $response = $this->actingAs($this->owner)
            ->getJson("/_test/owner/bookings/{$booking->id}/details")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('can_owner_complete', false);

        $blockers = implode(' ', $response->json('completion_blockers'));
        $this->assertStringContainsString('overdue', $blockers);
    }

    private function createBooking(array $overrides = []): BookingModel
    {
        $onlineGallery = $overrides['online_gallery'] ?? false;
        unset($overrides['online_gallery']);

        $booking = BookingModel::create(array_merge([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $this->client->id,
            'booking_type' => 'studio',
            'provider_id' => $this->studio->id,
            'event_name' => 'Completion Event',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 300,
            'remaining_balance' => 700,
            'payment_type' => 'downpayment',
            'status' => BookingModel::STATUS_IN_PROGRESS,
            'payment_status' => BookingModel::PAYMENT_PARTIALLY_PAID,
        ], $overrides));

        $package = PackagesModel::create([
            'studio_id' => $this->studio->id,
            'category_id' => null,
            'package_name' => 'Completion Package '.str()->random(4),
            'package_price' => 1000,
            'online_gallery' => $onlineGallery,
            'photographer_count' => 1,
            'status' => 'active',
        ]);

        \App\Models\BookingPackageModel::create([
            'booking_id' => $booking->id,
            'package_id' => $package->id,
            'package_type' => 'studio',
            'package_name' => $package->package_name,
            'package_price' => 1000,
        ]);

        return $booking;
    }

    private function createAssignment(BookingModel $booking, string $status): BookingAssignedPhotographerModel
    {
        return BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $this->studio->id,
            'photographer_id' => $this->photographer->id,
            'assigned_by' => $this->owner->id,
            'status' => $status,
            'assigned_at' => now(),
            'completed_at' => $status === 'completed' ? now() : null,
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
            'first_name' => 'Completion',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000009',
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
