<?php

namespace Tests\Feature\Services;

use App\Http\Controllers\StudioOwner\ServicesController;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerServicesTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createOwner();
        Auth::setUser($this->owner);

        Route::get('/_test/owner/services', [ServicesController::class, 'getServices']);
        Route::get('/_test/owner/services/{id}', [ServicesController::class, 'show']);
        Route::get('/_test/owner/services/{id}/edit', [ServicesController::class, 'edit']);
    }

    public function test_service_name_cast_returns_array(): void
    {
        $category = CategoriesModel::create([
            'category_name' => 'Portraits',
            'status' => 'active',
        ]);
        $studio = $this->createStudio();
        $service = ServicesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'service_name' => ['Portrait', 'Event'],
        ]);

        $fresh = ServicesModel::findOrFail($service->id);

        $this->assertSame(['Portrait', 'Event'], $fresh->service_name);
        $this->assertIsString($fresh->getRawOriginal('service_name'));
        $this->assertStringContainsString('"Portrait"', $fresh->getRawOriginal('service_name'));
    }

    public function test_view_services_page_renders_without_throwing(): void
    {
        $category = CategoriesModel::create([
            'category_name' => 'Portraits',
            'status' => 'active',
        ]);
        $studio = $this->createStudio();
        ServicesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'service_name' => ['Wedding Photography'],
        ]);

        $html = view('owner.view-services')->render();

        $this->assertStringContainsString('Wedding Photography', $html);
    }

    public function test_service_endpoints_return_service_names_array(): void
    {
        $category = CategoriesModel::create([
            'category_name' => 'Portraits',
            'status' => 'active',
        ]);
        $studio = $this->createStudio();
        $service = ServicesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'service_name' => ['Portrait', 'Event'],
        ]);

        $this->getJson("/_test/owner/services/{$service->id}")
            ->assertOk()
            ->assertJsonPath('data.service_names_array', ['Portrait', 'Event']);

        $this->getJson("/_test/owner/services/{$service->id}/edit")
            ->assertOk()
            ->assertJsonPath('data.service.service_names_array', ['Portrait', 'Event']);

        $this->getJson('/_test/owner/services')
            ->assertOk()
            ->assertJsonPath('data.0.service_names_array', ['Portrait', 'Event']);
    }

    private function createOwner(): UserModel
    {
        return UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Services',
            'last_name' => 'Owner',
            'email' => 'services-owner@example.com',
            'mobile_number' => '09170000002',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
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
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->text('service_name');
            $table->decimal('starting_from', 10, 2)->nullable();
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal');
            $table->text('description')->nullable();
            $table->string('status');
            $table->boolean('is_system')->default(false);
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal');
            $table->string('resource');
            $table->string('action');
            $table->string('permission_string');
            $table->text('description')->nullable();
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('studio_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });
    }
}
