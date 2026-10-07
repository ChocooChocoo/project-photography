<?php

namespace Tests\Feature\Services;

use App\Http\Controllers\StudioOwner\ServicesController;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceMediaTest extends TestCase
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
            'role' => 'owner', 'user_type' => 'photographer', 'first_name' => 'Svc', 'last_name' => 'Owner',
            'email' => 'svc-media@example.com', 'mobile_number' => '09170000008', 'password' => 'secret',
            'status' => 'active', 'email_verified' => true,
        ]);
        Auth::setUser($owner);
        $this->studio = StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'S', 'status' => 'verified']);
        $this->category = CategoriesModel::create(['category_name' => 'Portraits', 'status' => 'active']);

        Route::post('/_test/services', [ServicesController::class, 'store']);
        Route::put('/_test/services/{id}', [ServicesController::class, 'update']);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'studio_id' => $this->studio->id,
            'category_id' => $this->category->id,
            'service_name' => ['Wedding'],
        ], $extra);
    }

    public function test_store_saves_images_and_several_videos(): void
    {
        $this->post('/_test/services', $this->payload([
            'images' => [UploadedFile::fake()->createWithContent('a.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='))],
            'videos' => [UploadedFile::fake()->create('a.mp4', 100, 'video/mp4'), UploadedFile::fake()->create('b.mov', 100, 'video/quicktime')],
        ]), ['Accept' => 'application/json'])->assertStatus(201);

        $service = ServicesModel::firstOrFail();
        $this->assertCount(1, $service->images);
        $this->assertCount(2, $service->videos);
        Storage::disk('public')->assertExists($service->videos[1]);
    }

    public function test_store_rejects_invalid_media(): void
    {
        $headers = ['Accept' => 'application/json'];

        $this->post('/_test/services', $this->payload(['videos' => [UploadedFile::fake()->create('x.avi', 10)]]), $headers)
            ->assertStatus(422)->assertJsonValidationErrors('videos.0');
        $this->post('/_test/services', $this->payload(['images' => [UploadedFile::fake()->create('x.pdf', 10)]]), $headers)
            ->assertStatus(422)->assertJsonValidationErrors('images.0');
    }

    public function test_update_removes_dropped_media(): void
    {
        $this->post('/_test/services', $this->payload([
            'videos' => [UploadedFile::fake()->create('a.mp4', 10, 'video/mp4')],
        ]), ['Accept' => 'application/json'])->assertStatus(201);
        $service = ServicesModel::firstOrFail();
        $old = $service->videos[0];

        $this->put('/_test/services/'.$service->id, $this->payload(['keep_videos_sent' => 1]), ['Accept' => 'application/json'])->assertOk();

        $this->assertSame([], $service->fresh()->videos);
        Storage::disk('public')->assertMissing($old);
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
        Schema::create('tbl_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->text('service_name');
            $table->decimal('starting_from', 10, 2)->nullable();
            $table->json('images')->nullable();
            $table->json('videos')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
