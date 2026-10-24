<?php

namespace Tests\Feature\Owner;

use App\Http\Controllers\StudioOwner\PackagesController;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PackageMediaTest extends TestCase
{
    private StudiosModel $studio;

    private CategoriesModel $category;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Storage::fake('public');

        $owner = UserModel::create([
            'role' => 'owner', 'user_type' => 'photographer', 'first_name' => 'Pkg', 'last_name' => 'Owner',
            'email' => 'pkg-owner@example.com', 'mobile_number' => '09170000009', 'password' => 'secret',
            'status' => 'active', 'email_verified' => true,
        ]);
        Auth::setUser($owner);
        $this->studio = StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'S', 'status' => 'verified']);
        $this->category = CategoriesModel::create(['category_name' => 'Portraits', 'status' => 'active']);

        Route::post('/_test/packages', [PackagesController::class, 'store']);
        Route::put('/_test/packages/{package}', [PackagesController::class, 'update']);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'studio_id' => $this->studio->id,
            'category_id' => $this->category->id,
            'package_name' => 'Gold',
            'package_description' => 'A gold package for portraits',
            'package_inclusions' => json_encode(['Edited photos']),
            'package_location' => ['In-Studio'],
            'allow_time_customization' => 0,
            'duration' => 2,
            'maximum_edited_photos' => 10,
            'package_price' => 5000,
            'online_gallery' => 1,
            'photographer_count' => 1,
            'status' => 'active',
        ], $extra);
    }

    public function test_store_saves_videos_materials_and_enhanced_price(): void
    {
        $this->post('/_test/packages', $this->payload([
            'cover_videos' => [UploadedFile::fake()->create('a.mp4', 1000, 'video/mp4'), UploadedFile::fake()->create('b.webm', 1000, 'video/webm')],
            'materials' => json_encode([['name' => 'Photo frame', 'quantity' => 2]]),
            'enhanced_photos_price' => 750,
        ]), ['Accept' => 'application/json'])->assertOk()->assertJsonPath('success', true);

        $package = PackagesModel::firstOrFail();
        $this->assertCount(2, $package->cover_videos);
        Storage::disk('public')->assertExists($package->cover_videos[0]);
        $this->assertSame([['name' => 'Photo frame', 'quantity' => 2]], $package->materials);
        $this->assertSame('750.00', $package->enhanced_photos_price);
    }

    public function test_store_saves_per_service_editing_prices(): void
    {
        $this->post('/_test/packages', $this->payload([
            'editing_prices' => ['color_grading' => 500, 'brightness_contrast' => null, 'retouching' => 1200],
        ]), ['Accept' => 'application/json'])->assertOk();

        $package = PackagesModel::firstOrFail();
        $this->assertEquals(['color_grading' => 500, 'brightness_contrast' => null, 'retouching' => 1200], $package->editing_prices);
        $this->assertSame(['color_grading', 'retouching'], array_keys($package->editingOptions()));

        $this->post('/_test/packages', $this->payload(['editing_prices' => ['face_swap' => 300]]), ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('editing_prices');
        $this->post('/_test/packages', $this->payload(['editing_prices' => ['retouching' => -5]]), ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('editing_prices.retouching');
    }

    public function test_store_rejects_bad_video_type_oversize_and_too_many(): void
    {
        $headers = ['Accept' => 'application/json'];

        $this->post('/_test/packages', $this->payload(['cover_videos' => [UploadedFile::fake()->create('x.exe', 10)]]), $headers)
            ->assertStatus(422)->assertJsonValidationErrors('cover_videos.0');

        $this->post('/_test/packages', $this->payload(['cover_videos' => [UploadedFile::fake()->create('big.mp4', 51201, 'video/mp4')]]), $headers)
            ->assertStatus(422)->assertJsonValidationErrors('cover_videos.0');

        $six = array_map(fn ($i) => UploadedFile::fake()->create("v$i.mp4", 10, 'video/mp4'), range(1, 6));
        $this->post('/_test/packages', $this->payload(['cover_videos' => $six]), $headers)
            ->assertStatus(422)->assertJsonValidationErrors('cover_videos');

        $this->post('/_test/packages', $this->payload(['materials' => json_encode([['name' => 'Frame', 'quantity' => 0]])]), $headers)
            ->assertStatus(422)->assertJsonValidationErrors('materials.0.quantity');
    }

    public function test_update_keeps_listed_videos_and_deletes_dropped_ones(): void
    {
        $this->post('/_test/packages', $this->payload([
            'cover_videos' => [UploadedFile::fake()->create('a.mp4', 10, 'video/mp4'), UploadedFile::fake()->create('b.mp4', 10, 'video/mp4')],
        ]), ['Accept' => 'application/json'])->assertOk();
        $package = PackagesModel::firstOrFail();
        [$keep, $drop] = $package->cover_videos;

        $this->put('/_test/packages/'.$package->id, $this->payload([
            'keep_cover_videos' => [$keep],
            'cover_videos' => [UploadedFile::fake()->create('c.mp4', 10, 'video/mp4')],
        ]), ['Accept' => 'application/json'])->assertOk();

        $fresh = $package->fresh();
        $this->assertCount(2, $fresh->cover_videos);
        $this->assertContains($keep, $fresh->cover_videos);
        Storage::disk('public')->assertMissing($drop);
        Storage::disk('public')->assertExists($keep);
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
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->string('package_name');
            $table->text('package_description')->nullable();
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
            $table->decimal('package_price', 10, 2)->default(0);
            $table->boolean('online_gallery')->default(false);
            $table->integer('photographer_count')->default(1);
            $table->json('package_location')->nullable();
            $table->boolean('allow_time_customization')->default(false);
            $table->string('status')->default('active');
            $table->boolean('allow_multiple_locations')->default(false);
            $table->integer('max_locations')->nullable();
            $table->json('cover_images')->nullable();
            $table->json('cover_videos')->nullable();
            $table->json('materials')->nullable();
            $table->decimal('enhanced_photos_price', 10, 2)->nullable();
            $table->json('editing_prices')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
