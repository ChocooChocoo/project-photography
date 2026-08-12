<?php

namespace Tests\Feature\Permissions;

use App\Http\Controllers\Finance\PayrollApprovalController;
use App\Http\Controllers\StudioHR\EmployeeController;
use App\Http\Controllers\StudioPhotographer\AssignedBookingController;
use App\Models\BookingModel;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrossStudioIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::get('/_test/hr/employees/{id}', [EmployeeController::class, 'show']);
        Route::get('/_test/hr/employees', [EmployeeController::class, 'getEmployees']);
        Route::get('/_test/finance/payrolls/{id}', [PayrollApprovalController::class, 'show']);
        Route::get('/_test/photographer/assignments/{id}', [AssignedBookingController::class, 'getBookingDetails']);
    }

    public function test_hr_cannot_access_employees_of_other_studio(): void
    {
        $studioA = $this->createStudio('Studio A');
        $studioB = $this->createStudio('Studio B');

        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $photographerRole = $this->createRole('studio-photographer', 'studio-photographer');

        $hrA = $this->createUser('studio-hr', 'HR A', 'hr-a@example.com');
        $hrA->roles()->attach($hrRole->id, ['studio_id' => $studioA->id]);

        $empA = $this->createUser('studio-photographer', 'Emp A', 'emp-a@example.com');
        $empA->roles()->attach($photographerRole->id, ['studio_id' => $studioA->id]);
        $this->createSchedule($empA, $studioA);

        $empB = $this->createUser('studio-photographer', 'Emp B', 'emp-b@example.com');
        $empB->roles()->attach($photographerRole->id, ['studio_id' => $studioB->id]);
        $this->createSchedule($empB, $studioB);

        $this->actingAs($hrA)
            ->getJson("/_test/hr/employees/{$empA->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $empA->id);

        $this->actingAs($hrA)
            ->getJson("/_test/hr/employees/{$empB->id}")
            ->assertNotFound();

        $this->actingAs($hrA)
            ->getJson('/_test/hr/employees')
            ->assertOk()
            ->assertJsonFragment(['id' => $empA->id])
            ->assertJsonMissing(['id' => $empB->id]);
    }

    public function test_finance_cannot_access_payroll_of_other_studio(): void
    {
        $studioA = $this->createStudio('Studio A');
        $studioB = $this->createStudio('Studio B');

        $financeRole = $this->createRole('studio-finance-manager', 'studio-finance');
        $permission = PermissionModel::create([
            'name' => 'studio-finance.payroll.view',
            'portal' => 'studio-finance',
            'status' => 'active',
        ]);
        $financeRole->permissions()->attach($permission->id);

        $finA = $this->createUser('studio-finance', 'Fin A', 'fin-a@example.com');
        $finA->roles()->attach($financeRole->id, ['studio_id' => $studioA->id]);

        $employeeA = $this->createUser('studio-photographer', 'Pay Emp A', 'pay-emp-a@example.com');
        $employeeB = $this->createUser('studio-photographer', 'Pay Emp B', 'pay-emp-b@example.com');

        $payrollA = $this->createPayroll($studioA, $employeeA, $finA, 'PAYROLL-A');
        $payrollB = $this->createPayroll($studioB, $employeeB, $finA, 'PAYROLL-B');

        $this->actingAs($finA)
            ->getJson("/_test/finance/payrolls/{$payrollA->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->actingAs($finA)
            ->getJson("/_test/finance/payrolls/{$payrollB->id}")
            ->assertStatus(500)
            ->assertJsonPath('status', 'error');
    }

    public function test_photographer_cannot_access_other_photographers_assignment(): void
    {
        $studioA = $this->createStudio('Studio A');
        $studioB = $this->createStudio('Studio B');

        $photographerA = $this->createUser('studio-photographer', 'Photo A', 'photo-a@example.com');
        $photographerB = $this->createUser('studio-photographer', 'Photo B', 'photo-b@example.com');
        $client = $this->createUser('client', 'Client One', 'client-one@example.com');

        $bookingA = $this->createBooking($client, $studioA, 'BK-A');
        $bookingB = $this->createBooking($client, $studioB, 'BK-B');

        $assignmentA = BookingAssignedPhotographerModel::create([
            'booking_id' => $bookingA->id,
            'studio_id' => $studioA->id,
            'photographer_id' => $photographerA->id,
            'assigned_by' => $client->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $assignmentB = BookingAssignedPhotographerModel::create([
            'booking_id' => $bookingB->id,
            'studio_id' => $studioB->id,
            'photographer_id' => $photographerB->id,
            'assigned_by' => $client->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $this->actingAs($photographerA)
            ->getJson("/_test/photographer/assignments/{$assignmentA->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($photographerA)
            ->getJson("/_test/photographer/assignments/{$assignmentB->id}")
            ->assertStatus(500)
            ->assertJsonPath('success', false);
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

    private function createStudio(string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => 1,
            'studio_name' => $name,
            'status' => 'verified',
        ]);
    }

    private function createSchedule(UserModel $user, StudiosModel $studio): EmployeeScheduleModel
    {
        return EmployeeScheduleModel::create([
            'user_id' => $user->id,
            'studio_id' => $studio->id,
            'operating_days' => ['monday'],
            'start_time' => '09:00',
            'end_time' => '18:00',
            'is_active' => true,
            'notes' => null,
        ]);
    }

    private function createPayroll(StudiosModel $studio, UserModel $employee, UserModel $generator, string $reference): GeneratedPayrollModel
    {
        return GeneratedPayrollModel::create([
            'payroll_reference' => $reference,
            'user_id' => $employee->id,
            'studio_id' => $studio->id,
            'generated_by' => $generator->id,
            'employee_type' => 'studio_photographer',
            'payroll_basis' => 'attendance_only',
            'employee_role' => 'studio-photographer',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-15',
            'status' => 'pending',
            'generated_at' => now(),
        ]);
    }

    private function createBooking(UserModel $client, StudiosModel $studio, string $reference): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => $reference,
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => 'confirmed',
            'payment_status' => 'pending',
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
            $table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
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
            $table->timestamps();
        });
        Schema::create('tbl_studio_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('owner_id');
            $table->foreignId('photographer_id');
            $table->string('position')->nullable();
            $table->unsignedBigInteger('specialization')->nullable();
            $table->unsignedInteger('years_of_experience')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_employee_payroll', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('tbl_generated_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_reference', 50)->unique();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->foreignId('payroll_setting_id')->nullable();
            $table->foreignId('generated_by');
            $table->string('employee_type');
            $table->string('payroll_basis');
            $table->string('employee_role');
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
            $table->string('status');
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->foreignId('category_id')->nullable();
            $table->string('event_name');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location_type')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('studio_id');
            $table->foreignId('photographer_id');
            $table->foreignId('assigned_by');
            $table->string('status');
            $table->text('assignment_notes')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('response_deadline')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_type')->nullable();
            $table->string('package_name')->nullable();
            $table->decimal('package_price', 10, 2)->nullable();
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id')->nullable();
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->default(0);
            $table->boolean('online_gallery')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->string('payment_method');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->json('images')->nullable();
            $table->integer('total_photos')->nullable();
            $table->timestamps();
        });
    }
}
