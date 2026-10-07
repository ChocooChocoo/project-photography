<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\BookingContractController;
use App\Http\Controllers\Client\BookingController;
use App\Models\Admin\CategoriesModel;
use App\Models\BookingContractModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingContractTest extends TestCase
{
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private UserModel $client;

    private UserModel $owner;

    private StudiosModel $studio;

    private CategoriesModel $category;

    private StudioPackagesModel $package;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Storage::fake('public');

        $this->owner = $this->createUser('owner', 'owner-contract@example.com');
        $this->client = $this->createUser('client', 'client-contract@example.com');
        $this->studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Contract Studio',
            'status' => 'verified',
            'max_clients_per_day' => 3,
        ]);
        $this->category = CategoriesModel::create(['category_name' => 'Weddings', 'description' => 'x', 'status' => 'active']);
        $this->package = StudioPackagesModel::create([
            'studio_id' => $this->studio->id,
            'category_id' => $this->category->id,
            'package_name' => 'Multi Day Package',
            'package_description' => 'Coverage.',
            'package_inclusions' => ['Photos'],
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
            'studio_id' => $this->studio->id,
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'booking_limit' => 5,
            'advance_booking' => 3,
        ]);
        \App\Models\StudioPlanModel::create([
            'studio_id' => $this->studio->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'end_date' => Carbon::today()->addDays(60),
        ]);

        Route::post('/_test/booking', [BookingController::class, 'store']);
        Route::post('/_test/availability', [BookingController::class, 'checkAvailability']);
        Route::post('/_test/calendar', [BookingController::class, 'getCalendarAvailability']);
        Route::get('/_test/contract/{booking}', [BookingContractController::class, 'show']);
        Route::post('/_test/contract/{booking}/sign', [BookingContractController::class, 'sign']);
        Route::get('/_test/owner/bookings', fn () => 'ok')->name('owner.booking.index');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'studio',
            'provider_id' => $this->studio->id,
            'category_id' => $this->category->id,
            'package_id' => $this->package->id,
            'event_date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'location_type' => 'in-studio',
            'full_name' => 'Client User',
            'contact_number' => '09170000000',
            'email' => 'client-contract@example.com',
            'booking_frequency' => 'one_time',
            'terms_agree' => 1,
        ], $overrides);
    }

    private function longTermPayload(array $overrides = []): array
    {
        return $this->payload(array_merge([
            'event_date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'event_end_date' => Carbon::today()->addDays(12)->format('Y-m-d'),
        ], $overrides));
    }

    private function book(array $payload, ?UserModel $as = null)
    {
        return $this->actingAs($as ?? $this->client)->postJson('/_test/booking', $payload);
    }

    public function test_one_day_booking_is_short_term_and_has_no_contract(): void
    {
        $this->book($this->payload())->assertOk();

        $booking = BookingModel::firstOrFail();
        $this->assertSame('short_term', $booking->booking_duration_type);
        $this->assertNull($booking->event_end_date);
        $this->assertNotNull($booking->terms_accepted_at);
        $this->assertSame(0, BookingContractModel::count());
        $this->assertTrue($booking->canBeConfirmed());
    }

    public function test_multi_day_booking_is_long_term_and_creates_a_contract(): void
    {
        $this->book($this->longTermPayload())->assertOk();

        $booking = BookingModel::firstOrFail();
        $this->assertSame('long_term', $booking->booking_duration_type);
        $this->assertSame(Carbon::today()->addDays(12)->format('Y-m-d'), $booking->event_end_date->format('Y-m-d'));
        $this->assertNotNull($booking->terms_accepted_at);
        $this->assertTrue($booking->expires_at->gt(now()->addDays(6)));

        $contract = BookingContractModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertStringContainsString('Contract Studio', $contract->terms_html);
        $this->assertStringContainsString('Client User', $contract->terms_html);
        $this->assertStringContainsString('Multi Day Package', $contract->terms_html);
        $this->assertSame('pending', $contract->status);

        // Both parties are told the contract is waiting
        $this->assertSame(2, \App\Models\NotificationModel::where('type', 'contract_ready')->count());
    }

    public function test_recurring_and_multi_day_cannot_be_combined(): void
    {
        $this->book($this->longTermPayload([
            'booking_frequency' => 'recurring',
            'recurrence_pattern' => ['frequency' => 'weekly', 'interval' => 1, 'sessions' => 2],
        ]))->assertStatus(422)->assertJsonValidationErrors('event_end_date');

        $this->assertSame(0, BookingModel::count());
    }

    public function test_span_longer_than_thirty_days_is_rejected(): void
    {
        $this->book($this->longTermPayload(['event_end_date' => Carbon::today()->addDays(50)->format('Y-m-d')]))
            ->assertStatus(422)->assertJsonValidationErrors('event_end_date');
    }

    public function test_time_conflict_is_detected_on_any_day_of_a_multi_day_booking(): void
    {
        $this->book($this->longTermPayload())->assertOk();

        // Day 2 of the existing 3-day booking, overlapping hours
        $this->book($this->payload([
            'event_date' => Carbon::today()->addDays(11)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]), $this->createUser('client', 'other-client@example.com'))
            ->assertStatus(400)->assertJsonPath('time_overlap', true);

        // Same day, different hours is fine
        $this->book($this->payload([
            'event_date' => Carbon::today()->addDays(11)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
        ]), $this->createUser('client', 'third-client@example.com'))->assertOk();

        // A new multi-day request that only touches the last day also conflicts
        $this->actingAs($this->client)->postJson('/_test/availability', [
            'type' => 'studio',
            'provider_id' => $this->studio->id,
            'date' => Carbon::today()->addDays(12)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(14)->format('Y-m-d'),
            'start_time' => '09:30',
            'end_time' => '10:30',
        ])->assertOk()->assertJsonPath('available', false)->assertJsonPath('time_overlap', true);
    }

    public function test_capacity_is_counted_per_day_across_the_span(): void
    {
        $this->studio->update(['max_clients_per_day' => 1]);
        $this->book($this->payload(['event_date' => Carbon::today()->addDays(11)->format('Y-m-d'), 'start_time' => '09:00', 'end_time' => '10:00']))->assertOk();

        $this->actingAs($this->client)->postJson('/_test/availability', [
            'type' => 'studio',
            'provider_id' => $this->studio->id,
            'date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(12)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
        ])->assertOk()->assertJsonPath('available', false)->assertJsonPath('existing_bookings', 1);
    }

    public function test_calendar_counts_a_multi_day_booking_on_every_day_it_spans(): void
    {
        $start = Carbon::today()->addDays(10);
        $this->book($this->longTermPayload())->assertOk();

        $response = $this->actingAs($this->client)->postJson('/_test/calendar', [
            'type' => 'studio',
            'provider_id' => $this->studio->id,
            'year' => $start->year,
            'month' => $start->month,
        ])->assertOk();

        foreach ([10, 11, 12] as $offset) {
            $this->assertSame(1, $response->json('availability.'.Carbon::today()->addDays($offset)->format('Y-m-d').'.booking_count'), "day +$offset");
        }
        $this->assertSame(0, $response->json('availability.'.Carbon::today()->addDays(13)->format('Y-m-d').'.booking_count') ?? 0);
    }

    public function test_confirmation_gate_blocks_long_term_until_both_sign(): void
    {
        $this->book($this->longTermPayload())->assertOk();
        $booking = BookingModel::with('contract')->firstOrFail();

        $this->assertFalse($booking->canBeConfirmed());
        $this->assertFalse($booking->canTransitionTo(BookingModel::STATUS_CONFIRMED));
        $this->assertArrayNotHasKey(BookingModel::STATUS_CONFIRMED, $booking->getAvailableStatuses());
        $this->assertFalse($booking->confirmIfEligible());
        $this->assertSame('pending', $booking->fresh()->status);

        $booking->contract->update(['client_signed_at' => now(), 'studio_signed_at' => now()]);
        $booking->unsetRelation('contract');

        $this->assertTrue($booking->canBeConfirmed());
        $this->assertTrue($booking->canTransitionTo(BookingModel::STATUS_CONFIRMED));
        $this->assertTrue($booking->confirmIfEligible());
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_short_term_booking_still_confirms_without_a_contract(): void
    {
        $this->book($this->payload())->assertOk();
        $booking = BookingModel::firstOrFail();

        $this->assertTrue($booking->confirmIfEligible());
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_paid_long_term_booking_confirms_only_after_both_signatures(): void
    {
        $this->book($this->longTermPayload())->assertOk();
        $booking = BookingModel::firstOrFail();
        $booking->update(['payment_status' => 'paid']);

        // payment alone does not confirm
        $this->assertFalse($booking->fresh()->confirmIfEligible());

        $this->actingAs($this->client)->postJson("/_test/contract/{$booking->id}/sign", [
            'signed_name' => 'Client User', 'signature' => self::PNG,
        ])->assertOk()->assertJsonPath('fully_signed', false);
        $this->assertSame('pending', $booking->fresh()->status);

        $this->actingAs($this->owner)->postJson("/_test/contract/{$booking->id}/sign", [
            'signed_name' => 'Owner Person', 'signature' => self::PNG,
        ])->assertOk()->assertJsonPath('fully_signed', true)->assertJsonPath('booking_status', 'confirmed');

        $contract = BookingContractModel::firstOrFail();
        $this->assertSame('fully_signed', $contract->status);
        Storage::disk('public')->assertExists($contract->client_signature);
        Storage::disk('public')->assertExists($contract->studio_signature);
        $this->assertSame('Owner Person', $contract->studio_signed_by);
        $this->assertSame(2, \App\Models\NotificationModel::where('type', 'contract_signed')->count());
    }

    public function test_unpaid_booking_stays_pending_after_both_sign(): void
    {
        $this->book($this->longTermPayload())->assertOk();
        $booking = BookingModel::firstOrFail();

        $this->actingAs($this->client)->postJson("/_test/contract/{$booking->id}/sign", ['signed_name' => 'Client User', 'signature' => self::PNG])->assertOk();
        $this->actingAs($this->owner)->postJson("/_test/contract/{$booking->id}/sign", ['signed_name' => 'Owner Person', 'signature' => self::PNG])->assertOk();

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_only_the_two_parties_can_open_or_sign_the_contract(): void
    {
        $this->book($this->longTermPayload())->assertOk();
        $booking = BookingModel::firstOrFail();
        $stranger = $this->createUser('client', 'stranger@example.com');
        $otherOwner = $this->createUser('owner', 'other-owner@example.com');

        foreach ([$stranger, $otherOwner] as $user) {
            $this->actingAs($user)->getJson("/_test/contract/{$booking->id}")->assertForbidden();
            $this->actingAs($user)->postJson("/_test/contract/{$booking->id}/sign", ['signed_name' => 'Intruder', 'signature' => self::PNG])->assertForbidden();
        }

        $this->assertNull(BookingContractModel::firstOrFail()->client_signed_at);
    }

    public function test_bad_signatures_and_double_signing_are_rejected(): void
    {
        $this->book($this->longTermPayload())->assertOk();
        $booking = BookingModel::firstOrFail();
        $url = "/_test/contract/{$booking->id}/sign";

        $this->actingAs($this->client)->postJson($url, ['signed_name' => 'Client User', 'signature' => 'not-an-image'])->assertStatus(422)->assertJsonValidationErrors('signature');
        $this->actingAs($this->client)->postJson($url, ['signed_name' => 'Client User', 'signature' => 'data:image/png;base64,'.base64_encode('this is not a png')])->assertStatus(422);
        $this->actingAs($this->client)->postJson($url, ['signed_name' => 'Client User', 'signature' => 'data:image/png;base64,'.str_repeat('A', 200001)])->assertStatus(422)->assertJsonValidationErrors('signature');
        $this->actingAs($this->client)->postJson($url, ['signed_name' => 'Hi', 'signature' => self::PNG])->assertStatus(422)->assertJsonValidationErrors('signed_name');

        $this->actingAs($this->client)->postJson($url, ['signed_name' => 'Client User', 'signature' => self::PNG])->assertOk();
        $this->actingAs($this->client)->postJson($url, ['signed_name' => 'Client User', 'signature' => self::PNG])->assertStatus(422);
    }

    public function test_short_term_booking_has_no_contract_page(): void
    {
        $this->book($this->payload())->assertOk();
        $booking = BookingModel::firstOrFail();

        $this->actingAs($this->client)->getJson("/_test/contract/{$booking->id}")->assertNotFound();
    }

    public function test_contract_template_is_used_and_values_are_escaped(): void
    {
        $this->studio->update(['contract_template' => 'Agreement for {{client_name}} with {{provider_name}}: <script>alert(1)</script>']);

        $this->book($this->longTermPayload())->assertOk();

        $html = BookingContractModel::firstOrFail()->terms_html;
        $this->assertStringContainsString('Agreement for Client User with Contract Studio', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '0917'.random_int(1000000, 9999999),
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
            $table->softDeletes();
            $table->timestamps();
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
            $table->text('contract_template')->nullable();
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
            $table->json('materials')->nullable();
            $table->decimal('enhanced_photos_price', 10, 2)->nullable();
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
            $table->date('event_end_date')->nullable();
            $table->string('booking_duration_type')->default('short_term');
            $table->timestamp('terms_accepted_at')->nullable();
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
            $table->json('materials')->nullable();
            $table->string('photo_edit_option', 20)->nullable();
            $table->decimal('enhancement_amount', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('tbl_booking_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->longText('terms_html');
            $table->string('studio_signature')->nullable();
            $table->string('studio_signed_by')->nullable();
            $table->unsignedBigInteger('studio_signed_user_id')->nullable();
            $table->timestamp('studio_signed_at')->nullable();
            $table->string('client_signature')->nullable();
            $table->string('client_signed_by')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->string('status', 20)->default('pending');
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
