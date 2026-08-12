<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\StudioOwner\BookingEquipmentController as OwnerBookingEquipmentController;
use App\Http\Controllers\StudioPhotographer\AssignedBookingController;
use App\Http\Controllers\StudioPhotographer\BookingEquipmentController as PhotographerBookingEquipmentController;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\BookingEquipmentModel;
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

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
                'equipment_name' => 'Camera body',
                'equipment_type' => 'camera',
            ])->assertCreated()->assertJsonPath('success', true);

        $this->actingAs($this->owner)
            ->postJson("/_test/owner/bookings/{$booking->id}/equipment", [
                'equipment_name' => 'Flash',
                'equipment_type' => 'lighting',
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
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name');
            $table->date('event_date');
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
            $table->timestamps();
        });
        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('studio_id');
            $table->json('images')->nullable();
            $table->integer('total_photos')->default(0);
            $table->timestamps();
        });
    }
}
