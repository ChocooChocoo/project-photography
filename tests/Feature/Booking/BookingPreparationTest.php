<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\StudioOwner\BookingEquipmentController;
use App\Http\Controllers\StudioOwner\StudioEquipmentController;
use App\Http\Controllers\Client\BookingPreparationController;
use App\Http\Requests\StudioPhotographer\UpdateAssignmentStatusRequest;
use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudioEquipmentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BookingPreparationTest extends TestCase
{
    private $owner;
    private $client;
    private $studio;
    private $booking;
    private $photographer;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new EquipmentAssignmentTest('test_owner_can_assign_equipment_to_booking');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::table('tbl_bookings', function ($table) {
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });
        $this->owner = (new \ReflectionMethod($fixture, 'createUser'))->invoke($fixture, 'owner', 'prep-owner@example.com');
        $this->client = (new \ReflectionMethod($fixture, 'createUser'))->invoke($fixture, 'client', 'prep-client@example.com');
        $this->photographer = (new \ReflectionMethod($fixture, 'createUser'))->invoke($fixture, 'studio-photographer', 'prep-photo@example.com');
        $studioId = DB::table('tbl_studios')->insertGetId(['user_id' => $this->owner->id, 'studio_name' => 'Preparation Studio', 'status' => 'verified']);
        $this->studio = \App\Models\StudioOwner\StudiosModel::find($studioId);
        $this->booking = $this->booking('2026-11-01', '09:00', '12:00');
        Auth::setUser($this->owner);
        Route::post('/_prep/bookings/{bookingId}/equipment', [BookingEquipmentController::class, 'store']);
        Route::get('/_prep/studios/{studioId}/equipment', [StudioEquipmentController::class, 'index']);
        Route::post('/_prep/studios/{studioId}/equipment', [StudioEquipmentController::class, 'store']);
        Route::put('/_prep/catalog/{id}', [StudioEquipmentController::class, 'update']);
        Route::delete('/_prep/catalog/{id}', [StudioEquipmentController::class, 'destroy']);
        Route::get('/_prep/client/{booking}', [BookingPreparationController::class, 'show']);
    }

    private function booking(string $date, string $start, string $end): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'PREP-'.str()->random(10), 'client_id' => $this->client->id,
            'booking_type' => 'studio', 'provider_id' => $this->studio->id, 'event_name' => 'Preparation test',
            'event_date' => $date, 'start_time' => $start, 'end_time' => $end,
            'total_amount' => 1000, 'down_payment' => 0, 'payment_type' => 'full_payment',
            'status' => 'confirmed', 'payment_status' => 'pending',
        ]);
    }

    private function assign(BookingModel $booking): void
    {
        BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id, 'studio_id' => $this->studio->id,
            'photographer_id' => $this->photographer->id, 'assigned_by' => $this->owner->id,
            'status' => 'assigned',
        ]);
    }

    public function test_catalog_crud_preserves_assignment_snapshot(): void
    {
        $this->postJson("/_prep/studios/{$this->studio->id}/equipment", [
            'name' => 'Camera body', 'type' => 'camera', 'brand' => 'Canon', 'model' => 'R6', 'status' => 'available',
        ])->assertCreated();
        $item = StudioEquipmentModel::firstOrFail();
        $this->assign($this->booking);
        $this->postJson("/_prep/bookings/{$this->booking->id}/equipment", ['equipment_id' => $item->id])
            ->assertCreated()->assertJsonPath('data.brand', 'Canon');
        $this->putJson("/_prep/catalog/{$item->id}", ['name' => 'Updated', 'type' => 'camera', 'brand' => 'Nikon', 'status' => 'available'])
            ->assertOk();
        $this->assertDatabaseHas('tbl_booking_equipment', ['equipment_name' => 'Camera body R6', 'brand' => 'Canon']);
        $this->deleteJson("/_prep/catalog/{$item->id}")->assertStatus(409);
    }

    public function test_catalog_conflicts_cover_multiple_days_but_allow_adjacent_slots(): void
    {
        $item = StudioEquipmentModel::create(['studio_id' => $this->studio->id, 'name' => 'Camera', 'type' => 'camera', 'status' => 'available']);
        $this->booking->update(['event_end_date' => '2026-11-03']);
        $this->assign($this->booking);
        $this->postJson("/_prep/bookings/{$this->booking->id}/equipment", ['equipment_id' => $item->id])->assertCreated();
        $overlap = $this->booking('2026-11-02', '11:00', '14:00');
        $this->assign($overlap);
        $this->postJson("/_prep/bookings/{$overlap->id}/equipment", ['equipment_id' => $item->id])->assertStatus(409);
        $adjacent = $this->booking('2026-11-02', '12:00', '14:00');
        $this->assign($adjacent);
        $this->postJson("/_prep/bookings/{$adjacent->id}/equipment", ['equipment_id' => $item->id])->assertCreated();
    }

    public function test_timeline_records_changes_once_and_rolls_back(): void
    {
        $this->booking->update(['payment_status' => 'paid']);
        $this->booking->save();
        $this->assign($this->booking);
        $this->assertDatabaseCount('tbl_booking_events', 2);
        DB::beginTransaction();
        $this->booking->update(['status' => 'completed']);
        DB::rollBack();
        $this->assertDatabaseCount('tbl_booking_events', 2);
    }

    public function test_on_site_requires_real_image_and_coordinates_are_optional(): void
    {
        $rules = (new UpdateAssignmentStatusRequest)->rules();
        $validator = Validator::make(['status' => 'on_site'], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('on_site_proof_image', $validator->errors()->toArray());
        $validator = Validator::make(['status' => 'confirmed'], $rules);
        $this->assertFalse($validator->fails());
        $validator = Validator::make(['status' => 'on_site', 'on_site_proof_image' => \Illuminate\Http\UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml'), 'latitude' => 91], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitude', $validator->errors()->toArray());
    }

    public function test_paid_client_sees_timeline_and_other_clients_cannot_access_it(): void
    {
        $this->actingAs($this->client)->get("/_prep/client/{$this->booking->id}")->assertForbidden();
        $this->booking->update(['payment_status' => 'paid', 'preferred_camera_brand' => 'Canon']);
        $this->actingAs($this->client)->get("/_prep/client/{$this->booking->id}")
            ->assertOk()->assertSee('Payment settled')->assertSee('Canon')->assertSee('No equipment assigned yet');
        $this->actingAs($this->photographer)->get("/_prep/client/{$this->booking->id}")->assertNotFound();
    }

    public function test_quiet_payment_settlement_records_one_milestone(): void
    {
        \App\Models\PaymentModel::create([
            'booking_id' => $this->booking->id, 'payment_reference' => 'PREP-PAY',
            'amount' => 1000, 'payment_method' => 'card', 'status' => 'succeeded',
        ]);
        $this->booking->updatePaymentStatus();
        $this->booking->updatePaymentStatus();
        $this->assertDatabaseHas('tbl_booking_events', ['booking_id' => $this->booking->id, 'type' => 'payment_settled']);
        $this->assertDatabaseCount('tbl_booking_events', 1);
    }

    public function test_contract_signatures_and_gallery_publication_are_recorded_once(): void
    {
        Schema::create('tbl_booking_contracts', function ($table) {
            $table->id(); $table->unsignedBigInteger('booking_id');
            $table->timestamp('studio_signed_at')->nullable(); $table->timestamp('client_signed_at')->nullable();
            $table->timestamps();
        });
        $contract = \App\Models\BookingContractModel::create(['booking_id' => $this->booking->id]);
        $contract->update(['studio_signed_at' => now()]);
        $contract->update(['client_signed_at' => now()]);
        $contract->save();
        Schema::table('tbl_studio_online_gallery', fn ($table) => $table->string('gallery_status')->default('draft'));
        $gallery = \App\Models\StudioOwner\StudioOnlineGalleryModel::create(['booking_id' => $this->booking->id, 'studio_id' => $this->studio->id]);
        $gallery->update(['gallery_status' => 'published']);
        $gallery->save();
        $this->assertDatabaseCount('tbl_booking_events', 3);
        $this->assertDatabaseHas('tbl_booking_events', ['type' => 'gallery_published']);
    }

    public function test_cancelled_bookings_release_catalog_reservations_and_duplicate_assignments_are_blocked(): void
    {
        $item = StudioEquipmentModel::create(['studio_id' => $this->studio->id, 'name' => 'Camera', 'type' => 'camera', 'status' => 'available']);
        $this->assign($this->booking);
        $this->postJson("/_prep/bookings/{$this->booking->id}/equipment", ['equipment_id' => $item->id])->assertCreated();
        $this->postJson("/_prep/bookings/{$this->booking->id}/equipment", ['equipment_id' => $item->id])->assertStatus(409);
        $this->booking->update(['status' => 'cancelled']);
        $other = $this->booking('2026-11-01', '09:00', '12:00');
        $this->assign($other);
        $this->postJson("/_prep/bookings/{$other->id}/equipment", ['equipment_id' => $item->id])->assertCreated();
    }

    public function test_other_studio_catalog_items_and_unavailable_items_are_rejected(): void
    {
        $otherStudio = \App\Models\StudioOwner\StudiosModel::create(['user_id' => $this->owner->id, 'studio_name' => 'Other Studio', 'status' => 'verified']);
        $item = StudioEquipmentModel::create(['studio_id' => $otherStudio->id, 'name' => 'Camera', 'type' => 'camera', 'status' => 'available']);
        $this->assign($this->booking);
        $this->postJson("/_prep/bookings/{$this->booking->id}/equipment", ['equipment_id' => $item->id])->assertUnprocessable();
        $item->update(['studio_id' => $this->studio->id, 'status' => 'maintenance']);
        $this->postJson("/_prep/bookings/{$this->booking->id}/equipment", ['equipment_id' => $item->id])->assertUnprocessable();
        $this->assertDatabaseCount('tbl_booking_equipment', 0);
    }

    public function test_migration_rolls_back_only_phase_eight_additions(): void
    {
        (require database_path('migrations/2026_10_09_100000_add_booking_preparation.php'))->down();
        $this->assertFalse(Schema::hasTable('tbl_booking_events'));
        $this->assertFalse(Schema::hasTable('tbl_studio_equipment'));
        $this->assertFalse(Schema::hasColumn('tbl_bookings', 'preferred_camera_brand'));
        $this->assertFalse(Schema::hasColumn('tbl_booking_assigned_photographers', 'on_site_proof_image'));
        $this->assertTrue(Schema::hasTable('tbl_booking_equipment'));
        $this->assertDatabaseHas('tbl_bookings', ['id' => $this->booking->id]);
    }
}
