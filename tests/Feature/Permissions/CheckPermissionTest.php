<?php

namespace Tests\Feature\Permissions;

use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::middleware('permission:owner.services.manage')
            ->get('/_test/rbac', fn () => response()->json(['ok' => true]));
    }

    public function test_user_with_permission_can_access(): void
    {
        $user = $this->createUser('owner');
        $this->grantPermission($user, null);

        $this->actingAs($user)
            ->getJson('/_test/rbac')
            ->assertOk();
    }

    public function test_user_without_permission_is_rejected_with_json(): void
    {
        $user = $this->createUser('client');

        $this->actingAs($user)
            ->getJson('/_test/rbac')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_user_without_permission_is_redirected_to_dashboard(): void
    {
        $user = $this->createUser('freelancer');

        $this->actingAs($user)
            ->get('/_test/rbac')
            ->assertRedirect(route('freelancer.dashboard'));
    }

    public function test_permission_is_scoped_to_assigned_studio(): void
    {
        $user = $this->createUser('owner');
        $this->grantPermission($user, 1);

        $this->actingAs($user)
            ->getJson('/_test/rbac?studio_id=1')
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/_test/rbac?studio_id=2')
            ->assertForbidden();
    }

    private function grantPermission(UserModel $user, ?int $studioId): void
    {
        $role = RoleModel::create([
            'name' => 'owner',
            'portal' => 'owner',
            'status' => 'active',
        ]);

        $permission = PermissionModel::create([
            'name' => 'owner.services.manage',
            'permission_string' => 'owner.services.manage',
            'portal' => 'owner',
            'status' => 'active',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id, ['studio_id' => $studioId]);
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Rbac',
            'last_name' => 'Test',
            'email' => $role.'@example.com',
            'mobile_number' => '09170000001',
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
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('permission_string')->nullable();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->softDeletes();$table->timestamps();
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
