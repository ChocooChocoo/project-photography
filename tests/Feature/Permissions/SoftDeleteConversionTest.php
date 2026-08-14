<?php

namespace Tests\Feature\Permissions;

use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\StudioOwner\PermissionController;
use App\Http\Controllers\StudioOwner\RoleController;
use App\Http\Controllers\StudioOwner\ServicesController;
use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\SubscriptionPlanModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SoftDeleteConversionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::delete('/_test/roles/{id}', [RoleController::class, 'destroy']);
        Route::delete('/_test/permissions/{id}', [PermissionController::class, 'destroy']);
        Route::delete('/_test/services/{id}', [ServicesController::class, 'destroy']);
        Route::delete('/_test/categories/{id}', [CategoriesController::class, 'destroy']);
        Route::delete('/_test/locations/{id}', [LocationController::class, 'destroy']);
        Route::delete('/_test/plans/{id}', [SubscriptionController::class, 'destroy']);
        Route::delete('/_test/users/{id}', [UserController::class, 'deleteUser']);
    }

    public function test_role_delete_soft_deletes_and_allows_recreation(): void
    {
        $role = RoleModel::create([
            'name' => 'studio-hr-manager',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);

        $this->deleteJson("/_test/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_roles', ['id' => $role->id]);
        $this->assertSame(1, RoleModel::withTrashed()->count());
        $this->assertSame(0, RoleModel::count());

        $recreated = RoleModel::create([
            'name' => 'studio-hr-manager',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tbl_roles', ['id' => $recreated->id, 'name' => 'studio-hr-manager']);
    }

    public function test_permission_delete_soft_deletes_and_allows_recreation(): void
    {
        $permission = PermissionModel::create([
            'name' => 'studio-hr.attendance.view',
            'permission_string' => 'attendance:view',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);

        $this->deleteJson("/_test/permissions/{$permission->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_permissions', ['id' => $permission->id]);
        $this->assertSame(1, PermissionModel::withTrashed()->count());
        $this->assertSame(0, PermissionModel::count());

        $recreated = PermissionModel::create([
            'name' => 'studio-hr.attendance.view',
            'permission_string' => 'attendance:view',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tbl_permissions', ['id' => $recreated->id, 'name' => 'studio-hr.attendance.view']);
    }

    public function test_service_delete_soft_deletes_and_allows_recreation(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
        $category = CategoriesModel::create([
            'category_name' => 'Portraits',
            'status' => 'active',
        ]);
        $service = ServicesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'service_name' => ['Wedding'],
        ]);

        $this->actingAs($owner)
            ->deleteJson("/_test/services/{$service->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_services', ['id' => $service->id]);
        $this->assertSame(1, ServicesModel::withTrashed()->count());
        $this->assertSame(0, ServicesModel::where('studio_id', $studio->id)->count());

        $recreated = ServicesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'service_name' => ['Wedding'],
        ]);

        $this->assertDatabaseHas('tbl_services', ['id' => $recreated->id, 'studio_id' => $studio->id]);
    }

    public function test_category_delete_soft_deletes_and_allows_recreation(): void
    {
        $category = CategoriesModel::create([
            'category_name' => 'Portraits',
            'status' => 'active',
        ]);

        $this->deleteJson("/_test/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_categories', ['id' => $category->id]);
        $this->assertSame(1, CategoriesModel::withTrashed()->count());
        $this->assertSame(0, CategoriesModel::count());

        $recreated = CategoriesModel::create([
            'category_name' => 'Portraits',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tbl_categories', ['id' => $recreated->id, 'category_name' => 'Portraits']);
    }

    public function test_location_delete_soft_deletes_and_allows_recreation(): void
    {
        $location = LocationModel::create([
            'province' => 'cavite',
            'municipality' => 'Imus',
            'barangay' => json_encode(['Bayan Luma']),
            'zip_code' => '4103',
            'status' => 'active',
        ]);

        $this->deleteJson("/_test/locations/{$location->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_locations', ['id' => $location->id]);
        $this->assertSame(1, LocationModel::withTrashed()->count());
        $this->assertSame(0, LocationModel::count());

        $recreated = LocationModel::create([
            'province' => 'cavite',
            'municipality' => 'Imus',
            'barangay' => json_encode(['Bayan Luma']),
            'zip_code' => '4103',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tbl_locations', ['id' => $recreated->id, 'municipality' => 'Imus']);
    }

    public function test_subscription_plan_delete_soft_deletes_and_allows_recreation(): void
    {
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'plan_code' => 'STUDIO_BASIC_MONTHLY',
            'name' => 'Studio Basic',
            'price' => 999.00,
            'commission_rate' => 10.00,
            'features' => ['bookings'],
            'support_level' => 'basic',
            'status' => 'active',
        ]);

        $this->deleteJson("/_test/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_subscription_plans', ['id' => $plan->id]);
        $this->assertSame(1, SubscriptionPlanModel::withTrashed()->count());
        $this->assertSame(0, SubscriptionPlanModel::count());

        $recreated = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'plan_code' => 'STUDIO_BASIC_MONTHLY',
            'name' => 'Studio Basic',
            'price' => 999.00,
            'commission_rate' => 10.00,
            'features' => ['bookings'],
            'support_level' => 'basic',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tbl_subscription_plans', ['id' => $recreated->id, 'plan_code' => 'STUDIO_BASIC_MONTHLY']);
    }

    public function test_admin_user_delete_soft_deletes(): void
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive(UserModel::class), true)) {
            $this->markTestSkipped('UserModel soft deletes are delivered by the parallel users/studios agent.');
        }

        $user = $this->createUser('client', 'Doomed', 'doomed@example.com');

        $this->deleteJson("/_test/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('tbl_users', ['id' => $user->id]);
        $this->assertNull(UserModel::find($user->id));
        $this->assertSame(1, UserModel::withTrashed()->where('id', $user->id)->count());
    }

    private function createUser(string $role, string $firstName, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => $firstName,
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '0917'.substr(md5($email), 0, 7),
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
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
            $table->softDeletes();
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
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['name', 'deleted_at']);
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('portal', 50)->default('studio');
            $table->string('resource')->nullable();
            $table->string('action')->nullable();
            $table->string('permission_string')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['name', 'deleted_at']);
            $table->unique(['permission_string', 'deleted_at']);
        });
        Schema::create('tbl_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });
        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('studio_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'role_id', 'studio_id']);
        });
        Schema::create('tbl_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->text('service_name');
            $table->decimal('starting_from', 10, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['category_name', 'deleted_at']);
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province')->default('cavite');
            $table->string('municipality');
            $table->json('barangay');
            $table->string('zip_code');
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->json('features')->nullable();
            $table->string('support_level')->default('basic');
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['plan_code', 'deleted_at']);
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('status');
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_freelancer_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id');
            $table->foreignId('plan_id');
            $table->string('status');
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
    }
}
