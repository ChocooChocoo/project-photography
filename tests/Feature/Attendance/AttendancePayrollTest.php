<?php

namespace Tests\Feature\Attendance;

use App\Http\Controllers\Finance\PayrollApprovalController;
use App\Http\Controllers\StudioHR\EmployeeAttendanceController;
use App\Http\Controllers\StudioHR\GeneratePayrollController;
use App\Http\Controllers\StudioHR\LeaveRequestController as HrLeaveRequestController;
use App\Http\Controllers\StudioOwner\LeaveRequestController as OwnerLeaveRequestController;
use App\Models\LeaveRequestModel;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendancePayrollTest extends TestCase
{
    private StudiosModel $studio;
    private UserModel $owner;
    private UserModel $hr;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow(Carbon::parse('2026-08-10 08:55:00', 'Asia/Manila'));

        $this->owner = $this->createUser('owner', 'owner@example.com');
        $this->studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Test Studio',
            'status' => 'active',
            'attendance_latitude' => 14.5,
            'attendance_longitude' => 121.0,
            'attendance_radius_meters' => 100,
        ]);
        $this->hr = $this->createUser('studio-hr', 'hr@example.com');

        Route::post('/_test/hr/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn']);
        Route::post('/_test/hr/attendance/check-out', [EmployeeAttendanceController::class, 'checkOut']);
        Route::post('/_test/hr/leave', [HrLeaveRequestController::class, 'store']);
        Route::post('/_test/hr/leave/{id}/cancel', [HrLeaveRequestController::class, 'cancel']);
        Route::post('/_test/owner/leave/{id}/process/{action}', [OwnerLeaveRequestController::class, 'process']);
        Route::post('/_test/hr/payroll/generate', [GeneratePayrollController::class, 'store']);
        Route::post('/_test/finance/payroll/{id}/process/{action}', [PayrollApprovalController::class, 'update']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attendance_check_in_out_tracks_late_undertime_and_geofence(): void
    {
        $this->grantPermission($this->hr, 'studio-hr', 'studio-hr.attendance.view', $this->studio->id);
        $this->createSchedule($this->hr->id, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
        Auth::setUser($this->hr);

        Carbon::setTestNow(Carbon::parse('2026-08-10 08:55:00', 'Asia/Manila'));
        $this->postJson('/_test/hr/attendance/check-in', [
            'latitude' => 14.50001,
            'longitude' => 121.00001,
        ])->assertOk()
            ->assertJsonPath('status', 'ON_TIME')
            ->assertJsonPath('late_minutes', 0)
            ->assertJsonPath('location_validation.status', 'WITHIN_GEOFENCE');

        $dayOne = EmployeeAttendanceModel::where('user_id', $this->hr->id)
            ->whereDate('attendance_date', '2026-08-10')->firstOrFail();
        $this->assertSame('ON_TIME', $dayOne->check_in_status);
        $this->assertSame(0, $dayOne->late_minutes);
        $this->assertSame('WITHIN_GEOFENCE', $dayOne->check_in_location_status);
        $this->assertSame('09:00:00', $dayOne->scheduled_start_time);
        $this->assertSame('18:00:00', $dayOne->scheduled_end_time);

        Carbon::setTestNow(Carbon::parse('2026-08-10 17:30:00', 'Asia/Manila'));
        $this->postJson('/_test/hr/attendance/check-out', [
            'attendance_id' => $dayOne->id,
            'latitude' => 14.50001,
            'longitude' => 121.00001,
        ])->assertOk()
            ->assertJsonPath('status', 'UNDERTIME')
            ->assertJsonPath('undertime_minutes', 30)
            ->assertJsonPath('attendance.check_out_status', 'UNDERTIME');

        $this->assertSame('UNDERTIME', $dayOne->fresh()->check_out_status);
        $this->assertSame(30, $dayOne->fresh()->undertime_minutes);

        Carbon::setTestNow(Carbon::parse('2026-08-11 09:15:59', 'Asia/Manila'));
        $this->postJson('/_test/hr/attendance/check-in', [
            'latitude' => 14.50001,
            'longitude' => 121.00001,
        ])->assertOk()
            ->assertJsonPath('status', 'LATE')
            ->assertJsonPath('late_minutes', 15);

        $dayTwo = EmployeeAttendanceModel::where('user_id', $this->hr->id)
            ->whereDate('attendance_date', '2026-08-11')->firstOrFail();
        $this->assertSame('LATE', $dayTwo->check_in_status);
        $this->assertSame(15, $dayTwo->late_minutes);

        Carbon::setTestNow(Carbon::parse('2026-08-11 18:30:00', 'Asia/Manila'));
        $this->postJson('/_test/hr/attendance/check-out', [
            'attendance_id' => $dayTwo->id,
            'latitude' => 14.50001,
            'longitude' => 121.00001,
        ])->assertOk()
            ->assertJsonPath('status', 'ON_TIME')
            ->assertJsonPath('undertime_minutes', 0);

        $this->assertSame('ON_TIME', $dayTwo->fresh()->check_out_status);
        $this->assertSame(0, $dayTwo->fresh()->undertime_minutes);

        Carbon::setTestNow(Carbon::parse('2026-08-12 09:00:00', 'Asia/Manila'));
        $this->postJson('/_test/hr/attendance/check-in', [
            'latitude' => 14.6,
            'longitude' => 121.1,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('location_validation.status', 'OUTSIDE_GEOFENCE')
            ->assertJsonPath('location_validation.allowed', false);

        $this->assertSame(0, EmployeeAttendanceModel::where('user_id', $this->hr->id)
            ->whereDate('attendance_date', '2026-08-12')->count());
    }

    public function test_leave_request_create_approve_and_cancel_flow(): void
    {
        $this->grantPermission($this->hr, 'studio-hr', 'studio-hr.leave-requests.manage', $this->studio->id);
        $this->createSchedule($this->hr->id, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
        Auth::setUser($this->hr);

        $this->postJson('/_test/hr/leave', [
            'leave_type' => 'vacation_leave',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-21',
            'reason' => 'Family vacation trip.',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'pending');

        $approved = LeaveRequestModel::where('user_id', $this->hr->id)->firstOrFail();
        $this->assertSame('pending', $approved->status);
        $this->assertSame('2.00', $approved->total_days);

        Auth::setUser($this->owner);
        $this->postJson("/_test/owner/leave/{$approved->id}/process/approve", ['action' => 'approve'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame($this->owner->id, $approved->fresh()->approved_by);
        $this->assertNotNull($approved->fresh()->approved_at);

        Auth::setUser($this->hr);
        $this->postJson('/_test/hr/leave', [
            'leave_type' => 'sick_leave',
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-03',
            'reason' => 'Hospital appointment visit.',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'pending');

        $cancelled = LeaveRequestModel::where('user_id', $this->hr->id)
            ->orderByDesc('id')->firstOrFail();
        $this->postJson("/_test/hr/leave/{$cancelled->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertNotNull($cancelled->fresh()->cancelled_at);
    }

    public function test_payroll_generation_and_finance_approval(): void
    {
        $this->grantPermission($this->hr, 'studio-hr', 'studio-hr.generate-payroll.manage', $this->studio->id);
        $this->createSchedule($this->hr->id, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);

        $employee = $this->createUser('studio-hr', 'employee@example.com');
        $this->createSchedule($employee->id, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);

        $finance = $this->createUser('studio-finance', 'finance@example.com');
        $this->grantPermission($finance, 'studio-finance', 'studio-finance.payroll.approve', $this->studio->id);

        EmployeePayrollModel::create([
            'user_id' => $employee->id,
            'studio_id' => $this->studio->id,
            'created_by' => $this->owner->id,
            'payroll_basis' => 'attendance_only',
            'daily_rate' => 800,
            'is_active' => true,
        ]);

        $presentDates = [
            '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07',
            '2026-08-08', '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13',
        ];
        foreach ($presentDates as $index => $date) {
            $late = in_array($index, [1, 7], true);
            EmployeeAttendanceModel::create([
                'user_id' => $employee->id,
                'studio_id' => $this->studio->id,
                'attendance_date' => $date,
                'scheduled_start_time' => '09:00:00',
                'scheduled_end_time' => '18:00:00',
                'check_in_time' => Carbon::parse($date . ' 08:55:00', 'Asia/Manila'),
                'check_out_time' => Carbon::parse($date . ' 18:05:00', 'Asia/Manila'),
                'check_in_status' => $late ? 'LATE' : 'ON_TIME',
                'check_out_status' => 'ON_TIME',
                'late_minutes' => $late ? 15 : 0,
                'undertime_minutes' => 0,
            ]);
        }

        Auth::setUser($this->hr);
        $this->postJson('/_test/hr/payroll/generate', [
            'studio_id' => $this->studio->id,
            'employee_type' => 'regular_employee',
            'period_start' => '2026-08-03',
            'period_end' => '2026-08-15',
            'employee_ids' => [$employee->id],
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.generated_count', 1);

        $payroll = GeneratedPayrollModel::where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('pending', $payroll->status);
        $this->assertSame(10, $payroll->attendance_days_present);
        $this->assertSame(2, $payroll->attendance_days_absent);
        $this->assertSame(30, $payroll->attendance_minutes_late);
        $this->assertSame(0, $payroll->attendance_minutes_undertime);
        $this->assertSame('8000.00', $payroll->gross_amount);
        $this->assertSame('1600.00', $payroll->total_deductions);
        $this->assertSame('6400.00', $payroll->net_amount);
        $this->assertSame($this->hr->id, $payroll->generated_by);
        $this->assertSame($this->studio->id, $payroll->studio_id);
        $this->assertSame('2026-08-03', $payroll->period_start->toDateString());
        $this->assertSame('2026-08-15', $payroll->period_end->toDateString());

        Auth::setUser($finance);
        $this->postJson("/_test/finance/payroll/{$payroll->id}/process/approve", ['action' => 'approve'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('approved', $payroll->fresh()->status);
        $this->assertSame($finance->id, $payroll->fresh()->reviewed_by);
        $this->assertNotNull($payroll->fresh()->reviewed_at);
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
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createSchedule(int $userId, array $days): EmployeeScheduleModel
    {
        return EmployeeScheduleModel::create([
            'user_id' => $userId,
            'studio_id' => $this->studio->id,
            'operating_days' => $days,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);
    }

    private function grantPermission(UserModel $user, string $portal, string $permission, int $studioId): void
    {
        $role = RoleModel::create([
            'name' => $portal,
            'portal' => $portal,
            'status' => 'active',
        ]);
        $permissionRow = PermissionModel::create([
            'name' => $permission,
            'portal' => $portal,
            'permission_string' => $permission,
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
            $table->decimal('attendance_latitude', 10, 7)->nullable();
            $table->decimal('attendance_longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_meters')->default(100);
            
            $table->softDeletes();$table->timestamps();
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
            $table->string('permission_string', 150)->nullable();
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
        Schema::create('tbl_studio_employee_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->json('operating_days');
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('18:00:00');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->softDeletes();$table->timestamps();
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
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_in_distance_meters')->nullable();
            $table->string('check_in_location_status')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_out_distance_meters')->nullable();
            $table->string('check_out_location_status')->nullable();
            $table->string('check_in_ip')->nullable();
            $table->string('check_out_ip')->nullable();
            $table->text('check_in_user_agent')->nullable();
            $table->text('check_out_user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->unique();
            $table->foreignId('studio_id');
            $table->foreignId('user_id');
            $table->string('leave_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 2)->default(1.00);
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tbl_overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->nullable();
            $table->foreignId('studio_id');
            $table->foreignId('user_id');
            $table->date('overtime_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tbl_employee_payroll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->foreignId('created_by');
            $table->string('payroll_basis')->default('attendance_only');
            $table->decimal('daily_rate', 10, 2)->nullable();
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('per_booking_rate', 10, 2)->nullable();
            $table->decimal('booking_commission_percentage', 5, 2)->nullable();
            $table->decimal('sss_deduction', 10, 2)->default(0.00);
            $table->decimal('phic_deduction', 10, 2)->default(0.00);
            $table->decimal('hdmf_deduction', 10, 2)->default(0.00);
            $table->decimal('tax_withholding', 10, 2)->default(0.00);
            $table->decimal('sss_loan_deduction', 10, 2)->default(0.00);
            $table->decimal('hdmf_loan_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions', 10, 2)->default(0.00);
            $table->decimal('absence_deduction_per_day', 10, 2)->nullable();
            $table->decimal('undertime_deduction_per_hour', 10, 2)->nullable();
            $table->integer('late_grace_period_minutes')->default(15);
            $table->decimal('late_deduction_per_minute', 10, 2)->nullable();
            $table->string('absent_deduction_method')->default('deduct_daily_rate');
            $table->decimal('absent_fixed_deduction', 10, 2)->nullable();
            $table->decimal('absent_percentage_deduction', 5, 2)->nullable();
            $table->boolean('paid_holidays')->default(true);
            $table->string('payment_schedule')->default('semi_monthly');
            $table->integer('payday_1')->nullable();
            $table->integer('payday_2')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
