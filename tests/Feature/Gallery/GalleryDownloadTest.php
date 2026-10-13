<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Client\OnlineGalleryController;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GalleryDownloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new PhotographerGalleryPublishTest('test_assigned_photographer_cannot_publish_while_approval_is_pending');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::table('tbl_studio_online_gallery', fn ($table) => $table->text('drive_link')->nullable());
        Storage::fake('public');
        Route::get('/_download/{id}/{type}/{file}', [OnlineGalleryController::class, 'downloadFile'])->middleware(\App\Http\Middleware\ClientMiddleware::class);
        Route::get('/_zip/{id}/{type}', [OnlineGalleryController::class, 'downloadZip'])->middleware(\App\Http\Middleware\ClientMiddleware::class);
        Route::post('/_drive/{booking}', [\App\Http\Controllers\GalleryDeliveryController::class, 'saveDrive']);
    }

    public function test_download_requires_booking_owner_approval_and_publication(): void
    {
        $client = UserModel::create(['first_name' => 'Client', 'last_name' => 'Test', 'email' => 'download@example.com', 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => 'client', 'status' => 'active']);
        $booking = BookingModel::create(['client_id' => $client->id, 'provider_id' => 1, 'booking_type' => 'studio', 'booking_reference' => 'DOWNLOAD', 'event_name' => 'Download', 'event_date' => '2026-10-10', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => 'in_progress', 'payment_status' => 'paid']);
        $gallery = StudioOnlineGalleryModel::create(['booking_id' => $booking->id, 'studio_id' => 1, 'client_id' => $client->id, 'gallery_reference' => 'DOWNLOAD-GAL', 'gallery_name' => 'Download gallery', 'images' => ['studio-online-galleries/1/photo.jpg'], 'status' => 'active', 'gallery_status' => 'draft', 'approval_status' => 'pending']);
        Storage::disk('public')->put($gallery->images[0], 'photo content');
        $this->actingAs($client)->get("/_download/{$gallery->id}/studio/0")->assertNotFound();
        $gallery->update(['gallery_status' => 'published']);
        $this->get("/_download/{$gallery->id}/studio/0")->assertNotFound();
        $gallery->update(['approval_status' => 'approved']);
        $this->get("/_download/{$gallery->id}/studio/0")->assertOk()->assertDownload('photo.jpg');
        $gallery->update(['images' => ['../secrets.txt']]);
        $this->get("/_download/{$gallery->id}/studio/0")->assertNotFound();
        $gallery->update(['images' => ['studio-online-galleries/1/missing.jpg']]);
        $this->get("/_zip/{$gallery->id}/studio")->assertNotFound();
        $this->postJson("/_drive/{$booking->id}", ['drive_link' => 'https://drive.google.com/file/d/test/view'])->assertNotFound();
        $gallery->update(['images' => ['studio-online-galleries/1/photo.jpg']]);
        $this->get("/_download/{$gallery->id}/studio/99")->assertNotFound();
        $archive = $this->get("/_zip/{$gallery->id}/studio")->assertOk()->assertDownload('DOWNLOAD-GAL.zip');
        $archive->baseResponse->prepare(\Illuminate\Http\Request::create('/_zip'));
        $this->assertSame(200, $archive->baseResponse->getStatusCode());
        $this->assertGreaterThan(0, (int) $archive->baseResponse->headers->get('Content-Length'));
        $other = $client->replicate();
        $other->email = 'other-download@example.com';
        $other->save();
        $this->actingAs($other)->get("/_download/{$gallery->id}/studio/0")->assertNotFound();
        $this->get("/_zip/{$gallery->id}/studio")->assertNotFound();
        $owner = $client->replicate();
        $owner->email = 'drive-owner@example.com';
        $owner->role = 'owner';
        $owner->save();
        \App\Models\StudioOwner\StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'Drive Studio', 'status' => 'verified']);
        $gallery->update(['gallery_status' => 'draft']);
        $this->actingAs($owner)->postJson("/_drive/{$booking->id}", ['drive_link' => 'https://evil.test/a'])->assertUnprocessable();
        $this->postJson("/_drive/{$booking->id}", ['drive_link' => 'https://drive.google.com/file/d/test/view'])
            ->assertOk()->assertJsonPath('gallery.drive_link', 'https://drive.google.com/file/d/test/view')
            ->assertJsonPath('gallery.gallery_status', 'draft');
    }

    public function test_drive_hosts_are_exact_and_https_only(): void
    {
        foreach (['https://drive.google.com/file/d/test/view', 'https://docs.google.com/document/d/test/edit'] as $url) {
            $this->assertFalse(Validator::make(['link' => $url], ['link' => ['url', new \App\Rules\DriveLink]])->fails());
        }
        foreach (['http://drive.google.com/file/test', 'https://drive.google.com.evil.test/a', 'https://evil.test/a', 'https://user:pass@drive.google.com/a'] as $url) {
            $this->assertTrue(Validator::make(['link' => $url], ['link' => ['url', new \App\Rules\DriveLink]])->fails());
        }
    }

    public function test_gallery_details_require_booking_ownership_even_when_gallery_client_matches(): void
    {
        $client = UserModel::create(['first_name' => 'Client', 'last_name' => 'Test', 'email' => 'details@example.com', 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => 'client', 'status' => 'active']);
        $other = $client->replicate();
        $other->email = 'details-other@example.com';
        $other->save();
        $booking = BookingModel::create(['client_id' => $other->id, 'provider_id' => 1, 'booking_type' => 'studio', 'booking_reference' => 'DETAILS', 'event_name' => 'Details', 'event_date' => '2026-10-10', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => 'in_progress', 'payment_status' => 'paid']);
        $gallery = StudioOnlineGalleryModel::create(['booking_id' => $booking->id, 'studio_id' => 1, 'client_id' => $client->id, 'gallery_reference' => 'DETAILS-GAL', 'gallery_name' => 'Private gallery', 'images' => [], 'status' => 'active', 'gallery_status' => 'published', 'approval_status' => 'approved', 'drive_link' => 'https://drive.google.com/file/d/private/view']);
        Route::get('/_details/{id}/{type}', [OnlineGalleryController::class, 'getGalleryDetails']);

        $this->actingAs($client)->getJson("/_details/{$gallery->id}/studio")->assertNotFound();
    }

    public function test_owner_gallery_surface_offers_drive_only_delivery(): void
    {
        $html = view('owner.view-online-gallery', ['bookings' => collect()])->render();
        $this->assertStringContainsString('id="driveLinkForm"', $html);
        $this->assertStringContainsString('Save Drive link', $html);
        $this->assertStringContainsString('type="url"', $html);
    }

    public function test_owner_can_publish_drive_only_delivery_for_the_booking_client(): void
    {
        (require database_path('migrations/2026_02_22_145351_create_tbl_notifications_table.php'))->up();
        Route::post('/_publish/{id}', [\App\Http\Controllers\StudioOwner\OnlineGalleryController::class, 'publish']);
        Route::get('/_details/{id}/{type}', [OnlineGalleryController::class, 'getGalleryDetails']);
        $owner = UserModel::create(['first_name' => 'Owner', 'last_name' => 'Test', 'email' => 'drive-publish-owner@example.com', 'mobile_number' => '09123456789', 'password' => bcrypt('password'), 'role' => 'owner', 'status' => 'active']);
        $client = $owner->replicate();
        $client->email = 'drive-publish-client@example.com';
        $client->role = 'client';
        $client->save();
        $studio = \App\Models\StudioOwner\StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'Drive Studio', 'status' => 'verified']);
        $booking = BookingModel::create(['client_id' => $client->id, 'provider_id' => $studio->id, 'booking_type' => 'studio', 'booking_reference' => 'DRIVE-PUBLISH', 'event_name' => 'Drive delivery', 'event_date' => '2026-10-10', 'total_amount' => 100, 'down_payment' => 0, 'payment_type' => 'full_payment', 'status' => 'in_progress', 'payment_status' => 'paid']);
        $link = 'https://drive.google.com/file/d/delivery/view';
        $response = $this->actingAs($owner)->postJson("/_drive/{$booking->id}", ['drive_link' => $link])->assertOk();
        $id = $response->json('gallery.id');
        $response->assertJsonPath('gallery.images', [])->assertJsonPath('gallery.approval_status', 'approved')->assertJsonPath('gallery.gallery_status', 'draft');
        $this->actingAs($client)->getJson("/_details/{$id}/studio")->assertNotFound();
        $this->actingAs($owner)->postJson("/_publish/{$id}")->assertOk();
        $this->actingAs($client)->getJson("/_details/{$id}/studio")->assertOk()->assertJsonPath('gallery.drive_link', $link)->assertJsonPath('gallery.zip_url', null);
        $this->get("/_zip/{$id}/studio")->assertUnprocessable();
        $this->actingAs($owner)->postJson("/_drive/{$booking->id}", ['drive_link' => $link])->assertUnprocessable();
    }

    public function test_download_routes_belong_to_client_portal(): void
    {
        $routes = Route::getRoutes();
        $this->assertSame('client/online-gallery/{id}/{type}/download/{file}', $routes->getByName('client.online-gallery.download')->uri());
        $this->assertSame('client/online-gallery/{id}/{type}/download-zip', $routes->getByName('client.online-gallery.zip')->uri());
    }
}
