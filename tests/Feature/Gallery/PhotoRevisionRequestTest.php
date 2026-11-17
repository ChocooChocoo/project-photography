<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Client\MyBookingsController;
use App\Http\Controllers\GalleryRevisionController;
use App\Models\BookingModel;
use App\Models\NotificationModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The client flags individual photos for revision; the gallery stays
 * published and the provider replaces each flagged photo in place.
 */
class PhotoRevisionRequestTest extends TestCase
{
    private const A = 'studio-online-galleries/1/a.jpg';

    private const B = 'studio-online-galleries/1/b.jpg';

    private const C = 'studio-online-galleries/1/c.jpg';

    private UserModel $client;

    private UserModel $owner;

    private BookingModel $booking;

    private StudioOnlineGalleryModel $gallery;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new PhotographerGalleryPublishTest('test_assigned_photographer_cannot_publish_while_approval_is_pending');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::table('tbl_bookings', function ($table) {
            $table->timestamp('revision_requested_at')->nullable();
            $table->timestamp('revision_deadline')->nullable();
        });
        Schema::table('tbl_studio_online_gallery', fn ($table) => $table->json('raw_images')->nullable());
        Schema::create('tbl_freelancer_online_gallery', function ($table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->json('images')->nullable();
            $table->json('raw_images')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_17_100000_add_photo_revision_fields_to_online_galleries.php'))->up();

        Storage::fake('public');
        Route::post('/_revision/request/{id}', [MyBookingsController::class, 'requestRevision']);
        Route::post('/_revision/replace/{type}/{galleryId}', [GalleryRevisionController::class, 'replace']);

        $user = fn (string $role, string $email) => UserModel::create(['first_name' => ucfirst($role), 'last_name' => 'Test', 'email' => $email, 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => $role, 'status' => 'active']);
        $this->client = $user('client', 'rev-client@example.test');
        $this->owner = $user('owner', 'rev-owner@example.test');
        $studio = StudiosModel::create(['user_id' => $this->owner->id, 'studio_name' => 'Revision Studio', 'status' => 'verified']);
        $this->booking = BookingModel::create(['client_id' => $this->client->id, 'provider_id' => $studio->id, 'booking_type' => 'studio', 'booking_reference' => 'REV-1', 'event_name' => 'Portraits', 'event_date' => '2026-10-10', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => 'completed', 'payment_status' => 'paid', 'revision_deadline' => now()->addDays(7)]);

        foreach ([self::A, self::B, self::C] as $path) {
            Storage::disk('public')->put($path, 'edited');
        }
        Storage::disk('public')->put('studio-online-galleries/1/originals/a-raw.jpg', 'raw');
        $this->gallery = StudioOnlineGalleryModel::create(['booking_id' => $this->booking->id, 'studio_id' => $studio->id, 'client_id' => $this->client->id, 'gallery_reference' => 'REV-GAL', 'gallery_name' => 'Portraits', 'images' => [self::A, self::B, self::C], 'raw_images' => [self::A => 'studio-online-galleries/1/originals/a-raw.jpg'], 'total_photos' => 3, 'status' => 'active', 'gallery_status' => 'published', 'approval_status' => 'approved']);
    }

    private function request(array $photos, ?string $note = 'Please fix the lighting')
    {
        return $this->actingAs($this->client)->postJson("/_revision/request/{$this->booking->id}", ['photos' => $photos, 'note' => $note]);
    }

    private function replace(string $path)
    {
        return $this->postJson("/_revision/replace/studio/{$this->gallery->id}", ['image_path' => $path, 'image' => UploadedFile::fake()->createWithContent('new.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWf8AAAAASUVORK5CYII='))]);
    }

    public function test_client_flags_a_subset_and_the_gallery_stays_published(): void
    {
        $this->request([self::A, self::C, self::A])->assertOk()->assertJsonPath('success', true);

        $gallery = $this->gallery->fresh();
        $this->assertSame([self::A, self::C], $gallery->revision_photos);
        $this->assertSame('Please fix the lighting', $gallery->revision_note);
        $this->assertSame('published', $gallery->gallery_status);
        $this->assertNotNull($this->booking->fresh()->revision_requested_at);

        $notice = NotificationModel::where('user_id', $this->owner->id)->where('type', 'revision_requested')->firstOrFail();
        $this->assertStringContainsString('2 photo(s)', $notice->message);
    }

    public function test_foreign_path_is_rejected(): void
    {
        $this->request([self::A, 'studio-online-galleries/9/other.jpg'])->assertStatus(422);
        $this->assertNull($this->gallery->fresh()->revision_photos);
        $this->assertNull($this->booking->fresh()->revision_requested_at);
    }

    public function test_photos_are_required(): void
    {
        $this->request([])->assertStatus(422);
    }

    public function test_second_request_is_rejected(): void
    {
        $this->request([self::A])->assertOk();
        $this->request([self::B])->assertStatus(403);
        $this->assertSame([self::A], $this->gallery->fresh()->revision_photos);
    }

    public function test_replace_swaps_the_photo_in_place_and_resolves_after_the_last_one(): void
    {
        $this->request([self::A, self::B])->assertOk();
        $this->actingAs($this->owner);

        $response = $this->replace(self::A)->assertOk()->assertJsonPath('revision_photos', [self::B]);
        $gallery = $this->gallery->fresh();
        $newA = $gallery->images[0];

        $this->assertNotSame(self::A, $newA);
        $this->assertStringStartsWith('studio-online-galleries/1/', $newA);
        $this->assertSame([self::B, self::C], array_slice($gallery->images, 1));
        $this->assertSame($response->json('images'), $gallery->images);
        $this->assertSame([$newA => 'studio-online-galleries/1/originals/a-raw.jpg'], $gallery->raw_images);
        Storage::disk('public')->assertMissing(self::A);
        Storage::disk('public')->assertExists($newA);
        Storage::disk('public')->assertExists('studio-online-galleries/1/originals/a-raw.jpg');
        $this->assertNull($gallery->revision_resolved_at);
        $this->assertSame(0, NotificationModel::where('type', 'gallery_revision_ready')->count());

        $this->replace(self::B)->assertOk()->assertJsonPath('revision_photos', []);
        $gallery = $gallery->fresh();
        $this->assertNotNull($gallery->revision_resolved_at);
        $this->assertSame('published', $gallery->gallery_status);
        $this->assertSame(1, NotificationModel::where('user_id', $this->client->id)->where('type', 'gallery_revision_ready')->count());
    }

    public function test_unflagged_photo_cannot_be_replaced(): void
    {
        $this->request([self::A])->assertOk();
        $this->actingAs($this->owner);

        $this->replace(self::C)->assertStatus(422);
        $this->assertSame([self::A, self::B, self::C], $this->gallery->fresh()->images);
    }

    public function test_unauthorized_user_cannot_replace(): void
    {
        $this->request([self::A])->assertOk();

        $this->actingAs($this->client);
        $this->replace(self::A)->assertForbidden();
        $this->assertSame(self::A, $this->gallery->fresh()->images[0]);
        Storage::disk('public')->assertExists(self::A);
    }
}
