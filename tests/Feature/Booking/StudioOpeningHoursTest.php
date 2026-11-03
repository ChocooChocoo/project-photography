<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\BookingController;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\StudioManagement\DepositPolicyTest;
use Tests\TestCase;

/**
 * Client test C-12: an in-studio booking at 07:00 or ending at 19:00 was
 * accepted although the studio is open 08:00 to 18:00. On-location bookings
 * (for example an evening wedding) are not limited by the studio's hours.
 */
class StudioOpeningHoursTest extends TestCase
{
    private DepositPolicyTest $fixture;

    private $client;

    private StudiosModel $studio;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $this->fixture = new DepositPolicyTest('test_client_can_choose_the_studios_percentage_downpayment');
        $this->call_('createSchema');
        $owner = $this->call_('createUser', 'owner');
        (new \ReflectionProperty($this->fixture, 'owner'))->setValue($this->fixture, $owner);
        $this->client = $this->call_('createUser', 'client');
        $this->studio = $this->call_('createStudio', ['requires_downpayment' => false]);
        $this->payload = $this->call_('bookingPayload', $this->studio);

        Route::post('/_test/client/booking', [BookingController::class, 'store']);
        Route::post('/_test/client/check', [BookingController::class, 'checkAvailability']);
        Route::get('/_test/owner/bookings', fn () => 'ok')->name('owner.booking.index');
    }

    private function call_(string $method, ...$args)
    {
        return (new \ReflectionMethod($this->fixture, $method))->invoke($this->fixture, ...$args);
    }

    public function test_in_studio_bookings_must_fit_the_opening_hours(): void
    {
        foreach ([['07:00', '09:00'], ['16:00', '19:00']] as [$start, $end]) {
            $this->actingAs($this->client)
                ->postJson('/_test/client/booking', ['start_time' => $start, 'end_time' => $end, 'payment_type' => 'full_payment'] + $this->payload)
                ->assertStatus(422)
                ->assertJsonPath('message', 'The studio is open 8:00 AM to 6:00 PM. Choose a time within those hours, or choose On-Location.');

            $this->actingAs($this->client)
                ->postJson('/_test/client/check', [
                    'type' => 'studio', 'provider_id' => $this->studio->id, 'date' => $this->payload['event_date'],
                    'start_time' => $start, 'end_time' => $end, 'location_type' => 'in-studio',
                ])
                ->assertOk()
                ->assertJsonPath('available', false)
                ->assertJsonPath('message', 'The studio is open 8:00 AM to 6:00 PM. Choose a time within those hours, or choose On-Location.');
        }
        $this->assertSame(0, BookingModel::count());

        // Exactly the opening hours is fine.
        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['start_time' => '08:00', 'end_time' => '18:00', 'payment_type' => 'full_payment'] + $this->payload)
            ->assertOk();
    }

    public function test_the_check_without_a_location_yet_does_not_refuse_early_or_late_times(): void
    {
        $this->actingAs($this->client)
            ->postJson('/_test/client/check', [
                'type' => 'studio', 'provider_id' => $this->studio->id, 'date' => $this->payload['event_date'],
                'start_time' => '19:00', 'end_time' => '22:00',
            ])
            ->assertOk()
            ->assertJsonPath('available', true);
    }

    public function test_an_on_location_booking_needs_a_venue_pin(): void
    {
        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', [
                'location_type' => 'on-location', 'city' => 'Imus', 'barangay' => 'Alapan I-A', 'payment_type' => 'full_payment',
            ] + $this->payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['venue_latitude', 'venue_longitude'])
            ->assertJsonPath('errors.venue_latitude.0', 'Pin the venue on the map.');
        $this->assertSame(0, BookingModel::count());
    }

    public function test_a_fixed_package_sets_its_own_end_time(): void
    {
        // The browser sends 10:00, but the 4-hour package ends at 13:00.
        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['start_time' => '09:00', 'end_time' => '10:00', 'payment_type' => 'full_payment'] + $this->payload)
            ->assertOk();
        $this->assertSame('13:00', substr((string) BookingModel::first()->end_time, 0, 5));
    }

    public function test_a_fixed_package_that_would_pass_midnight_is_refused(): void
    {
        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['start_time' => '21:00', 'end_time' => '23:00', 'payment_type' => 'full_payment'] + $this->payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'This 4-hour package must end by midnight. Choose an earlier start time.');
        $this->assertSame(0, BookingModel::count());
    }

    public function test_a_flexible_in_studio_session_may_run_past_closing(): void
    {
        \App\Models\StudioOwner\PackagesModel::whereKey($this->payload['package_id'])->update(['allow_time_customization' => true]);

        $this->actingAs($this->client)
            ->postJson('/_test/client/check', [
                'type' => 'studio', 'provider_id' => $this->studio->id, 'package_id' => $this->payload['package_id'],
                'date' => $this->payload['event_date'], 'start_time' => '16:00', 'end_time' => '22:00', 'location_type' => 'in-studio',
            ])
            ->assertOk()
            ->assertJsonPath('available', true);

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['start_time' => '16:00', 'end_time' => '22:00', 'payment_type' => 'full_payment'] + $this->payload)
            ->assertOk();
        $this->assertSame('22:00', substr((string) BookingModel::first()->end_time, 0, 5));

        // The start still has to be within opening hours.
        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['start_time' => '18:30', 'end_time' => '20:00', 'payment_type' => 'full_payment'] + $this->payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'The studio is open 8:00 AM to 6:00 PM. Start within those hours; the session may run past closing until midnight.');
    }
}
