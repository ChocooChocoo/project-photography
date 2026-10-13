<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\HardcopyOrderController;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HardcopyOrderTest extends TestCase
{
    private $owner;

    private $client;

    private $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new EquipmentAssignmentTest('test_owner_can_assign_equipment_to_booking');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        (require database_path('migrations/2026_10_09_150000_add_hardcopy_orders.php'))->up();
        (require database_path('migrations/2026_02_22_145351_create_tbl_notifications_table.php'))->up();
        Schema::table('tbl_payments', function ($table) {
            $table->string('stripe_session_id')->nullable();
            $table->timestamp('paid_at')->nullable();
        });
        $this->owner = (new \ReflectionMethod($fixture, 'createUser'))->invoke($fixture, 'owner', 'hardcopy-owner@example.com');
        $this->client = (new \ReflectionMethod($fixture, 'createUser'))->invoke($fixture, 'client', 'hardcopy-client@example.com');
        $studio = DB::table('tbl_studios')->insertGetId(['user_id' => $this->owner->id, 'studio_name' => 'Hardcopy Studio', 'status' => 'verified']);
        $this->booking = BookingModel::create(['booking_reference' => 'HC-TEST', 'client_id' => $this->client->id, 'booking_type' => 'studio', 'provider_id' => $studio, 'event_name' => 'Hardcopy event', 'event_date' => '2026-10-20', 'total_amount' => 1000, 'down_payment' => 1000, 'payment_type' => 'full_payment', 'status' => 'in_progress', 'payment_status' => 'paid']);
        PaymentModel::create(['booking_id' => $this->booking->id, 'payment_reference' => 'HC-ORIGINAL', 'amount' => 1000, 'payment_method' => 'card', 'status' => 'succeeded']);
        Route::post('/_hc/{bookingId}', [HardcopyOrderController::class, 'store']);
        Route::post('/_hc/{bookingId}/accept/{order}', [HardcopyOrderController::class, 'accept']);
        Route::post('/_hc/{bookingId}/status/{order}', [HardcopyOrderController::class, 'status']);
        Route::get('/_hc/{bookingId}/owner', [HardcopyOrderController::class, 'ownerShow']);
        Route::get('/_hc/{bookingId}/client', [HardcopyOrderController::class, 'clientShow']);
    }

    private function quote(): int
    {
        $this->actingAs($this->owner)->postJson('/_hc/'.$this->booking->id, ['fulfillment_method' => 'pickup', 'items' => [['name' => 'Frame', 'size' => '8x10', 'quantity' => 2, 'unit_price' => 150]]])->assertCreated()->assertJsonPath('order.amount', '300.00');

        return DB::table('tbl_booking_hardcopy_orders')->latest('id')->value('id');
    }

    public function test_booking_panels_offer_quote_and_acceptance_controls(): void
    {
        $this->actingAs($this->owner)->get('/_hc/'.$this->booking->id.'/owner')->assertOk()->assertSee('Create hardcopy quote');
        $this->quote();
        $this->actingAs($this->client)->get('/_hc/'.$this->booking->id.'/client')->assertOk()->assertSee('Accept quote')->assertSee('Frame');
        $this->get('/_hc/'.$this->booking->id.'/owner')->assertNotFound();
    }

    public function test_fulfillment_is_required_and_client_cannot_create_quote(): void
    {
        $this->actingAs($this->owner)->postJson('/_hc/'.$this->booking->id, ['items' => [['name' => 'Print', 'quantity' => 1, 'unit_price' => 0]]])->assertUnprocessable();
        $this->actingAs($this->client)->postJson('/_hc/'.$this->booking->id, ['fulfillment_method' => 'pickup', 'items' => [['name' => 'Print', 'quantity' => 1, 'unit_price' => 0]]])->assertNotFound();
    }

    public function test_acceptance_charges_once_and_courier_needs_address(): void
    {
        $order = $this->quote();
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'courier'])->assertUnprocessable();
        $this->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'courier', 'delivery_address' => '123 Example Road', 'contact_number' => '09123456789'])->assertOk();
        $this->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'courier', 'delivery_address' => '123 Example Road', 'contact_number' => '09123456789'])->assertOk();
        $this->assertEquals(1300, $this->booking->fresh()->total_amount);
        $this->assertDatabaseCount('tbl_payments', 2);
        $this->assertDatabaseHas('tbl_payments', ['booking_id' => $this->booking->id, 'status' => 'pending', 'amount' => 300]);
    }

    public function test_fulfillment_transitions_require_payment_and_tracking_and_notify_once(): void
    {
        $order = $this->quote();
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'courier', 'delivery_address' => '123 Example Road', 'contact_number' => '09123456789'])->assertOk();
        $this->actingAs($this->owner)->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'preparing'])->assertUnprocessable();
        PaymentModel::where('status', 'pending')->first()->markAsPaid();
        foreach (['preparing', 'ready'] as $status) {
            $this->postJson("/_hc/{$this->booking->id}/status/{$order}", compact('status'))->assertOk();
        }
        $this->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'delivered'])->assertUnprocessable();
        $this->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'shipped'])->assertUnprocessable();
        $this->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'shipped', 'courier_name' => 'LBC', 'tracking_number' => 'TEST123'])->assertOk();
        $this->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'delivered'])->assertOk();
        $this->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'delivered'])->assertOk();
        $this->assertDatabaseCount('tbl_notifications', 3);
        $this->assertDatabaseHas('tbl_booking_events', ['type' => 'hardcopy_delivered']);
    }

    public function test_other_client_cannot_accept_order_and_active_checkout_blocks_charge(): void
    {
        $order = $this->quote();
        $this->actingAs($this->owner)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'pickup'])->assertNotFound();
        PaymentModel::create(['booking_id' => $this->booking->id, 'payment_reference' => 'HC-CHECKOUT', 'amount' => 10, 'payment_method' => 'card', 'status' => 'pending', 'stripe_session_id' => 'cs_test_active']);
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'pickup'])->assertStatus(409);
        $this->assertEquals(1000, $this->booking->fresh()->total_amount);
    }

    public function test_pickup_order_can_be_delivered_without_courier_tracking(): void
    {
        $order = $this->quote();
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'pickup'])->assertOk();
        PaymentModel::where('status', 'pending')->first()->markAsPaid();
        foreach (['preparing', 'ready', 'delivered'] as $status) {
            $this->actingAs($this->owner)->postJson("/_hc/{$this->booking->id}/status/{$order}", compact('status'))->assertOk();
        }
        $this->assertDatabaseHas('tbl_booking_hardcopy_orders', ['id' => $order, 'status' => 'delivered', 'tracking_number' => null]);
        $this->assertDatabaseCount('tbl_notifications', 2);
    }

    public function test_only_unaccepted_quotes_can_be_cancelled_without_refund(): void
    {
        $order = $this->quote();
        $this->postJson("/_hc/{$this->booking->id}/status/{$order}", ['status' => 'cancelled'])->assertOk();
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'pickup'])->assertUnprocessable();
        $this->assertEquals(1000, $this->booking->fresh()->total_amount);
        $accepted = $this->quote();
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$accepted}", ['fulfillment_method' => 'pickup'])->assertOk();
        $this->actingAs($this->owner)->postJson("/_hc/{$this->booking->id}/status/{$accepted}", ['status' => 'cancelled'])->assertUnprocessable();
    }

    public function test_accepted_hardcopy_balance_uses_existing_checkout(): void
    {
        Schema::table('tbl_payments', fn ($table) => $table->json('payment_details')->nullable());
        $stripe = \Mockery::mock(\App\Services\StripeService::class);
        $stripe->shouldReceive('createCheckoutSession')->once()
            ->with('300.00', 'HC-TEST', 'PHP', 'Balance Payment - HC-TEST')
            ->andReturn(['id' => 'cs_hardcopy_test', 'url' => 'https://checkout.stripe.com/test']);
        $this->app->instance(\App\Services\StripeService::class, $stripe);
        $this->app->instance(\App\Services\PaymongoService::class, \Mockery::mock(\App\Services\PaymongoService::class));
        Route::post('/_hardcopy_checkout', [\App\Http\Controllers\Client\BookingController::class, 'initializePayment']);
        $order = $this->quote();
        $this->actingAs($this->client)->postJson("/_hc/{$this->booking->id}/accept/{$order}", ['fulfillment_method' => 'pickup'])->assertOk();
        $this->postJson('/_hardcopy_checkout', ['booking_id' => $this->booking->id])->assertOk()
            ->assertJsonPath('is_balance_payment', true)->assertJsonPath('session_id', 'cs_hardcopy_test');
        $this->assertDatabaseHas('tbl_payments', ['booking_id' => $this->booking->id, 'amount' => 300, 'stripe_session_id' => 'cs_hardcopy_test']);
    }
}
