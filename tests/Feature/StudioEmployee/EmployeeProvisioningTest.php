<?php

namespace Tests\Feature\StudioEmployee;

use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeProvisioningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_owner_store_persists_suffix_and_forces_password_change(): void
    {
        Mail::fake();

        $owner = $this->createUser('owner', 'owner@example.com');

        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
        $this->createSubscription($studio);

        $this->grantPermission($owner, 'owner', 'manage_employees', $studio->id);
        $this->actingAs($owner);

        $role = RoleModel::create([
            'name' => 'studio-hr-manager',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('owner.employee.store'), [
            'studio_id' => $studio->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'suffix' => 'III',
            'email' => 'maria.santos@example.com',
            'mobile_number' => '09170000001',
            'role_id' => $role->id,
            'status' => 'active',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $employee = UserModel::where('email', 'maria.santos@example.com')->firstOrFail();
        $this->assertSame('III', $employee->suffix);
        $this->assertTrue($employee->must_change_password);
    }

    public function test_hr_store_sets_must_change_password_flag(): void
    {
        Mail::fake();

        $owner = $this->createUser('owner', 'owner@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
        $this->createSubscription($studio);

        $hr = $this->createUser('studio-hr', 'hr@example.com');
        $this->grantPermission($hr, 'studio-hr', 'create_employee', $studio->id);
        $this->actingAs($hr);

        $role = RoleModel::create([
            'name' => 'studio-finance-manager',
            'portal' => 'studio-finance',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('studio-hr.employee.store'), [
            'studio_id' => $studio->id,
            'first_name' => 'Pedro',
            'last_name' => 'Cruz',
            'suffix' => 'Sr.',
            'email' => 'pedro.cruz@example.com',
            'mobile_number' => '09170000001',
            'role_id' => $role->id,
            'status' => 'active',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $employee = UserModel::where('email', 'pedro.cruz@example.com')->firstOrFail();
        $this->assertSame('Sr.', $employee->suffix);
        $this->assertTrue($employee->must_change_password);
    }

    public function test_owner_destroy_soft_deletes_employee(): void
    {
        $owner = $this->createUser('owner', 'owner@example.com');

        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
        $this->createSubscription($studio);

        $this->grantPermission($owner, 'owner', 'manage_employees', $studio->id);
        $this->actingAs($owner);

        $employee = $this->createAssignedEmployee($studio, 'studio-hr-manager', 'delete-me@example.com');
        $employee->update(['password' => Hash::make('knownpass123')]);

        $response = $this->deleteJson(route('owner.employee.destroy', $employee->id), [
            'studio_id' => $studio->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('tbl_users', ['id' => $employee->id]);
        $this->assertNotNull(UserModel::withTrashed()->find($employee->id)->deleted_at);

        // Soft-deleted employee can no longer log in
        $this->postJson(route('auth.login.store'), [
            'email' => $employee->email,
            'password' => 'knownpass123',
        ])->assertUnauthorized();
    }

    public function test_hr_destroy_soft_deletes_employee(): void
    {
        $owner = $this->createUser('owner', 'owner@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
        $this->createSubscription($studio);

        $hr = $this->createUser('studio-hr', 'hr@example.com');
        $this->grantPermission($hr, 'studio-hr', 'delete_employee', $studio->id);
        $this->actingAs($hr);

        $employee = $this->createAssignedEmployee($studio, 'studio-hr-manager', 'hr-delete-me@example.com');

        $response = $this->deleteJson(route('studio-hr.employee.destroy', $employee->id), [
            'studio_id' => $studio->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('tbl_users', ['id' => $employee->id]);
        $this->assertNotNull(UserModel::withTrashed()->find($employee->id)->deleted_at);
    }

    private function createAssignedEmployee(StudiosModel $studio, string $roleName, string $email): UserModel
    {
        $employee = $this->createUser('studio-hr', $email);

        $role = RoleModel::create([
            'name' => $roleName,
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);
        $employee->assignRole($role, $studio->id);

        EmployeeScheduleModel::create([
            'user_id' => $employee->id,
            'studio_id' => $studio->id,
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);

        return $employee;
    }

    private function createSubscription(StudiosModel $studio): StudioPlanModel
    {
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'plan_code' => 'PLAN-' . str()->upper(str()->random(8)),
            'name' => 'Basic Plan',
            'price' => 590,
            'commission_rate' => 5,
            'trial_days' => 0,
            'priority_level' => 1,
            'status' => 'active',
        ]);

        return StudioPlanModel::create([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-' . str()->upper(str()->random(10)),
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'next_billing_date' => now()->addDays(20)->toDateString(),
            'amount_paid' => 590,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Provision',
            'last_name' => 'Test',
            'email' => $email,
            'mobile_number' => '09170000001',
            'password' => Hash::make('secret'),
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function grantPermission(UserModel $user, string $portal, string $permission, ?int $studioId = null): void
    {
        $role = RoleModel::create([
            'name' => $portal,
            'portal' => $portal,
            'status' => 'active',
        ]);
        $permissionRow = PermissionModel::create([
            'name' => $permission,
            'portal' => $portal,
            'status' => 'active',
        ]);

        $role->permissions()->attach($permissionRow->id);
        $user->assignRole($role, $studioId);
    }

    private function createSchema(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->string('suffix')->nullable();
            $table->string('org_role')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('token_expiry')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->date('permit_expiry_date')->nullable();
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->string('display_name', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
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
        Schema::create('tbl_studio_employee_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->json('operating_days');
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('18:00:00');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('priority_level')->default(0);
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status');
            $table->string('status');
            $table->timestamps();
        });
    }
}
