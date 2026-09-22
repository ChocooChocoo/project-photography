<?php

namespace Tests\Feature\Permissions;

use App\Http\Controllers\StudioOwner\RoleController;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRolesAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::middleware('permission:owner.roles.manage')->group(function () {
            Route::get('/_test/user-roles', [RoleController::class, 'userRoles']);
            Route::post('/_test/user-roles', [RoleController::class, 'updateUserRoles']);
        });
    }

    public function test_user_roles_page_lists_studio_users_and_roles(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $financeRole = $this->createRole('studio-finance-manager', 'studio-finance');
        $photoRole = $this->createRole('studio-photographer', 'studio-photographer');

        $hrUser = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $this->attachEmployee($hrUser, $hrRole, $studio);

        $photoUser = $this->createUser('studio-photographer', 'Paolo', 'paolo@example.com');
        $this->attachEmployee($photoUser, $photoRole, $studio);
        $this->createPhotographerRecord($studio, $owner, $photoUser);

        $memberUser = $this->createUser('freelancer', 'Fiona', 'fiona@example.com');
        StudioMemberModel::create([
            'studio_id' => $studio->id,
            'freelancer_id' => $memberUser->id,
            'invited_by' => $owner->id,
            'status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->get('/_test/user-roles')
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('Paolo')
            ->assertSee('Fiona')
            ->assertSee('HR Manager')
            ->assertSee('Finance Manager')
            ->assertSee('Photographer')
            ->assertSee('name="user_ids[]"', false)
            ->assertSee('name="role_ids[]"', false);
    }

    public function test_update_user_roles_assigns_multiple_roles_to_one_user(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $financeRole = $this->createRole('studio-finance-manager', 'studio-finance');

        $hrPermission = PermissionModel::create([
            'name' => 'studio-hr.attendance.view',
            'permission_string' => 'studio-hr.attendance.view',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);
        $financePermission = PermissionModel::create([
            'name' => 'studio-finance.payroll.view',
            'permission_string' => 'studio-finance.payroll.view',
            'portal' => 'studio-finance',
            'status' => 'active',
        ]);
        $hrRole->permissions()->attach($hrPermission->id);
        $financeRole->permissions()->attach($financePermission->id);

        $employee = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $this->attachEmployee($employee, $hrRole, $studio);

        $this->actingAs($owner)
            ->from('/_test/user-roles')
            ->post('/_test/user-roles', [
                'user_ids' => [$employee->id],
                'role_ids' => [$hrRole->id, $financeRole->id],
            ])
            ->assertRedirect('/_test/user-roles')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tbl_user_roles', [
            'user_id' => $employee->id,
            'role_id' => $hrRole->id,
            'studio_id' => $studio->id,
        ]);
        $this->assertDatabaseHas('tbl_user_roles', [
            'user_id' => $employee->id,
            'role_id' => $financeRole->id,
            'studio_id' => $studio->id,
        ]);

        $employee->refresh();
        $this->assertTrue($employee->hasPermission('studio-hr.attendance.view', $studio->id));
        $this->assertTrue($employee->hasPermission('studio-hr.attendance.view'));
    }

    public function test_update_user_roles_grants_permissions_from_both_roles(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $financeRole = $this->createRole('studio-finance-manager', 'studio-finance');

        // One canonical identity per permission: portal.resource.action. The
        // stored string and the checked string use the same form.
        $hrPermission = PermissionModel::create([
            'name' => 'studio-hr.attendance.view',
            'permission_string' => 'studio-hr.attendance.view',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);
        $financePermission = PermissionModel::create([
            'name' => 'studio-finance.payroll.view',
            'permission_string' => 'studio-finance.payroll.view',
            'portal' => 'studio-finance',
            'status' => 'active',
        ]);
        $hrRole->permissions()->attach($hrPermission->id);
        $financeRole->permissions()->attach($financePermission->id);

        $employee = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $this->attachEmployee($employee, $hrRole, $studio);

        $this->actingAs($owner)
            ->post('/_test/user-roles', [
                'user_ids' => [$employee->id],
                'role_ids' => [$hrRole->id, $financeRole->id],
            ])
            ->assertSessionHas('success');

        $permissionStrings = $employee->refresh()->getAllPermissions($studio->id, null)
            ->pluck('permission_string')
            ->all();

        $this->assertContains('studio-hr.attendance.view', $permissionStrings);
        $this->assertContains('studio-finance.payroll.view', $permissionStrings);

        // The same canonical identity resolves on the permission check.
        $this->assertTrue($employee->hasPermission('studio-hr.attendance.view', $studio->id));
        $this->assertTrue($employee->hasPermission('studio-hr.attendance.view'));
    }

    public function test_view_roles_reports_a_permission_save_failure(): void
    {
        $html = view('owner.view-roles')->render();

        $this->assertStringNotContainsString(
            'Role details updated, but permissions may need review.',
            $html
        );
        $this->assertStringContainsString("contentType: 'application/json'", $html);
    }

    public function test_update_user_roles_sync_removes_unchecked_roles(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $financeRole = $this->createRole('studio-finance-manager', 'studio-finance');

        $employee = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $this->attachEmployee($employee, $hrRole, $studio);
        $employee->assignRole($financeRole, $studio->id);

        $this->actingAs($owner)
            ->post('/_test/user-roles', [
                'user_ids' => [$employee->id],
                'role_ids' => [$hrRole->id],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tbl_user_roles', [
            'user_id' => $employee->id,
            'role_id' => $hrRole->id,
            'studio_id' => $studio->id,
        ]);
        $this->assertDatabaseMissing('tbl_user_roles', [
            'user_id' => $employee->id,
            'role_id' => $financeRole->id,
            'studio_id' => $studio->id,
        ]);
    }

    public function test_update_user_roles_clears_all_roles_when_none_checked(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');

        $employee = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $this->attachEmployee($employee, $hrRole, $studio);

        $this->actingAs($owner)
            ->post('/_test/user-roles', [
                'user_ids' => [$employee->id],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tbl_user_roles', [
            'user_id' => $employee->id,
            'role_id' => $hrRole->id,
            'studio_id' => $studio->id,
        ]);
    }

    public function test_update_user_roles_rejects_roles_outside_studio_portals(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $clientRole = $this->createRole('client', 'client');

        $employee = $this->createUser('studio-hr', 'Ana', 'ana@example.com');
        $this->attachEmployee($employee, $hrRole, $studio);

        $this->actingAs($owner)
            ->from('/_test/user-roles')
            ->post('/_test/user-roles', [
                'user_ids' => [$employee->id],
                'role_ids' => [$hrRole->id, $clientRole->id],
            ])
            ->assertRedirect('/_test/user-roles')
            ->assertSessionHasErrors('role_ids');

        $this->assertDatabaseMissing('tbl_user_roles', [
            'user_id' => $employee->id,
            'role_id' => $clientRole->id,
            'studio_id' => $studio->id,
        ]);
    }

    public function test_update_user_roles_rejects_users_not_belonging_to_studio(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');
        $this->grantPermission($owner);

        $studio = $this->createStudio($owner);
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');

        $outsider = $this->createUser('studio-finance', 'Zoe', 'zoe@example.com');

        $this->actingAs($owner)
            ->from('/_test/user-roles')
            ->post('/_test/user-roles', [
                'user_ids' => [$outsider->id],
                'role_ids' => [$hrRole->id],
            ])
            ->assertRedirect('/_test/user-roles')
            ->assertSessionHasErrors('user_ids');

        $this->assertDatabaseMissing('tbl_user_roles', [
            'user_id' => $outsider->id,
            'role_id' => $hrRole->id,
            'studio_id' => $studio->id,
        ]);
    }

    public function test_view_roles_page_has_select_all_controls_per_category_group(): void
    {
        $owner = $this->createUser('owner', 'Owner', 'owner@example.com');

        $html = view('owner.view-roles')->render();

        $this->assertStringContainsString('Select All', $html);
        $this->assertStringContainsString('permission-select-all', $html);
        $this->assertStringContainsString('data-group', $html);
    }

    private function grantPermission(UserModel $user): void
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

    private function createStudio(UserModel $owner): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
    }

    private function attachEmployee(UserModel $user, RoleModel $role, StudiosModel $studio): void
    {
        $user->assignRole($role, $studio->id);
        \App\Models\StudioOwner\EmployeeScheduleModel::create([
            'user_id' => $user->id,
            'studio_id' => $studio->id,
            'operating_days' => ['monday'],
            'start_time' => '09:00',
            'end_time' => '18:00',
            'is_active' => true,
            'notes' => null,
        ]);
    }

    private function createPhotographerRecord(StudiosModel $studio, UserModel $owner, UserModel $photographer): void
    {
        StudioPhotographersModel::create([
            'studio_id' => $studio->id,
            'owner_id' => $owner->id,
            'photographer_id' => $photographer->id,
            'position' => 'Lead',
            'specialization' => null,
            'years_of_experience' => 3,
            'status' => 'active',
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
        Schema::create('tbl_studio_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('freelancer_id');
            $table->foreignId('invited_by');
            $table->string('status')->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studio_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('owner_id');
            $table->foreignId('photographer_id');
            $table->string('position')->nullable();
            $table->unsignedBigInteger('specialization')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studio_employee_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->json('operating_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
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
        Schema::create('tbl_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->text('service_name');
            $table->decimal('starting_from', 10, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
