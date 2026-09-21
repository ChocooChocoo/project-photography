<?php

namespace Tests\Feature\StudioManagement;

use App\Http\Controllers\Client\BookingController;
use App\Models\Admin\CategoriesModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepositPolicyTest extends TestCase
{
    private UserModel $client;

    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner');
        $this->client = $this->createUser('client');

        Route::post('/_test/client/booking', [BookingController::class, 'store']);
        Route::post('/_test/client/booking-summary', [BookingController::class, 'getSummary']);
        Route::get('/_test/owner/bookings', fn () => 'ok')->name('owner.booking.index');
    }

    public function test_studio_without_downpayment_creates_a_full_payment_booking(): void
    {
        $studio = $this->createStudio(['requires_downpayment' => false]);

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', $this->bookingPayload($studio))
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking = BookingModel::latest('id')->first();

        $this->assertSame('full_payment', $booking->payment_type);
        $this->assertSame(10000, $booking->getRawOriginal('down_payment'));
        $this->assertSame(0, $booking->getRawOriginal('remaining_balance'));
        $this->assertSame('100% (Full payment)', $booking->deposit_policy);
    }

    public function test_studio_summary_honors_full_payment_selection(): void
    {
        $studio = $this->createStudio(['requires_downpayment' => true, 'downpayment_percentage' => 30]);
        $payload = $this->bookingPayload($studio);

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking-summary', [
                'package_id' => $payload['package_id'],
                'type' => 'studio',
                'payment_type' => 'full_payment',
            ])
            ->assertOk()
            ->assertJsonPath('summary.payment_type', 'full_payment')
            ->assertJsonPath('summary.down_payment', '10,000.00')
            ->assertJsonPath('summary.remaining_balance', '0.00');
    }

    public function test_studio_full_payment_selection_persists_and_creates_full_initial_payment(): void
    {
        $studio = $this->createStudio(['requires_downpayment' => true, 'downpayment_percentage' => 30]);
        $payload = $this->bookingPayload($studio);
        $payload['payment_type'] = 'full_payment';

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking = BookingModel::latest('id')->first();
        $payment = PaymentModel::latest('id')->first();

        $this->assertSame('full_payment', $booking->payment_type);
        $this->assertSame(10000, $booking->getRawOriginal('down_payment'));
        $this->assertSame(0, $booking->getRawOriginal('remaining_balance'));
        $this->assertSame(10000, $payment->getRawOriginal('amount'));
    }

    public function test_studio_with_downpayment_keeps_the_percentage_deposit(): void
    {
        $studio = $this->createStudio(['requires_downpayment' => true, 'downpayment_percentage' => 30]);

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', $this->bookingPayload($studio))
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking = BookingModel::latest('id')->first();

        $this->assertSame('downpayment', $booking->payment_type);
        $this->assertSame(3000, $booking->getRawOriginal('down_payment'));
        $this->assertSame(7000, $booking->getRawOriginal('remaining_balance'));
        $this->assertSame('30.00%', $booking->deposit_policy);
    }

    public function test_studio_without_a_downpayment_flag_defaults_to_required_deposit(): void
    {
        $studio = $this->createStudio(['downpayment_percentage' => 50]);

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', $this->bookingPayload($studio))
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking = BookingModel::latest('id')->first();

        $this->assertSame('downpayment', $booking->payment_type);
        $this->assertSame(5000, $booking->getRawOriginal('down_payment'));
        $this->assertSame('50.00%', $booking->deposit_policy);
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $role.'-deposit@example.com',
            'mobile_number' => '09170000004',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(array $overrides = []): StudiosModel
    {
        return StudiosModel::create(array_merge([
            'user_id' => $this->owner->id,
            'studio_name' => 'Deposit Studio',
            'status' => 'verified',
            'downpayment_percentage' => 30,
            'max_clients_per_day' => 5,
            'advance_booking_days' => 3,
        ], $overrides));
    }

    private function bookingPayload(StudiosModel $studio): array
    {
        $category = CategoriesModel::create([
            'category_name' => 'Wedding Photography',
            'description' => 'Wedding coverage.',
            'status' => 'active',
        ]);

        $package = StudioPackagesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'package_name' => 'Basic Wedding Package',
            'package_description' => 'Standard coverage.',
            'package_inclusions' => ['4 hours coverage'],
            'duration' => 4,
            'maximum_edited_photos' => 100,
            'coverage_scope' => 'On-Site',
            'package_price' => 10000,
            'package_location' => ['In-Studio'],
            'allow_multiple_locations' => false,
            'max_locations' => 1,
            'status' => 'active',
        ]);

        StudioScheduleModel::create([
            'studio_id' => $studio->id,
            'operating_days' => [strtolower(Carbon::tomorrow()->format('l'))],
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'booking_limit' => 5,
            'advance_booking' => 3,
        ]);

        \App\Models\StudioPlanModel::create([
            'studio_id' => $studio->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'end_date' => Carbon::today()->addDays(30),
        ]);

        return [
            'type' => 'studio',
            'provider_id' => $studio->id,
            'category_id' => $category->id,
            'package_id' => $package->id,
            'event_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location_type' => 'in-studio',
            'special_requests' => 'None',
            'full_name' => 'Client User',
            'contact_number' => '09170000000',
            'email' => 'client-deposit@example.com',
            'payment_type' => 'downpayment',
            'booking_frequency' => 'one_time',
            'terms_agree' => 1,
        ];
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
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->decimal('downpayment_percentage', 8, 2)->nullable();
            $table->boolean('requires_downpayment')->default(true);
            $table->integer('max_clients_per_day')->nullable();
            $table->integer('advance_booking_days')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->text('operating_days')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->integer('booking_limit')->nullable();
            $table->integer('advance_booking')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->date('end_date')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->date('grace_ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->string('package_name');
            $table->text('package_description')->nullable();
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
            $table->string('booking_reference');
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->foreignId('provider_id');
            $table->foreignId('category_id');
            $table->date('event_date');
            $table->string('start_time');
            $table->string('end_time');
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
            $table->string('deposit_policy');
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->timestamp('expires_at')->nullable();
            $table->string('booking_frequency')->nullable();
            $table->json('recurrence_pattern')->nullable();
            $table->foreignId('parent_booking_id')->nullable();
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
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('payment_reference');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->timestamps();
        });
    }
}
