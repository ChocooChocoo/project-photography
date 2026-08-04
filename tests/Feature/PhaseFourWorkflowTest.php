<?php

namespace Tests\Feature;

use App\Http\Controllers\Client\MyBookingsController;
use App\Http\Controllers\Freelancer\BookingController as FreelancerBookingController;
use App\Models\BookingModel;
use App\Models\NotificationModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use App\Traits\Notifiable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Tests\TestCase;

class PhaseFourWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_booking_details_include_an_active_assigned_photographers_profile(): void
    {
        $client = $this->createUser('client', 'Client', 'photo-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'Avery', 'photo-photographer@example.com', 'profiles/avery.jpg');
        $studio = StudiosModel::create(['user_id' => $photographer->id, 'studio_name' => 'Focus Studio', 'status' => 'active']);
        $booking = $this->createBooking($client, $studio->id);

        BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'photographer_id' => $photographer->id,
            'assigned_by' => $photographer->id,
            'status' => 'confirmed',
        ]);

        Auth::setUser($client);

        $response = app(MyBookingsController::class)->getBookingDetails($booking->id);
        $assignment = $response->getData(true)['assignedPhotographers'][0];

        $this->assertSame('profiles/avery.jpg', $assignment['photographer']['profile_photo']);
    }

    public function test_photographer_assignment_notification_names_the_photographer(): void
    {
        $client = $this->createUser('client', 'Client', 'notification-client@example.com');
        $studio = StudiosModel::create(['user_id' => $client->id, 'studio_name' => 'Focus Studio', 'status' => 'active']);
        $booking = $this->createBooking($client, $studio->id);
        $notifier = new class {
            use Notifiable;
        };

        $notifier->notifyPhotographerAssigned($booking, $client, ['Avery Lens']);

        $this->assertStringContainsString('meet Avery Lens', NotificationModel::firstOrFail()->message);
    }

    public function test_featured_status_uses_the_current_accessible_plan_priority(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'featured-owner@example.com');
        $studio = StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'Focus Studio', 'status' => 'active']);
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'premium',
            'billing_cycle' => 'monthly',
            'plan_code' => 'STU_PREMIUM',
            'name' => 'Premium',
            'price' => 1000,
            'commission_rate' => 5,
            'trial_days' => 0,
            'priority_level' => 3,
            'status' => 'active',
        ]);
        StudioPlanModel::create([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-FEATURED',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'next_billing_date' => now()->addMonth(),
            'amount_paid' => 1000,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        $this->assertTrue($studio->fresh()->isFeatured());
    }

    public function test_booking_accepts_a_venue_landmark(): void
    {
        $client = $this->createUser('client', 'Client', 'landmark-client@example.com');
        $booking = BookingModel::create([
            'booking_reference' => 'BK-LANDMARK',
            'client_id' => $client->id,
            'booking_type' => 'freelancer',
            'provider_id' => $client->id,
            'event_date' => now()->addWeek()->toDateString(),
            'location_type' => 'on-location',
            'status' => 'pending',
            'payment_status' => 'pending',
            'venue_landmark' => 'Beside the public library',
        ]);

        $this->assertSame('Beside the public library', $booking->venue_landmark);
    }

    public function test_freelancer_confirms_a_direct_booking_without_an_assignment_record(): void
    {
        $freelancer = $this->createUser('freelancer', 'Freelancer', 'direct-freelancer@example.com');
        $booking = BookingModel::create([
            'booking_reference' => 'BK-DIRECT',
            'client_id' => $freelancer->id,
            'booking_type' => 'freelancer',
            'provider_id' => $freelancer->id,
            'event_date' => now()->addWeek()->toDateString(),
            'location_type' => 'on-location',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        Auth::setUser($freelancer);
        $response = app(FreelancerBookingController::class)->updateStatus(new Request(['status' => 'confirmed']), $booking->id);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(0, BookingAssignedPhotographerModel::where('booking_id', $booking->id)->count());
    }

    private function createUser(string $role, string $firstName, string $email, ?string $profilePhoto = null): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => $firstName,
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000000',
            'password' => 'secret',
            'profile_photo' => $profilePhoto,
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createBooking(UserModel $client, int $studioId): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studioId,
            'event_date' => now()->addWeek()->toDateString(),
            'location_type' => 'on-location',
            'status' => 'confirmed',
            'payment_status' => 'pending',
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
            $table->string('mobile_number');
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->string('status');
            $table->boolean('email_verified')->default(false);
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
            $table->date('event_date');
            $table->string('location_type')->nullable();
            $table->string('venue_landmark')->nullable();
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('studio_id');
            $table->foreignId('photographer_id');
            $table->foreignId('assigned_by');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_studio_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('photographer_id');
            $table->string('specialization')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('package_name')->nullable();
            $table->decimal('package_price', 10, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('priority_level')->default(1);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status');
            $table->string('status');
            $table->timestamps();
        });
    }
}
