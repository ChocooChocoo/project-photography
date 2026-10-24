<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\BookingController;
use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\StudioManagement\DepositPolicyTest;
use Tests\TestCase;

/**
 * T02 enhancement 2: clients choose unedited (RAW) photos or one or more
 * editing services, and the price updates.
 */
class PhotoEditingPricingTest extends TestCase
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
        $this->studio = $this->call_('createStudio', ['requires_downpayment' => true, 'downpayment_percentage' => 30]);
        $this->payload = $this->call_('bookingPayload', $this->studio);
        StudioPackagesModel::whereKey($this->payload['package_id'])->update([
            'editing_prices' => json_encode(['color_grading' => 500, 'retouching' => 1200]),
        ]);

        Route::post('/_test/client/booking', [BookingController::class, 'store']);
        Route::post('/_test/client/booking-summary', [BookingController::class, 'getSummary']);
        Route::get('/_test/owner/bookings', fn () => 'ok')->name('owner.booking.index');
    }

    private function call_(string $method, ...$args)
    {
        return (new \ReflectionMethod($this->fixture, $method))->invoke($this->fixture, ...$args);
    }

    private function summary(array $extra = [])
    {
        return $this->actingAs($this->client)->postJson('/_test/client/booking-summary', $extra + [
            'package_id' => $this->payload['package_id'], 'type' => 'studio', 'payment_type' => 'full_payment',
        ]);
    }

    public function test_package_lists_only_the_services_it_prices(): void
    {
        $this->assertSame([
            'color_grading' => ['label' => 'Color grading', 'price' => 500.0],
            'retouching' => ['label' => 'Retouching', 'price' => 1200.0],
        ], StudioPackagesModel::find($this->payload['package_id'])->editingOptions());
    }

    public function test_raw_photos_keep_the_package_price(): void
    {
        $this->summary()
            ->assertOk()
            ->assertJsonPath('summary.photo_edit_option', 'raw')
            ->assertJsonPath('summary.editing_services', [])
            ->assertJsonPath('summary.enhancement_amount', '0.00')
            ->assertJsonPath('summary.total_amount', '10,000.00');
    }

    public function test_selected_services_add_their_prices_to_summary_total_and_snapshot(): void
    {
        $this->summary(['editing_services' => ['color_grading', 'retouching']])
            ->assertOk()
            ->assertJsonPath('summary.photo_edit_option', 'enhanced')
            ->assertJsonPath('summary.editing_services', ['Color grading', 'Retouching'])
            ->assertJsonPath('summary.enhancement_amount', '1,700.00')
            ->assertJsonPath('summary.total_amount', '11,700.00');

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['payment_type' => 'full_payment', 'editing_services' => ['retouching', 'color_grading']] + $this->payload)
            ->assertOk();

        $booking = BookingModel::latest('id')->first();
        $snapshot = BookingPackageModel::where('booking_id', $booking->id)->first();
        $this->assertEquals(11700, $booking->total_amount);
        $this->assertEquals(11700, PaymentModel::latest('id')->first()->amount);
        $this->assertSame('enhanced', $snapshot->photo_edit_option);
        $this->assertSame('1700.00', $snapshot->enhancement_amount);
        $this->assertEquals(['color_grading' => 500, 'retouching' => 1200], $snapshot->editing_services);
        $this->assertSame(['color_grading', 'retouching'], array_keys($snapshot->editing_services));
        $this->assertSame('Color grading, Retouching', $snapshot->editing_summary);
        // MySQL JSON may return keys in another order; the label keeps the catalog order.
        $this->assertSame('Color grading, Retouching', (new BookingPackageModel(['editing_services' => ['retouching' => 1200, 'color_grading' => 500]]))->editing_summary);
        $this->assertSame('Unedited (RAW) photos', (new BookingPackageModel(['photo_edit_option' => 'raw']))->editing_summary);

        // Later price changes do not touch what the client booked.
        StudioPackagesModel::whereKey($this->payload['package_id'])->update(['editing_prices' => json_encode(['color_grading' => 900])]);
        $this->assertEquals(['color_grading' => 500, 'retouching' => 1200], $snapshot->fresh()->editing_services);
    }

    public function test_downpayment_is_taken_from_the_edited_total(): void
    {
        $this->summary(['payment_type' => 'downpayment', 'editing_services' => ['color_grading', 'retouching']])
            ->assertOk()
            ->assertJsonPath('summary.down_payment', '3,510.00')
            ->assertJsonPath('summary.remaining_balance', '8,190.00');
    }

    public function test_a_service_the_package_does_not_offer_is_refused(): void
    {
        $this->summary(['editing_services' => ['brightness_contrast']])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This package does not offer: Brightness and contrast.');

        $this->actingAs($this->client)
            ->postJson('/_test/client/booking', ['editing_services' => ['color_grading', 'brightness_contrast']] + $this->payload)
            ->assertStatus(422);
        $this->assertSame(0, BookingModel::count());

        $this->summary(['editing_services' => ['face_swap']])->assertStatus(422)->assertJsonValidationErrors('editing_services.0');
    }

    public function test_old_enhanced_choice_still_works_for_packages_with_a_single_enhanced_price(): void
    {
        StudioPackagesModel::whereKey($this->payload['package_id'])->update(['editing_prices' => null, 'enhanced_photos_price' => 750]);

        $this->summary(['photo_edit_option' => 'enhanced'])
            ->assertOk()
            ->assertJsonPath('summary.editing_services', ['Enhanced photos'])
            ->assertJsonPath('summary.total_amount', '10,750.00');
    }
}
