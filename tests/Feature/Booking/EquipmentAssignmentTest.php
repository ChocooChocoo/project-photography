<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\StudioOwner\BookingEquipmentController as OwnerBookingEquipmentController;
use App\Http\Controllers\StudioPhotographer\AssignedBookingController;
use App\Http\Controllers\StudioPhotographer\BookingEquipmentController as PhotographerBookingEquipmentController;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\BookingEquipmentModel;
use App\Models\StudioOwner\StudioEquipmentModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EquipmentAssignmentTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'equipment-owner@example.com');
        Auth::setUser($this->owner);

        Route::get('/_test/owner/bookings/{bookingId}/equipment', [OwnerBookingEquipmentController::class, 'index']);
        Route::post('/_test/owner/bookings/{bookingId}/equipment', [OwnerBookingEquipmentController::class, 'store']);
        Route::delete('/_test/owner/equipment/{id}', [OwnerBookingEquipmentController::class, 'destroy']);
        Route::post('/_test/photographer/equipment/{id}/confirm', [PhotographerBookingEquipmentController::class, 'confirm']);
        Route::get('/_test/photographer/assignment/{id}/details', [AssignedBookingController::class, 'getBookingDetails']);
    }

    public function test_owner_can_assign_equipment_to_booking(): void
    {
        $client = $this->createUser('client', 'equipment-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);

        $photographer = $this->createUser('studio-photographer', 'equipment-owner-assigned@example.com');
        $this->createAssignment($booking, $studio, $photographer);

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
                'equipment_id' => $this->createCatalogItem($studio, 'Camera body', 'camera')->id,
            ])->assertCreated()->assertJsonPath('success', true);

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
                'equipment_id' => $this->createCatalogItem($studio, 'Flash', 'lighting')->id,
                'notes' => 'Battery pack included.',
            ])->assertCreated()->assertJsonPath('success', true);

        $this->assertSame(2, BookingEquipmentModel::where('booking_id', $booking->id)->count());
        $this->assertSame(
            ['Camera body', 'Flash'],
            BookingEquipmentModel::where('booking_id', $booking->id)
                ->orderBy('id')
                ->pluck('equipment_name')
                ->all()
        );
        $this->assertFalse(BookingEquipmentModel::where('booking_id', $booking->id)->first()->confirmed);
        $this->assertNull(BookingEquipmentModel::where('booking_id', $booking->id)->first()->confirmed_at);

        $this->actingAs($this->owner)
            ->getJson("/_test/owner/bookings/{$booking->id}/equipment")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_equipment_requires_a_non_cancelled_photographer(): void
    {
        $client = $this->createUser('client', 'equipment-gate-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $payload = ['equipment_id' => $this->createCatalogItem($studio)->id];

        $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", $payload)
            ->assertUnprocessable()->assertJsonPath('success', false);

        $photographer = $this->createUser('studio-photographer', 'equipment-gate-photo@example.com');
        $assignment = $this->createAssignment($booking, $studio, $photographer);
        $assignment->update(['status' => 'cancelled']);
        $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", $payload)
            ->assertUnprocessable();
        $this->assertDatabaseCount('tbl_booking_equipment', 0);
    }

    public function test_equipment_assignment_must_belong_to_the_same_booking(): void
    {
        $client = $this->createUser('client', 'equipment-scope-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'equipment-scope-photo@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $this->createAssignment($booking, $studio, $photographer);
        $otherBooking = $this->createBooking($studio, $client);
        $otherAssignment = $this->createAssignment($otherBooking, $studio, $photographer);

        $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
            'assignment_id' => $otherAssignment->id,
            'equipment_id' => $this->createCatalogItem($studio)->id,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('tbl_booking_equipment', 0);
    }

    public function test_invalid_equipment_returns_validation_errors(): void
    {
        $client = $this->createUser('client', 'equipment-invalid-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $photographer = $this->createUser('studio-photographer', 'equipment-invalid-photo@example.com');
        $this->createAssignment($booking, $studio, $photographer);
        $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", [])
            ->assertUnprocessable()->assertJsonValidationErrors(['equipment_id']);

        // Free text is no longer accepted; equipment comes from the studio catalog.
        $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
            'equipment_name' => 'Camera body', 'equipment_type' => 'camera', 'brand' => 'Canon',
        ])->assertUnprocessable()->assertJsonValidationErrors(['equipment_id']);
        $this->assertDatabaseCount('tbl_booking_equipment', 0);
    }

    public function test_terminal_bookings_cannot_receive_equipment(): void
    {
        $client = $this->createUser('client', 'equipment-terminal-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $photographer = $this->createUser('studio-photographer', 'equipment-terminal-photo@example.com');
        $this->createAssignment($booking, $studio, $photographer);
        foreach (['completed', 'cancelled'] as $status) {
            $booking->update(['status' => $status]);
            $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
                'equipment_id' => $this->createCatalogItem($studio)->id,
            ])->assertUnprocessable();
        }
        $this->assertDatabaseCount('tbl_booking_equipment', 0);
    }

    public function test_owner_cannot_assign_equipment_to_another_owners_booking(): void
    {
        $client = $this->createUser('client', 'equipment-other-client@example.com');
        $otherOwner = $this->createUser('owner', 'equipment-other-owner@example.com');
        $studio = $this->createStudio();
        $studio->update(['user_id' => $otherOwner->id]);
        $booking = $this->createBooking($studio, $client);
        $this->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
            'equipment_id' => $this->createCatalogItem($studio)->id,
        ])->assertNotFound();
        $this->getJson("/_test/owner/bookings/{$booking->id}/equipment")->assertNotFound();
        $equipment = $this->createEquipment($booking);
        $this->deleteJson("/_test/owner/equipment/{$equipment->id}")->assertNotFound();
        $this->assertDatabaseHas('tbl_booking_equipment', ['id' => $equipment->id]);
    }

    public function test_photographer_can_confirm_assigned_equipment(): void
    {
        $client = $this->createUser('client', 'equipment-confirm-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'equipment-confirm-photographer@example.com');
        $otherPhotographer = $this->createUser('studio-photographer', 'equipment-other-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $assignment = $this->createAssignment($booking, $studio, $photographer);
        $equipment = $this->createEquipment($booking, $assignment);

        $this->actingAs($photographer)
            ->postJson("/_test/photographer/equipment/{$equipment->id}/confirm")
            ->assertOk()
            ->assertJsonPath('success', true);

        $equipment->refresh();
        $this->assertTrue($equipment->confirmed);
        $this->assertNotNull($equipment->confirmed_at);

        $this->actingAs($otherPhotographer)
            ->postJson("/_test/photographer/equipment/{$equipment->id}/confirm")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertTrue($equipment->fresh()->confirmed);
    }

    public function test_photographer_cannot_confirm_equipment_on_a_cancelled_booking(): void
    {
        $client = $this->createUser('client', 'equipment-cancelled-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'equipment-cancelled-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $assignment = $this->createAssignment($booking, $studio, $photographer);
        $equipment = $this->createEquipment($booking, $assignment);
        $booking->update(['status' => 'cancelled']);

        $this->actingAs($photographer)
            ->postJson("/_test/photographer/equipment/{$equipment->id}/confirm")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertFalse($equipment->fresh()->confirmed);
    }

    public function test_brand_mismatch_is_flagged_without_blocking(): void
    {
        $client = $this->createUser('client', 'equipment-brand-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $booking->update(['preferred_camera_brand' => 'Canon']);
        $this->createAssignment($booking, $studio, $this->createUser('studio-photographer', 'equipment-brand-photo@example.com'));

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
                'equipment_id' => $this->createCatalogItem($studio, 'Camera MARK VI', 'DSLR', 'NIKON')->id,
            ])->assertCreated()->assertJsonPath('data.brand_mismatch', true);

        $same = BookingEquipmentModel::create(['booking_id' => $booking->id, 'equipment_name' => 'Body', 'equipment_type' => 'camera', 'brand' => ' canon ']);
        $unbranded = BookingEquipmentModel::create(['booking_id' => $booking->id, 'equipment_name' => 'Tripod', 'equipment_type' => 'support']);
        $this->assertFalse($same->brand_mismatch);
        $this->assertFalse($unbranded->brand_mismatch);

        $booking->update(['preferred_camera_brand' => null]);
        $this->assertFalse($same->fresh()->brand_mismatch);
    }

    public function test_photographer_modal_lists_equipment_and_calls_the_confirm_route(): void
    {
        $blade = file_get_contents(resource_path('views/studio-photographer/view-assigned-booking.blade.php'));

        $this->assertStringContainsString("route('assignment.equipment.confirm', '__ID__')", $blade);
        $this->assertStringContainsString('data.equipment', $blade);
        $this->assertStringContainsString('Confirm equipment', $blade);
    }

    public function test_owner_can_remove_equipment(): void
    {
        $client = $this->createUser('client', 'equipment-remove-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $equipment = $this->createEquipment($booking);

        $this->actingAs($this->owner)
            ->deleteJson("/_test/owner/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tbl_booking_equipment', ['id' => $equipment->id]);
    }

    public function test_assignment_details_include_equipment(): void
    {
        $client = $this->createUser('client', 'equipment-details-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'equipment-details-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client);
        $assignment = $this->createAssignment($booking, $studio, $photographer);
        $this->createEquipment($booking, $assignment, 'Camera body', 'camera');
        $this->createEquipment($booking, $assignment, 'Flash', 'lighting');

        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-EQUIPMENT-DETAILS',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        $this->actingAs($photographer)
            ->getJson("/_test/photographer/assignment/{$assignment->id}/details")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'equipment')
            ->assertJsonPath('equipment.0.equipment_name', 'Camera body')
            ->assertJsonPath('equipment.0.confirmed', false);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Equipment',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000006',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Equipment Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(StudiosModel $studio, UserModel $client): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Equipment Test Event',
            'event_date' => '2026-08-10',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
    }

    private function createAssignment(BookingModel $booking, StudiosModel $studio, UserModel $photographer): BookingAssignedPhotographerModel
    {
        return BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'photographer_id' => $photographer->id,
            'assigned_by' => $this->owner->id,
            'status' => 'confirmed',
            'assigned_at' => now(),
            'response_deadline' => now()->addDay(),
        ]);
    }

    private function createCatalogItem(StudiosModel $studio, string $name = 'Camera body', string $type = 'camera', ?string $brand = null): StudioEquipmentModel
    {
        return StudioEquipmentModel::create([
            'studio_id' => $studio->id,
            'name' => $name,
            'type' => $type,
            'brand' => $brand,
            'status' => 'available',
        ]);
    }

    private function createEquipment(BookingModel $booking, ?BookingAssignedPhotographerModel $assignment = null, string $name = 'Camera body', string $type = 'camera'): BookingEquipmentModel
    {
        return BookingEquipmentModel::create([
            'booking_id' => $booking->id,
            'assignment_id' => $assignment?->id,
            'equipment_name' => $name,
            'equipment_type' => $type,
            'confirmed' => false,
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
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name');
            $table->date('event_date');
            $table->date('event_end_date')->nullable();
            $table->string('booking_duration_type')->default('short_term');
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('location_type')->default('in-studio');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->text('cancellation_reason')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('studio_id');
            $table->foreignId('photographer_id');
            $table->foreignId('assigned_by');
            $table->string('status')->default('assigned');
            $table->text('assignment_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('response_deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('on_site_at')->nullable();
            $table->timestamp('client_confirmed_at')->nullable();
            $table->text('client_confirmation_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_booking_equipment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('equipment_name');
            $table->string('equipment_type');
            $table->text('notes')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
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
        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_type');
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->string('package_name');
            $table->decimal('package_price', 10, 2);
            $table->boolean('online_gallery')->default(false);
            $table->integer('photographer_count')->default(0);
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('studio_id');
            $table->json('images')->nullable();
            $table->integer('total_photos')->default(0);
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_09_100000_add_booking_preparation.php'))->up();
    }
}
