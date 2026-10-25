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
}
