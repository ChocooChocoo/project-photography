<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\BookingController;
use App\Models\BookingModel;
use App\Models\UserModel;
use App\Services\StripeService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\StudioManagement\DepositPolicyTest;
use Tests\TestCase;

/**
 * Client test C-14: a client who left checkout (pressed Cancel at "Confirm
 * Payment", closed the Stripe tab or had a failed card) had no way to pay for
 * the booking from My Bookings. Starting a payment must also be limited to the
 * booking's own client.
 */
class ResumeBookingPaymentTest extends TestCase
{
    private DepositPolicyTest $fixture;

    private $client;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $this->fixture = new DepositPolicyTest('test_client_can_choose_the_studios_percentage_downpayment');
        $this->call_('createSchema');
        foreach (['stripe_session_id' => 'string', 'payment_details' => 'json'] as $column => $type) {
            if (! Schema::hasColumn('tbl_payments', $column)) {
                Schema::table('tbl_payments', fn ($table) => $table->{$type}($column)->nullable());
            }
        }
        $owner = $this->call_('createUser', 'owner');
        (new \ReflectionProperty($this->fixture, 'owner'))->setValue($this->fixture, $owner);
        $this->client = $this->call_('createUser', 'client');
        $studio = $this->call_('createStudio', ['requires_downpayment' => false]);
        $this->payload = $this->call_('bookingPayload', $studio);

        $stripe = $this->createMock(StripeService::class);
        $stripe->method('createCheckoutSession')->willReturn(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.test/cs_test_1']);
        $this->app->instance(StripeService::class, $stripe);

        Route::post('/_test/client/booking', [BookingController::class, 'store']);
        Route::post('/_test/client/pay', [BookingController::class, 'initializePayment']);
        Route::get('/_test/owner/bookings', fn () => 'ok')->name('owner.booking.index');
    }

    private function call_(string $method, ...$args)
    {
        return (new \ReflectionMethod($this->fixture, $method))->invoke($this->fixture, ...$args);
    }

    public function test_the_client_can_restart_payment_for_an_unpaid_booking_and_nobody_else_can(): void
    {
        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['payment_type' => 'full_payment'] + $this->payload)
            ->assertOk();
        $booking = BookingModel::firstOrFail();

        $stranger = UserModel::create([
            'role' => 'client', 'user_type' => 'customer', 'first_name' => 'Other', 'last_name' => 'Client',
            'email' => 'other-client@example.com', 'mobile_number' => '09170000099', 'password' => 'secret',
            'status' => 'active', 'email_verified' => true,
        ]);
        $this->actingAs($stranger)->postJson('/_test/client/pay', ['booking_id' => $booking->id])->assertNotFound();

        $this->actingAs($this->client)
            ->postJson('/_test/client/pay', ['booking_id' => $booking->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', 'https://checkout.stripe.test/cs_test_1');

        $booking->update(['status' => BookingModel::STATUS_CANCELLED]);
        $this->actingAs($this->client)
            ->postJson('/_test/client/pay', ['booking_id' => $booking->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This booking was cancelled, so it can no longer be paid.');
    }

    public function test_my_bookings_offers_complete_payment_for_an_unpaid_booking(): void
    {
        $blade = file_get_contents(resource_path('views/client/view-my-bookings.blade.php'));

        $this->assertStringContainsString('pay-now-btn', $blade);
        $this->assertStringContainsString('Complete payment', $blade);
    }
}
