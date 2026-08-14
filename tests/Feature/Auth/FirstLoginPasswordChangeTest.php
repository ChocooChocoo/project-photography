<?php

namespace Tests\Feature\Auth;

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

class FirstLoginPasswordChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_employee_created_by_owner_must_change_password_on_first_login(): void
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
            'first_name' => 'Jane',
            'middle_name' => 'D',
            'last_name' => 'Doe',
            'suffix' => 'Jr.',
            'email' => 'jane.doe@example.com',
            'mobile_number' => '09170000001',
            'role_id' => $role->id,
            'status' => 'active',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $employee = UserModel::where('email', 'jane.doe@example.com')->firstOrFail();
        $this->assertTrue($employee->must_change_password);
        $this->assertSame('Jr.', $employee->suffix);
    }

    public function test_employee_with_flag_is_gated_to_password_change(): void
    {
        $employee = $this->createEmployeeWithFlag();

        $this->actingAs($employee)
            ->get(route('studio-hr.dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_employee_with_flag_can_access_password_change_and_onboarding(): void
    {
        $employee = $this->createEmployeeWithFlag();

        $this->actingAs($employee)
            ->get(route('password.change'))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('onboarding'))
            ->assertOk();
    }

    public function test_logout_always_works_even_with_flag(): void
    {
        $employee = $this->createEmployeeWithFlag();

        $this->actingAs($employee)
            ->post(route('auth.logout'))
            ->assertRedirect(route('login'));
    }

    public function test_password_change_rejects_incorrect_current_password(): void
    {
        $employee = $this->createEmployeeWithFlag();

        $this->actingAs($employee)
            ->post(route('password.change.store'), [
                'current_password' => 'wrong-password',
                'new_password' => 'newsecret123',
                'new_password_confirmation' => 'newsecret123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($employee->fresh()->must_change_password);
    }

    public function test_password_change_requires_confirmation_and_minimum_length(): void
    {
        $employee = $this->createEmployeeWithFlag();

        $this->actingAs($employee)
            ->post(route('password.change.store'), [
                'current_password' => 'secret',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('new_password');

        $this->actingAs($employee)
            ->post(route('password.change.store'), [
                'current_password' => 'secret',
                'new_password' => 'newsecret123',
                'new_password_confirmation' => 'different123',
            ])
            ->assertSessionHasErrors('new_password');
    }

    public function test_password_change_clears_flag_and_new_password_reaches_dashboard(): void
    {
        $employee = $this->createEmployeeWithFlag();
        $this->grantPermission($employee, 'studio-hr', 'studio-hr.dashboard.view');

        $this->actingAs($employee)
            ->post(route('password.change.store'), [
                'current_password' => 'secret',
                'new_password' => 'newsecret123',
                'new_password_confirmation' => 'newsecret123',
            ])
            ->assertRedirect(route('studio-hr.dashboard'));

        $this->assertFalse($employee->fresh()->must_change_password);

        // Old password no longer works
        $this->postJson(route('auth.login.store'), [
            'email' => $employee->email,
            'password' => 'secret',
        ])->assertUnauthorized();

        // New password logs in and reaches the dashboard (gate cleared)
        $login = $this->postJson(route('auth.login.store'), [
            'email' => $employee->email,
            'password' => 'newsecret123',
        ]);

        $login->assertOk()->assertJsonPath('success', true);

        $this->get(route('studio-hr.dashboard'))
            ->assertOk();
    }

    public function test_employee_login_redirect_goes_to_password_change_after_real_login(): void
    {
        $employee = $this->createEmployeeWithFlag();
        UserModel::where('email', $employee->email)->update(['password' => Hash::make('knownpass123')]);

        $login = $this->postJson(route('auth.login.store'), [
            'email' => $employee->email,
            'password' => 'knownpass123',
        ]);

        $login->assertOk()->assertJsonPath('success', true);

        $this->get(route('studio-hr.dashboard'))
            ->assertRedirect(route('password.change'));
    }

    private function createEmployeeWithFlag(): UserModel
    {
        $owner = $this->createUser('owner', 'owner@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'verified',
        ]);
        $this->createSubscription($studio);

        $employee = $this->createUser('studio-hr', 'hr@example.com');
        $employee->update(['must_change_password' => true]);

        $role = RoleModel::create([
            'name' => 'studio-hr-manager',
            'portal' => 'studio-hr',
            'status' => 'active',
        ]);
        $employee->assignRole($role, $studio->id);

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
            'first_name' => 'Test',
            'last_name' => 'User',
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
        Schema::create('tbl_employee_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->foreignId('schedule_id')->nullable();
            $table->date('attendance_date');
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->string('check_in_status')->nullable();
            $table->string('check_out_status')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('undertime_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_generated_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_reference', 50)->unique();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->foreignId('payroll_setting_id');
            $table->foreignId('generated_by');
            $table->string('employee_type');
            $table->string('payroll_basis');
            $table->string('employee_role', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('attendance_days_present')->default(0);
            $table->unsignedInteger('attendance_days_absent')->default(0);
            $table->unsignedInteger('attendance_minutes_late')->default(0);
            $table->unsignedInteger('attendance_minutes_undertime')->default(0);
            $table->unsignedInteger('booking_count')->default(0);
            $table->decimal('attendance_amount', 12, 2)->default(0);
            $table->decimal('booking_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->json('deduction_breakdown')->nullable();
            $table->json('computation_summary')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }
}
