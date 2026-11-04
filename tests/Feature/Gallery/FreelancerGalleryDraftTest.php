<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Freelancer\OnlineGalleryController;
use App\Http\Controllers\GalleryOriginalController;
use App\Models\BookingModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\UserModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FreelancerGalleryDraftTest extends TestCase
{
    private BookingModel $booking;
    private UserModel $freelancer;
    private UserModel $client;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new PhotographerGalleryPublishTest('test_assigned_photographer_cannot_publish_while_approval_is_pending');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::create('tbl_freelancer_online_gallery', function ($table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('client_id');
            $table->string('gallery_reference');
            $table->string('gallery_name')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_images')->nullable();
            $table->string('status');
            $table->integer('total_photos')->default(0);
            $table->string('gallery_status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('approval_status')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Storage::fake('public');
        Route::post('/_freelancer/upload/{booking}', [OnlineGalleryController::class, 'uploadImages']);
        Route::post('/_freelancer/publish/{gallery}', [OnlineGalleryController::class, 'publish']);
        Route::post('/_freelancer/original/{type}/{galleryId}', [GalleryOriginalController::class, 'store']);
        Route::get('/_freelancer/client/{id}/{type}', [\App\Http\Controllers\Client\OnlineGalleryController::class, 'getGalleryDetails']);
        $this->freelancer = UserModel::create(['first_name' => 'Freelancer', 'last_name' => 'Test', 'email' => 'draft-freelancer@example.test', 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => 'freelancer', 'status' => 'active']);
        $this->client = $this->freelancer->replicate();
        $this->client->fill(['email' => 'draft-client@example.test', 'role' => 'client'])->save();
        $this->booking = BookingModel::create(['client_id' => $this->client->id, 'provider_id' => $this->freelancer->id, 'booking_type' => 'freelancer', 'booking_reference' => 'FREELANCER-DRAFT', 'event_name' => 'Portraits', 'event_date' => '2026-11-03', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => 'completed', 'payment_status' => 'paid']);
        $this->actingAs($this->freelancer);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWf8AAAAASUVORK5CYII='));
    }

    private function upload(): FreelanceOnlineGalleryModel
    {
        $this->postJson("/_freelancer/upload/{$this->booking->id}", ['images' => [$this->photo()]])->assertOk();

        return FreelanceOnlineGalleryModel::firstOrFail();
    }

    public function test_uploaded_gallery_stays_draft_until_originals_are_attached_and_published(): void
    {
        $gallery = $this->upload();
        $this->assertSame('draft', $gallery->gallery_status);
        $this->assertNull($gallery->published_at);
        $this->actingAs($this->client)->getJson("/_freelancer/client/{$gallery->id}/freelancer")->assertNotFound();
        $this->actingAs($this->freelancer);
        $this->postJson("/_freelancer/original/freelancer/{$gallery->id}", ['image_path' => $gallery->images[0], 'original' => $this->photo()])->assertOk();
        $this->postJson("/_freelancer/publish/{$gallery->id}")->assertOk()->assertJsonPath('gallery.gallery_status', 'published');
        $this->assertNotNull($gallery->fresh()->published_at);
        $this->assertNotEmpty($gallery->fresh()->raw_images);
        $this->actingAs($this->client)->getJson("/_freelancer/client/{$gallery->id}/freelancer")->assertOk();
        $this->actingAs($this->freelancer);
        $this->postJson("/_freelancer/original/freelancer/{$gallery->id}", ['image_path' => $gallery->images[0], 'original' => $this->photo()])->assertUnprocessable();
    }

    public function test_another_user_cannot_publish_gallery(): void
    {
        $gallery = $this->upload();
        $this->actingAs($this->client)->postJson("/_freelancer/publish/{$gallery->id}")->assertNotFound();
    }

    public function test_published_gallery_rejects_additional_images(): void
    {
        $gallery = $this->upload();
        $gallery->update(['gallery_status' => 'published', 'published_at' => now()]);
        $this->postJson("/_freelancer/upload/{$this->booking->id}", ['images' => [$this->photo()]])->assertUnprocessable();
        $this->assertCount(1, $gallery->fresh()->images);
    }
}
