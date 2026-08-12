<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\BookingController;
use App\Models\Admin\CategoriesModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecurringBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::post('/test/bookings/store', [BookingController::class, 'store']);
        Route::get('/owner/bookings', fn () => 'ok')->name('owner.booking.index');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_recurring_booking_creates_child_sessions(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        $client = $this->createUser('client', 'recurring-client@example.com');
        $owner = $this->createUser('owner', 'recurring-owner@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Recurring Studio',
            'status' => 'verified',
            'downpayment_percentage' => 30,
        ]);

        StudioPlanModel::create([
            'studio_id' => $studio->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'trial_ends_at' => null,
            'end_date' => '2026-12-31',
        ]);

        StudioScheduleModel::create([
            'studio_id' => $studio->id,
            'operating_days' => ['thursday'],
            'booking_limit' => 3,
        ]);

        $category = CategoriesModel::create([
            'category_name' => 'Wedding',
            'status' => 'active',
        ]);

        $package = StudioPackagesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'package_name' => 'Classic Package',
            'package_description' => 'Test package',
            'package_inclusions' => ['4 hours coverage'],
            'duration' => 8,
            'maximum_edited_photos' => 200,
            'coverage_scope' => 'Single location',
            'package_price' => 10000,
            'package_location' => ['In-Studio'],
            'allow_multiple_locations' => false,
            'max_locations' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($client)->postJson('/test/bookings/store', [
            'type' => 'studio',
            'provider_id' => $studio->id,
            'category_id' => $category->id,
            'package_id' => $package->id,
            'event_date' => '2026-08-20',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'location_type' => 'in-studio',
            'payment_type' => 'downpayment',
            'special_requests' => null,
            'full_name' => 'Jane Client',
            'contact_number' => '09170000000',
            'email' => 'jane@example.com',
            'booking_frequency' => 'recurring',
            'recurrence_pattern' => ['frequency' => 'weekly', 'interval' => 1, 'sessions' => 4],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(4, BookingModel::count());

        $parent = BookingModel::whereNull('parent_booking_id')->sole();
        $this->assertSame('recurring', $parent->booking_frequency);
        $this->assertSame('weekly', $parent->recurrence_pattern['frequency']);
        $this->assertSame('2026-08-20', $parent->event_date->format('Y-m-d'));
        $this->assertNotNull($parent->expires_at);

        $children = BookingModel::where('parent_booking_id', $parent->id)
            ->orderBy('event_date')
            ->get();

        $this->assertCount(3, $children);
        $this->assertSame(
            ['2026-08-27', '2026-09-03', '2026-09-10'],
            $children->map(fn (BookingModel $child) => $child->event_date->format('Y-m-d'))->all()
        );

        $this->assertSame(4, BookingModel::pluck('booking_reference')->unique()->count());
        $this->assertSame(4, BookingModel::whereNotNull('expires_at')->count());
        $this->assertSame(4, BookingModel::where('status', 'pending')->count());
        $this->assertSame(4, BookingModel::where('payment_status', 'pending')->count());
    }

    public function test_non_recurring_booking_still_creates_single_row(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        $client = $this->createUser('client', 'single-client@example.com');
        $owner = $this->createUser('owner', 'single-owner@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Single Studio',
            'status' => 'verified',
            'downpayment_percentage' => 30,
        ]);

        StudioPlanModel::create([
            'studio_id' => $studio->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'trial_ends_at' => null,
            'end_date' => '2026-12-31',
        ]);

        StudioScheduleModel::create([
            'studio_id' => $studio->id,
            'operating_days' => ['thursday'],
            'booking_limit' => 3,
        ]);

        $category = CategoriesModel::create([
            'category_name' => 'Wedding',
            'status' => 'active',
        ]);

        $package = StudioPackagesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'package_name' => 'Classic Package',
            'package_description' => 'Test package',
            'package_inclusions' => ['4 hours coverage'],
            'duration' => 8,
            'maximum_edited_photos' => 200,
            'coverage_scope' => 'Single location',
            'package_price' => 10000,
            'package_location' => ['In-Studio'],
            'allow_multiple_locations' => false,
            'max_locations' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($client)->postJson('/test/bookings/store', [
            'type' => 'studio',
            'provider_id' => $studio->id,
            'category_id' => $category->id,
            'package_id' => $package->id,
            'event_date' => '2026-08-20',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'location_type' => 'in-studio',
            'payment_type' => 'downpayment',
            'special_requests' => null,
            'full_name' => 'Jane Client',
            'contact_number' => '09170000000',
            'email' => 'jane@example.com',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, BookingModel::count());

        $booking = BookingModel::sole();
        $this->assertSame('one_time', $booking->booking_frequency);
        $this->assertNull($booking->recurrence_pattern);
        $this->assertNull($booking->parent_booking_id);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Booking',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000004',
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
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->decimal('downpayment_percentage', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->string('status');
            $table->string('payment_status');
            $table->timestamp('trial_ends_at')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->json('operating_days')->nullable();
            $table->integer('booking_limit')->default(3);
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->string('package_name');
            $table->string('package_description')->nullable();
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
            $table->decimal('package_price', 10, 2);
            $table->json('package_location')->nullable();
            $table->boolean('allow_multiple_locations')->default(false);
            $table->integer('max_locations')->default(1);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('event_name')->nullable();
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location_type');
            $table->string('venue_name')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->json('multiple_locations')->nullable();
            $table->text('special_requests')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->string('deposit_policy')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('status');
            $table->string('payment_status');
            $table->timestamp('expires_at')->nullable();
            $table->string('booking_frequency')->default('one_time');
            $table->json('recurrence_pattern')->nullable();
            $table->unsignedBigInteger('parent_booking_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('package_id');
            $table->string('package_type');
            $table->string('package_name');
            $table->decimal('package_price', 10, 2);
            $table->text('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
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
