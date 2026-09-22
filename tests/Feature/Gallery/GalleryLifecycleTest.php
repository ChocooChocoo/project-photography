<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Client\OnlineGalleryController as ClientOnlineGalleryController;
use App\Http\Controllers\StudioOwner\OnlineGalleryController as OwnerOnlineGalleryController;
use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryLifecycleTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'gallery-owner@example.com');
        Auth::setUser($this->owner);

        Route::post('/_test/owner/gallery/{booking}/upload', [OwnerOnlineGalleryController::class, 'uploadImages']);
        Route::post('/_test/owner/gallery/{gallery}/publish', [OwnerOnlineGalleryController::class, 'publish']);
        Route::post('/_test/owner/gallery/{gallery}/approve', [OwnerOnlineGalleryController::class, 'approve']);
        Route::post('/_test/owner/gallery/{gallery}/reject', [OwnerOnlineGalleryController::class, 'reject']);
        Route::get('/_test/client/gallery/{id}/{type}', [ClientOnlineGalleryController::class, 'getGalleryDetails']);
    }

    public function test_studio_gallery_draft_publish_and_client_visibility(): void
    {
        $client = $this->createUser('client', 'gallery-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, 'in_progress');
        $this->attachStudioPackage($booking, $studio);

        Storage::fake('public');

        $this->post("/_test/owner/gallery/{$booking->id}/upload", [
            'images' => [$this->fakePng('photo1.png')],
            'gallery_name' => 'Wedding Gallery',
        ])->assertOk()->assertJsonPath('gallery.gallery_status', 'draft');

        $gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('draft', $gallery->gallery_status);
        $this->assertNull($gallery->published_at);
        $this->assertCount(1, $gallery->images);
        $this->assertStringStartsWith('studio-online-galleries/', $gallery->images[0]);

        // Draft galleries are hidden from the client.
        $this->actingAs($client)
            ->getJson("/_test/client/gallery/{$gallery->id}/studio")
            ->assertNotFound()
            ->assertJsonPath('success', false);

        Auth::setUser($this->owner);
        $this->post("/_test/owner/gallery/{$gallery->id}/publish")
            ->assertOk()
            ->assertJsonPath('gallery.gallery_status', 'published');

        $gallery->refresh();
        $this->assertSame('published', $gallery->gallery_status);
        $this->assertNotNull($gallery->published_at);

        // Published galleries become visible to the client.
        $this->actingAs($client)
            ->getJson("/_test/client/gallery/{$gallery->id}/studio")
            ->assertOk()
            ->assertJsonPath('gallery.gallery_reference', $gallery->gallery_reference)
            ->assertJsonPath('gallery.total_photos', 1);
    }

    public function test_owner_can_approve_a_pending_gallery(): void
    {
        $gallery = $this->createPendingGallery();

        Auth::setUser($this->owner);

        $this->post("/_test/owner/gallery/{$gallery->id}/approve")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.approval_status', 'approved');

        $gallery->refresh();
        $this->assertSame('approved', $gallery->approval_status);
        $this->assertSame($this->owner->id, $gallery->approved_by);
        $this->assertNotNull($gallery->approved_at);
        $this->assertNull($gallery->rejected_at);
    }

    public function test_owner_can_reject_a_pending_gallery_with_a_reason(): void
    {
        $gallery = $this->createPendingGallery();

        Auth::setUser($this->owner);

        $this->post("/_test/owner/gallery/{$gallery->id}/reject", [
            'rejection_reason' => 'The photos are not edited yet.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.approval_status', 'rejected');

        $gallery->refresh();
        $this->assertSame('rejected', $gallery->approval_status);
        $this->assertSame($this->owner->id, $gallery->rejected_by);
        $this->assertSame('The photos are not edited yet.', $gallery->rejection_reason);
        $this->assertNotNull($gallery->rejected_at);
        $this->assertNull($gallery->approved_at);
    }

    public function test_owner_rejection_requires_a_reason(): void
    {
        $gallery = $this->createPendingGallery();

        Auth::setUser($this->owner);

        $this->postJson("/_test/owner/gallery/{$gallery->id}/reject")
            ->assertStatus(422);

        $gallery->refresh();
        $this->assertSame('pending', $gallery->approval_status);
    }

    public function test_owner_cannot_process_a_gallery_that_is_not_pending(): void
    {
        $gallery = $this->createPendingGallery([
            'approval_status' => 'approved',
            'approved_by' => $this->owner->id,
            'approved_at' => now(),
        ]);

        Auth::setUser($this->owner);

        $this->post("/_test/owner/gallery/{$gallery->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_publishing_a_pending_gallery_records_the_owner_approval(): void
    {
        $gallery = $this->createPendingGallery();

        Auth::setUser($this->owner);

        $this->post("/_test/owner/gallery/{$gallery->id}/publish")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('gallery.gallery_status', 'published')
            ->assertJsonPath('gallery.approval_status', 'approved');

        $gallery->refresh();
        $this->assertSame('published', $gallery->gallery_status);
        $this->assertSame('approved', $gallery->approval_status);
        $this->assertSame($this->owner->id, $gallery->approved_by);
        $this->assertNotNull($gallery->approved_at);
        $this->assertNotNull($gallery->published_at);
    }

    public function test_publishing_a_rejected_gallery_clears_the_stale_rejection(): void
    {
        $gallery = $this->createPendingGallery([
            'approval_status' => 'rejected',
            'rejected_by' => $this->owner->id,
            'rejected_at' => now(),
            'rejection_reason' => 'The photos are not edited yet.',
        ]);

        Auth::setUser($this->owner);

        $this->post("/_test/owner/gallery/{$gallery->id}/publish")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('gallery.gallery_status', 'published')
            ->assertJsonPath('gallery.approval_status', 'approved');

        $gallery->refresh();
        $this->assertSame('published', $gallery->gallery_status);
        $this->assertSame('approved', $gallery->approval_status);
        $this->assertSame($this->owner->id, $gallery->approved_by);
        $this->assertNull($gallery->rejection_reason);
        $this->assertNull($gallery->rejected_by);
        $this->assertNull($gallery->rejected_at);
    }

    public function test_publishing_an_approved_gallery_stays_approved(): void
    {
        $approvedAt = now()->subDay();

        $gallery = $this->createPendingGallery([
            'approval_status' => 'approved',
            'approved_by' => $this->owner->id,
            'approved_at' => $approvedAt,
        ]);

        Auth::setUser($this->owner);

        $this->post("/_test/owner/gallery/{$gallery->id}/publish")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('gallery.gallery_status', 'published')
            ->assertJsonPath('gallery.approval_status', 'approved');

        $gallery->refresh();
        $this->assertSame('published', $gallery->gallery_status);
        $this->assertSame('approved', $gallery->approval_status);
        $this->assertSame($this->owner->id, $gallery->approved_by);
    }

    public function test_gallery_upload_rejected_for_non_active_booking(): void
    {
        $client = $this->createUser('client', 'gallery-client-2@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, 'confirmed');
        $this->attachStudioPackage($booking, $studio);

        Storage::fake('public');

        $this->post("/_test/owner/gallery/{$booking->id}/upload", [
            'images' => [$this->fakePng('photo1.png')],
        ])->assertStatus(500)->assertJsonPath('success', false);

        $this->assertSame(0, StudioOnlineGalleryModel::count());
    }

    private function createPendingGallery(array $overrides = []): StudioOnlineGalleryModel
    {
        $client = $this->createUser('client', 'gallery-pending-client@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, 'in_progress');

        return StudioOnlineGalleryModel::create(array_merge([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'client_id' => $client->id,
            'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
            'gallery_name' => 'Pending Gallery',
            'images' => ['studio-online-galleries/1/photo.png'],
            'total_photos' => 1,
            'status' => 'active',
            'gallery_status' => 'draft',
            'approval_status' => 'pending',
            'submitted_by' => $this->owner->id,
            'submitted_at' => now(),
        ], $overrides));
    }

    private function fakePng(string $name): UploadedFile
    {
        // Minimal 1x1 transparent PNG; getimagesize() parses it without GD.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $path = tempnam(sys_get_temp_dir(), 'gallery').'.png';
        file_put_contents($path, $png);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Gallery',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000003',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Gallery Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(StudiosModel $studio, UserModel $client, string $status): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => $status,
            'payment_status' => 'paid',
        ]);
    }

    private function attachStudioPackage(BookingModel $booking, StudiosModel $studio): void
    {
        $package = PackagesModel::create([
            'studio_id' => $studio->id,
            'package_name' => 'Premier Shoot',
            'package_price' => 1000,
            'online_gallery' => 1,
            'photographer_count' => 1,
            'status' => 'active',
        ]);

        BookingPackageModel::create([
            'booking_id' => $booking->id,
            'package_id' => $package->id,
            'package_type' => 'studio',
            'package_name' => $package->package_name,
            'package_price' => $package->package_price,
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
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->enum('gallery_type', ['booking', 'portfolio'])->default('booking');
            $table->string('gallery_reference')->unique();
            $table->string('gallery_name')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('total_photos')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->enum('gallery_status', ['draft', 'published'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'cancelled'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }
}
