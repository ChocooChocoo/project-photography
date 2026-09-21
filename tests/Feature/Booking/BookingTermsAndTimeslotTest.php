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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingTermsAndTimeslotTest extends TestCase
{
    private UserModel $client;

    private UserModel $owner;

    private StudiosModel $studio;

    private CategoriesModel $category;

    private StudioPackagesModel $package;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-12 10:00:00');

        Schema::dropAllTables();
        $this->createSchema();

        Route::post('/test/bookings/store', [BookingController::class, 'store']);
        Route::post('/test/bookings/check-availability', [BookingController::class, 'checkAvailability']);
        Route::get('/owner/bookings', fn () => 'ok')->name('owner.booking.index');

        $this->owner = $this->createUser('owner', 'terms-owner@example.com');
        $this->client = $this->createUser('client', 'terms-client@example.com');
        $this->studio = $this->createStudio();
        $this->category = CategoriesModel::create([
            'category_name' => 'Wedding',
            'status' => 'active',
        ]);
        $this->package = StudioPackagesModel::create([
            'studio_id' => $this->studio->id,
            'category_id' => $this->category->id,
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

        StudioScheduleModel::create([
            'studio_id' => $this->studio->id,
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'booking_limit' => 5,
        ]);

        StudioPlanModel::create([
            'studio_id' => $this->studio->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'trial_ends_at' => null,
            'end_date' => '2026-12-31',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_store_rejects_booking_without_terms_agreement(): void
    {
        $payload = $this->bookingPayload();
        unset($payload['terms_agree']);

        $this->actingAs($this->client)
            ->postJson('/test/bookings/store', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms_agree');

        $this->assertSame(0, BookingModel::count());
    }

    public function test_store_rejects_explicit_false_terms_agreement(): void
    {
        $this->actingAs($this->client)
            ->postJson('/test/bookings/store', $this->bookingPayload(['terms_agree' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms_agree');

        $this->assertSame(0, BookingModel::count());
    }

    public function test_store_accepts_booking_when_terms_agreed(): void
    {
        $this->actingAs($this->client)
            ->postJson('/test/bookings/store', $this->bookingPayload(['terms_agree' => 1]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, BookingModel::count());
    }

    public function test_store_rejects_overlapping_timeslot(): void
    {
        $date = Carbon::tomorrow()->format('Y-m-d');
        $this->createExistingBooking($date, '09:00', '11:00');

        $response = $this->actingAs($this->client)
            ->postJson('/test/bookings/store', $this->bookingPayload([
                'event_date' => $date,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'terms_agree' => 1,
            ]));

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('time_overlap', true);

        // Only the pre-existing booking remains.
        $this->assertSame(1, BookingModel::count());
    }

    public function test_availability_endpoint_flags_overlapping_timeslot(): void
    {
        $date = Carbon::tomorrow()->format('Y-m-d');
        $this->createExistingBooking($date, '09:00', '11:00');

        $this->actingAs($this->client)
            ->postJson('/test/bookings/check-availability', [
                'type' => 'studio',
                'provider_id' => $this->studio->id,
                'date' => $date,
                'start_time' => '10:00',
                'end_time' => '12:00',
            ])
            ->assertOk()
            ->assertJson([
                'available' => false,
                'time_overlap' => true,
            ]);
    }

    public function test_availability_endpoint_allows_non_overlapping_timeslot(): void
    {
        $date = Carbon::tomorrow()->format('Y-m-d');
        $this->createExistingBooking($date, '09:00', '11:00');

        $this->actingAs($this->client)
            ->postJson('/test/bookings/check-availability', [
                'type' => 'studio',
                'provider_id' => $this->studio->id,
                'date' => $date,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ])
            ->assertOk()
            ->assertJson([
                'available' => true,
            ]);
    }

    public function test_booking_form_step_validation_gates_terms_and_overlap(): void
    {
        // The step validation runs in the browser, so assert the Blade template
        // wires the terms gate, the submitted flag, and the overlap rejection.
        $blade = file_get_contents(resource_path('views/client/booking-forms.blade.php'));

        $this->assertStringContainsString('id="termsCheck"', $blade);
        $this->assertStringContainsString("terms_agree: \$('#termsCheck').is(':checked') ? 1 : 0", $blade);
        $this->assertStringContainsString("if (!\$('#termsCheck').is(':checked'))", $blade);
        $this->assertStringContainsString("dateStatusText.includes('overlap')", $blade);
        $this->assertStringContainsString('You must agree to the terms and conditions.', $blade);
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

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Terms Studio',
            'status' => 'verified',
            'downpayment_percentage' => 30,
        ]);
    }

    private function bookingPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'studio',
            'provider_id' => $this->studio->id,
            'category_id' => $this->category->id,
            'package_id' => $this->package->id,
            'event_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location_type' => 'in-studio',
            'special_requests' => null,
            'full_name' => 'Jane Client',
            'contact_number' => '09170000000',
            'email' => 'jane@example.com',
            'payment_type' => 'downpayment',
            'booking_frequency' => 'one_time',
            'terms_agree' => 1,
        ], $overrides);
    }

    private function createExistingBooking(string $date, string $start, string $end): BookingModel
    {
        // Insert raw so the DATE column holds a plain Y-m-d value, matching how
        // MySQL stores it (SQLite would otherwise keep the Eloquent date cast's
        // "Y-m-d H:i:s" text and break the equality used by the overlap query).
        $id = DB::table('tbl_bookings')->insertGetId([
            'booking_reference' => 'BK-EXIST-' . strtoupper(str()->random(6)),
            'client_id' => $this->client->id,
            'booking_type' => 'studio',
            'provider_id' => $this->studio->id,
            'category_id' => $this->category->id,
            'event_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'location_type' => 'in-studio',
            'total_amount' => 10000,
            'down_payment' => 3000,
            'remaining_balance' => 7000,
            'deposit_policy' => '30%',
            'payment_type' => 'downpayment',
            'status' => BookingModel::STATUS_PENDING,
            'payment_status' => BookingModel::PAYMENT_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return BookingModel::findOrFail($id);
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
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->decimal('downpayment_percentage', 5, 2)->nullable();
            $table->softDeletes();
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
            $table->softDeletes();
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
