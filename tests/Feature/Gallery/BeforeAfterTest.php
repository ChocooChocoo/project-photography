<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Client\OnlineGalleryController;
use App\Http\Controllers\GalleryOriginalController;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Photographers attach the unedited original to a delivered photo; the
 * client gets both, index-aligned, for a before-and-after slider.
 */
class BeforeAfterTest extends TestCase
{
    private UserModel $client;

    private UserModel $owner;

    private StudioOnlineGalleryModel $gallery;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new PhotographerGalleryPublishTest('test_assigned_photographer_cannot_publish_while_approval_is_pending');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::table('tbl_studio_online_gallery', function ($table) {
            $table->text('drive_link')->nullable();
            $table->json('raw_images')->nullable();
        });
        Storage::fake('public');
        Route::post('/_originals/{type}/{galleryId}', [GalleryOriginalController::class, 'store']);
        Route::get('/_gallery/{id}/{type}', [OnlineGalleryController::class, 'getGalleryDetails']);

        $user = fn (string $role, string $email) => UserModel::create(['first_name' => ucfirst($role), 'last_name' => 'Test', 'email' => $email, 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => $role, 'status' => 'active']);
        $this->client = $user('client', 'ba-client@example.com');
        $this->owner = $user('owner', 'ba-owner@example.com');
        $studio = StudiosModel::create(['user_id' => $this->owner->id, 'studio_name' => 'Before After Studio', 'status' => 'verified']);
        $booking = BookingModel::create(['client_id' => $this->client->id, 'provider_id' => $studio->id, 'booking_type' => 'studio', 'booking_reference' => 'BA-1', 'event_name' => 'Portraits', 'event_date' => '2026-10-10', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => 'in_progress', 'payment_status' => 'paid']);
        $this->gallery = StudioOnlineGalleryModel::create(['booking_id' => $booking->id, 'studio_id' => $studio->id, 'client_id' => $this->client->id, 'gallery_reference' => 'BA-GAL', 'gallery_name' => 'Portraits', 'images' => ['studio-online-galleries/1/a.jpg', 'studio-online-galleries/1/b.jpg'], 'total_photos' => 2, 'status' => 'active', 'gallery_status' => 'draft', 'approval_status' => 'pending']);
    }

    private function upload(string $image)
    {
        return $this->postJson("/_originals/studio/{$this->gallery->id}", ['image_path' => $image, 'original' => UploadedFile::fake()->createWithContent('raw.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWf8AAAAASUVORK5CYII='))]);
    }

    public function test_owner_attaches_an_original_and_the_client_receives_it_index_aligned(): void
    {
        $this->actingAs($this->client);
        $this->upload('studio-online-galleries/1/b.jpg')->assertForbidden();

        $this->actingAs($this->owner);
        $this->upload('studio-online-galleries/1/missing.jpg')->assertUnprocessable();
        $this->upload('studio-online-galleries/1/b.jpg')->assertOk()->assertJsonPath('success', true);

        $original = $this->gallery->fresh()->raw_images['studio-online-galleries/1/b.jpg'];
        $this->assertStringStartsWith('studio-online-galleries/1/originals/', $original);
        Storage::disk('public')->assertExists($original);

        $this->gallery->update(['gallery_status' => 'published', 'approval_status' => 'approved']);
        $this->actingAs($this->client)->getJson("/_gallery/{$this->gallery->id}/studio")
            ->assertOk()->assertJsonPath('gallery.originals', [null, $original]);

        // Published galleries are locked.
        $this->actingAs($this->owner);
        $this->upload('studio-online-galleries/1/a.jpg')->assertUnprocessable();
    }

    public function test_removing_an_edited_photo_removes_its_original(): void
    {
        $this->actingAs($this->owner);
        $this->upload('studio-online-galleries/1/a.jpg')->assertOk();
        $original = $this->gallery->fresh()->raw_images['studio-online-galleries/1/a.jpg'];

        $gallery = $this->gallery->fresh();
        $gallery->update(['images' => ['studio-online-galleries/1/b.jpg'], 'total_photos' => 1]);

        $this->assertNull($gallery->fresh()->raw_images);
        Storage::disk('public')->assertMissing($original);
    }
}
