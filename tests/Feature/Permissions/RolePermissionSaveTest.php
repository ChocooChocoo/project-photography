<?php

namespace Tests\Feature\Permissions;

use App\Http\Controllers\StudioOwner\RoleController;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression coverage for the owner role permission save path: the
 * subscription gate must not block a role write, the pivot rows must change,
 * the permission cache must clear, and the response must carry the saved
 * permission list.
 */
class RolePermissionSaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->clearPermissionCache();

        // The real owner group carries the owner guard, the subscription gate,
        // and the permission check. The route name keeps the production name so
        // the gate applies the same exemption.
        Route::middleware(['owner', 'subscription.access:manage', 'permission:owner.roles.manage'])
            ->put('/_test/owner/roles/{id}/permissions', [RoleController::class, 'updatePermissions'])
            ->name('owner.role.update-permissions');

        Route::middleware(['owner', 'subscription.access:manage', 'permission:owner.roles.manage'])
            ->put('/_test/owner/gated/{id}', fn () => response()->json(['success' => true]))
            ->name('_test.owner.gated');
    }

    public function test_owner_saves_two_permissions_and_sees_the_saved_state(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantRoleManagementPermission($owner);
        $studio = $this->createStudio($owner, 'First Studio');

        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $view = $this->createPermission('studio-hr.online-gallery.view', 'studio-hr', 'online-gallery', 'view');
        $manage = $this->createPermission('studio-hr.online-gallery.manage', 'studio-hr', 'online-gallery', 'manage');

        $employee = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $employee->roles()->attach($role->id, ['studio_id' => $studio->id]);

        // Prime the permission cache so the clear is observable.
        $employee->getAllPermissions($studio->id);

        $response = $this->actingAs($owner)->putJson("/_test/owner/roles/{$role->id}/permissions", [
            'permissions' => [$view->id, $manage->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.permissions_count', 2);

        $returnedIds = collect($response->json('data.permissions'))->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$view->id, $manage->id])->sort()->values()->all(), $returnedIds);

        $this->assertDatabaseHas('tbl_role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $view->id,
        ]);
        $this->assertDatabaseHas('tbl_role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $manage->id,
        ]);

        $this->assertSame([], $this->permissionCache());
    }

    public function test_multi_studio_owner_saves_role_permissions_without_forbidden(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantRoleManagementPermission($owner);
        $this->createStudio($owner, 'First Studio');
        $this->createStudio($owner, 'Second Studio');

        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $view = $this->createPermission('studio-hr.online-gallery.view', 'studio-hr', 'online-gallery', 'view');
        $manage = $this->createPermission('studio-hr.online-gallery.manage', 'studio-hr', 'online-gallery', 'manage');

        $response = $this->actingAs($owner)->putJson("/_test/owner/roles/{$role->id}/permissions", [
            'permissions' => [$view->id, $manage->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.permissions_count', 2);

        $this->assertDatabaseHas('tbl_role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $view->id,
        ]);
        $this->assertDatabaseHas('tbl_role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $manage->id,
        ]);
    }

    public function test_multi_studio_owner_is_still_blocked_on_a_gated_route(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantRoleManagementPermission($owner);
        $this->createStudio($owner, 'First Studio');
        $this->createStudio($owner, 'Second Studio');

        $this->actingAs($owner)
            ->putJson('/_test/owner/gated/1', [])
            ->assertForbidden();
    }

    public function test_owner_removes_all_permissions_with_an_empty_selection(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantRoleManagementPermission($owner);
        $this->createStudio($owner, 'First Studio');

        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $view = $this->createPermission('studio-hr.online-gallery.view', 'studio-hr', 'online-gallery', 'view');
        $manage = $this->createPermission('studio-hr.online-gallery.manage', 'studio-hr', 'online-gallery', 'manage');
        $role->permissions()->attach([$view->id, $manage->id]);

        $response = $this->actingAs($owner)->putJson("/_test/owner/roles/{$role->id}/permissions", [
            'permissions' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.permissions_count', 0);

        $this->assertDatabaseMissing('tbl_role_permissions', ['role_id' => $role->id]);
    }

    private function grantRoleManagementPermission(UserModel $user): void
    {
        $role = RoleModel::create([
            'name' => 'owner',
            'portal' => 'owner',
            'status' => 'active',
        ]);

        $permission = PermissionModel::create([
            'name' => 'owner.roles.manage',
            'permission_string' => 'owner.roles.manage',
            'portal' => 'owner',
            'status' => 'active',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id, ['studio_id' => null]);
    }

    private function createUser(string $role, string $firstName, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'Photographer',
            'first_name' => $firstName,
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '0917'.substr(md5($email), 0, 7),
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createRole(string $name, string $portal): RoleModel
    {
        return RoleModel::create([
            'name' => $name,
            'portal' => $portal,
            'status' => 'active',
        ]);
    }

    private function createPermission(string $string, string $portal, string $resource, string $action): PermissionModel
    {
        return PermissionModel::create([
            'name' => $string,
            'portal' => $portal,
            'resource' => $resource,
            'action' => $action,
            'permission_string' => $string,
            'status' => 'active',
        ]);
    }

    private function createStudio(UserModel $owner, string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => $name,
            'status' => 'verified',
        ]);
    }

    /**
     * Read the protected UserModel permission cache.
     */
    private function permissionCache(): array
    {
        $property = new \ReflectionProperty(UserModel::class, 'permissionCache');
        $property->setAccessible(true);

        return $property->getValue();
    }

    /**
     * UserModel caches permissions in a static array keyed by user id. Reset it
     * between tests so a grant from one test cannot leak into the next.
     */
    private function clearPermissionCache(): void
    {
        $property = new \ReflectionProperty(UserModel::class, 'permissionCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
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

        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('subscription_reference')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('status')->default('pending');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
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
    }
}
