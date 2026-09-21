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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GalleryVisibilityTest extends TestCase
{
    private UserModel $owner;
    private StudiosModel $studio;
    private UserModel $client;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        $this->owner = $this->createUser('owner', 'visibility-owner@example.com');
        $this->studio = $this->createStudio();
        $this->client = $this->createUser('client', 'visibility-client@example.com');
    }

    public function test_client_sees_published_gallery_for_in_progress_booking(): void
    {
        $booking = $this->createBooking('in_progress');
        $this->attachStudioPackage($booking);
        $this->createGallery($booking, 'published');

        Auth::setUser($this->client);

        $view = app(ClientOnlineGalleryController::class)->index();
        $galleries = $view->getData()['galleries'];

        $this->assertCount(1, $galleries);
        $this->assertSame($booking->booking_reference, $galleries->first()->booking_reference);
        $this->assertSame('studio', $galleries->first()->type);
    }

    public function test_client_does_not_see_draft_or_not_started_galleries(): void
    {
        $inProgress = $this->createBooking('in_progress');
        $this->attachStudioPackage($inProgress);
        $this->createGallery($inProgress, 'draft');

        $confirmed = $this->createBooking('confirmed');
        $this->attachStudioPackage($confirmed);
        $this->createGallery($confirmed, 'published');

        Auth::setUser($this->client);

        $galleries = app(ClientOnlineGalleryController::class)->index()->getData()['galleries'];

        $this->assertCount(0, $galleries);
    }

    public function test_owner_gallery_list_includes_in_progress_booking(): void
    {
        $booking = $this->createBooking('in_progress');
        $this->attachStudioPackage($booking);

        Auth::setUser($this->owner);

        $view = app(OwnerOnlineGalleryController::class)->index();
        $bookings = $view->getData()['bookings'];

        $this->assertCount(1, $bookings);
        $this->assertSame($booking->id, $bookings->first()->id);
        $this->assertFalse($bookings->first()->has_gallery);
    }

    public function test_owner_gallery_json_endpoint_includes_in_progress_booking(): void
    {
        $booking = $this->createBooking('in_progress');
        $this->attachStudioPackage($booking);
        $this->createGallery($booking, 'draft');

        Auth::setUser($this->owner);

        $response = app(OwnerOnlineGalleryController::class)->getCompletedBookings();

        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertCount(1, $payload['bookings']);
        $this->assertSame($booking->id, $payload['bookings'][0]['id']);
        $this->assertSame('draft', $payload['bookings'][0]['gallery_status']);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Visibility',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000010',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Visibility Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(string $status): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $this->client->id,
            'booking_type' => 'studio',
            'provider_id' => $this->studio->id,
            'event_name' => 'Visibility Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => $status,
            'payment_status' => 'paid',
        ]);
    }

    private function attachStudioPackage(BookingModel $booking): void
    {
        $package = PackagesModel::create([
            'studio_id' => $this->studio->id,
            'package_name' => 'Visibility Package',
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

    private function createGallery(BookingModel $booking, string $galleryStatus): StudioOnlineGalleryModel
    {
        return StudioOnlineGalleryModel::create([
            'booking_id' => $booking->id,
            'studio_id' => $this->studio->id,
            'client_id' => $this->client->id,
            'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
            'gallery_name' => 'Visibility Gallery',
            'images' => ['studio-online-galleries/1/photo.png'],
            'total_photos' => 1,
            'status' => 'active',
            'gallery_status' => $galleryStatus,
            'published_at' => $galleryStatus === 'published' ? now() : null,
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
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('category_id')->nullable();
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
            $table->softDeletes();
            $table->timestamps();
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
            $table->timestamps();
        });
    }
}
