<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Freelancer\OnlineGalleryController as FreelancerGallery;
use App\Http\Controllers\StudioHR\OnlineGalleryController as HrGallery;
use App\Http\Controllers\StudioOwner\OnlineGalleryController as OwnerGallery;
use App\Http\Controllers\StudioPhotographer\OnlineGalleryController as PhotographerGallery;
use App\Models\BookingModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A completed booking's gallery (or a published freelancer gallery) cannot be
 * deleted, whole or photo by photo, from any provider portal.
 */
class GalleryDeletionLockTest extends TestCase
{
    private const PHOTO = 'galleries/1/a.jpg';

    private UserModel $owner;

    private UserModel $client;

    private UserModel $photographer;

    private UserModel $freelancer;

    private StudiosModel $studio;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new PhotographerGalleryPublishTest('test_assigned_photographer_cannot_publish_while_approval_is_pending');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::table('tbl_studio_online_gallery', fn ($table) => $table->json('raw_images')->nullable());
        Schema::create('tbl_freelancer_online_gallery', function ($table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('client_id');
            $table->string('gallery_reference');
            $table->string('gallery_name')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_images')->nullable();
            $table->string('status');
            $table->integer('total_photos')->default(0);
            $table->string('gallery_status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('approval_status')->nullable();
            $table->timestamps();
        });
        Storage::fake('public');

        foreach (['owner' => OwnerGallery::class, 'hr' => HrGallery::class, 'photographer' => PhotographerGallery::class, 'freelancer' => FreelancerGallery::class] as $portal => $controller) {
            Route::delete("/_lock/{$portal}/{galleryId}/image", [$controller, 'deleteImage']);
            Route::delete("/_lock/{$portal}/{galleryId}", [$controller, 'deleteGallery']);
        }

        $user = fn (string $role, string $email) => UserModel::create(['first_name' => ucfirst($role), 'last_name' => 'Test', 'email' => $email, 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => $role, 'status' => 'active']);
        $this->owner = $user('owner', 'lock-owner@example.test');
        $this->client = $user('client', 'lock-client@example.test');
        $this->photographer = $user('studio_photographer', 'lock-photographer@example.test');
        $this->freelancer = $user('freelancer', 'lock-freelancer@example.test');
        $this->studio = StudiosModel::create(['user_id' => $this->owner->id, 'studio_name' => 'Lock Studio', 'status' => 'verified']);
    }

    private function booking(string $type, string $status): BookingModel
    {
        return BookingModel::create(['client_id' => $this->client->id, 'provider_id' => $type === 'studio' ? $this->studio->id : $this->freelancer->id, 'booking_type' => $type, 'booking_reference' => 'LOCK-'.str()->random(8), 'event_name' => 'Event', 'event_date' => '2026-10-10', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => $status, 'payment_status' => 'paid']);
    }

    private function studioGallery(string $bookingStatus): StudioOnlineGalleryModel
    {
        $booking = $this->booking('studio', $bookingStatus);
        BookingAssignedPhotographerModel::create(['booking_id' => $booking->id, 'studio_id' => $this->studio->id, 'photographer_id' => $this->photographer->id, 'status' => 'completed']);
        Storage::disk('public')->put(self::PHOTO, 'x');

        return StudioOnlineGalleryModel::create(['booking_id' => $booking->id, 'studio_id' => $this->studio->id, 'client_id' => $this->client->id, 'gallery_reference' => 'G-'.str()->random(8), 'images' => [self::PHOTO], 'total_photos' => 1, 'status' => 'active', 'gallery_status' => 'published']);
    }

    private function freelancerGallery(string $galleryStatus): FreelanceOnlineGalleryModel
    {
        $booking = $this->booking('freelancer', 'completed');
        Storage::disk('public')->put(self::PHOTO, 'x');

        return FreelanceOnlineGalleryModel::create(['booking_id' => $booking->id, 'freelancer_id' => $this->freelancer->id, 'client_id' => $this->client->id, 'gallery_reference' => 'FG-'.str()->random(8), 'images' => [self::PHOTO], 'total_photos' => 1, 'status' => 'active', 'gallery_status' => $galleryStatus]);
    }

    private function hrUser(): UserModel
    {
        $hr = new class extends UserModel
        {
            public array $studioIds = [];

            public function getAssignedStudioIds(?string $portal = null): \Illuminate\Support\Collection
            {
                return collect($this->studioIds);
            }
        };
        $hr->forceFill(UserModel::create(['first_name' => 'Hr', 'last_name' => 'Test', 'email' => 'lock-hr-'.str()->random(6).'@example.test', 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => 'studio-hr', 'status' => 'active'])->getAttributes());
        $hr->exists = true;
        $hr->studioIds = [$this->studio->id];

        return $hr;
    }

    /** @return array<string, UserModel> */
    private function studioActors(): array
    {
        return ['owner' => $this->owner, 'hr' => $this->hrUser(), 'photographer' => $this->photographer];
    }

    public function test_studio_portals_cannot_delete_a_completed_booking_gallery(): void
    {
        foreach ($this->studioActors() as $portal => $actor) {
            $gallery = $this->studioGallery('completed');
            $this->actingAs($actor);

            $this->deleteJson("/_lock/{$portal}/{$gallery->id}/image", ['image_path' => self::PHOTO])
                ->assertStatus(422)->assertJsonPath('message', BookingModel::GALLERY_DELETION_LOCKED_MESSAGE);
            $this->deleteJson("/_lock/{$portal}/{$gallery->id}")
                ->assertStatus(422)->assertJsonPath('success', false);

            $this->assertSame([self::PHOTO], $gallery->fresh()->images, $portal);
            Storage::disk('public')->assertExists(self::PHOTO);
            $gallery->delete();
        }
    }

    public function test_studio_portals_can_still_delete_while_in_progress(): void
    {
        foreach ($this->studioActors() as $portal => $actor) {
            $this->actingAs($actor);

            $gallery = $this->studioGallery('in_progress');
            $this->deleteJson("/_lock/{$portal}/{$gallery->id}/image", ['image_path' => self::PHOTO])->assertOk();
            $this->assertSame([], $gallery->fresh()->images, $portal);

            $gallery = $this->studioGallery('in_progress');
            $this->deleteJson("/_lock/{$portal}/{$gallery->id}")->assertOk();
            $this->assertNull($gallery->fresh(), $portal);
        }
    }

    public function test_freelancer_gallery_locks_only_once_published(): void
    {
        $this->actingAs($this->freelancer);

        $published = $this->freelancerGallery('published');
        $this->deleteJson("/_lock/freelancer/{$published->id}/image", ['image_path' => self::PHOTO])->assertStatus(422);
        $this->deleteJson("/_lock/freelancer/{$published->id}")->assertStatus(422);
        $this->assertSame([self::PHOTO], $published->fresh()->images);
        Storage::disk('public')->assertExists(self::PHOTO);

        $draft = $this->freelancerGallery('draft');
        $this->deleteJson("/_lock/freelancer/{$draft->id}/image", ['image_path' => self::PHOTO])->assertOk();
        $this->deleteJson("/_lock/freelancer/{$draft->id}")->assertOk();
        $this->assertNull($draft->fresh());
    }
}
