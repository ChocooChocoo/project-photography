<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\GeneratePayrollRequest;
use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles payroll generation for HR users.
 */
class GeneratePayrollController extends Controller
{
    /**
     * Display the payroll generation page.
     */
    public function index()
    {
        $hrUser = $this->getAuthenticatedHrUser();

        if (!$this->hasPayrollPermission($hrUser, 'view')) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'You do not have permission to access payroll generation.');
        }

        $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
        $studios = StudiosModel::whereIn('id', $assignedStudioIds)
            ->whereIn('status', ['verified', 'active'])
            ->orderBy('studio_name')
            ->get();

        if ($studios->isEmpty()) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $canGenerate = $this->hasPayrollPermission($hrUser, 'create');
        $generatedPayrolls = GeneratedPayrollModel::with(['employee', 'studio', 'generator', 'reviewer'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get();

        return view('studio-hr.generate-payroll', compact('studios', 'canGenerate', 'generatedPayrolls'));
    }

    /**
     * Load employees that are eligible for payroll generation.
     */
    public function getEmployees(Request $request): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view payroll generation data.',
                    'errors' => [],
                ], 403);
            }

            $validated = $request->validate([
                'studio_id' => ['required', 'integer', 'exists:tbl_studios,id'],
                'employee_type' => ['required', 'in:regular_employee,studio_photographer'],
                'period_start' => ['required', 'date'],
                'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            ], [
                'studio_id.required' => 'Please select a studio first.',
                'employee_type.required' => 'Please choose an employee type filter.',
                'period_start.required' => 'Please provide the payroll period start date.',
                'period_end.required' => 'Please provide the payroll period end date.',
                'period_end.after_or_equal' => 'The payroll period end date must be on or after the start date.',
            ]);

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $studioId = (int) $validated['studio_id'];

            if (!$assignedStudioIds->contains($studioId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have access to the selected studio.',
                    'errors' => [],
                ], 403);
            }

            $employeeType = $validated['employee_type'];
            $periodStart = Carbon::parse($validated['period_start'])->startOfDay();
            $periodEnd = Carbon::parse($validated['period_end'])->endOfDay();
            $payrollBasis = $this->getPayrollBasisForEmployeeType($employeeType);
            $employeeRoles = $this->getRolesForEmployeeType($employeeType);

            $existingGeneratedUserIds = GeneratedPayrollModel::query()
                ->where('studio_id', $studioId)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->pluck('user_id')
                ->toArray();

            $employees = EmployeePayrollModel::with(['employee.roles'])
                ->where('studio_id', $studioId)
                ->where('is_active', true)
                ->where('payroll_basis', $payrollBasis)
                ->whereHas('employee', function ($query) use ($employeeRoles) {
                    $query->whereIn('role', $employeeRoles)
                        ->where('status', 'active');
                })
                ->whereNotIn('user_id', $existingGeneratedUserIds)
                ->orderBy('user_id')
                ->get()
                ->map(function (EmployeePayrollModel $payrollSetting) use ($periodStart, $periodEnd, $employeeType) {
                    $employee = $payrollSetting->employee;
                    $attendanceSummary = $this->getAttendanceMetrics($payrollSetting, $periodStart, $periodEnd);
                    $bookingSummary = $employeeType === 'studio_photographer'
                        ? $this->getBookingMetrics($payrollSetting, $periodStart, $periodEnd)
                        : [
                            'booking_count' => 0,
                            'booking_amount' => 0,
                            'completed_booking_total' => 0,
                        ];

                    return [
                        'id' => $employee->id,
                        'full_name' => $employee->full_name,
                        'email' => $employee->email,
                        'role' => $employee->role,
                        'role_display' => $employee->roles->first()->display_name ?? $this->getRoleDisplay($employee->role),
                        'payroll_setting_id' => $payrollSetting->id,
                        'payroll_basis' => $payrollSetting->payroll_basis,
                        'payroll_basis_display' => $payrollSetting->payroll_basis_display,
                        'daily_rate' => $payrollSetting->daily_rate,
                        'monthly_salary' => $payrollSetting->monthly_salary,
                        'hourly_rate' => $payrollSetting->hourly_rate,
                        'per_booking_rate' => $payrollSetting->per_booking_rate,
                        'booking_commission_percentage' => $payrollSetting->booking_commission_percentage,
                        'attendance_preview' => [
                            'days_present' => $attendanceSummary['attendance_days_present'],
                            'days_absent' => $attendanceSummary['attendance_days_absent'],
                            'approved_leave_days' => $attendanceSummary['approved_leave_days'],
                            'payable_days' => $attendanceSummary['payable_days'],
                            'late_minutes' => $attendanceSummary['attendance_minutes_late'],
                            'undertime_minutes' => $attendanceSummary['attendance_minutes_undertime'],
                            'worked_hours' => $attendanceSummary['worked_hours'],
                            'approved_overtime_hours' => $attendanceSummary['approved_overtime_hours'],
                            'regular_attendance_amount' => round((float) $attendanceSummary['regular_attendance_amount'], 2),
                            'overtime_amount' => round((float) $attendanceSummary['overtime_amount'], 2),
                            'attendance_amount' => round((float) $attendanceSummary['attendance_amount'], 2),
                        ],
                        'booking_preview' => [
                            'booking_count' => $bookingSummary['booking_count'],
                            'booking_amount' => round((float) $bookingSummary['booking_amount'], 2),
                        ],
                    ];
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'message' => $employees->isEmpty()
                    ? 'No eligible employees found for the selected filter and period.'
                    : 'Eligible employees loaded successfully.',
                'data' => $employees,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Exception $exception) {
            Log::error('Failed to load payroll generation employees.', [
                'exception' => $exception,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load employees for payroll generation.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Generate payroll records for selected employees.
     */
    public function store(GeneratePayrollRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'create')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to generate payroll.',
                    'errors' => [],
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $validated = $request->validated();
            $studioId = (int) $validated['studio_id'];

            if (!$assignedStudioIds->contains($studioId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have access to the selected studio.',
                    'errors' => [],
                ], 403);
            }

            $employeeType = $validated['employee_type'];
            $payrollBasis = $this->getPayrollBasisForEmployeeType($employeeType);
            $employeeRoles = $this->getRolesForEmployeeType($employeeType);
            $periodStart = Carbon::parse($validated['period_start'])->startOfDay();
            $periodEnd = Carbon::parse($validated['period_end'])->endOfDay();
            $employeeIds = collect($validated['employee_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $notes = $validated['notes'] ?? null;

            $payrollSettings = EmployeePayrollModel::with(['employee.roles'])
                ->where('studio_id', $studioId)
                ->where('is_active', true)
                ->where('payroll_basis', $payrollBasis)
                ->whereIn('user_id', $employeeIds)
                ->whereHas('employee', function ($query) use ($employeeRoles) {
                    $query->whereIn('role', $employeeRoles)
                        ->where('status', 'active');
                })
                ->get()
                ->keyBy('user_id');

            $generatedRecords = [];
            $skippedEmployees = [];

            foreach ($employeeIds as $employeeId) {
                $payrollSetting = $payrollSettings->get($employeeId);

                if (!$payrollSetting) {
                    $skippedEmployees[] = "Employee ID {$employeeId} has no active payroll settings for the selected filter.";
                    continue;
                }

                $alreadyGenerated = GeneratedPayrollModel::query()
                    ->where('studio_id', $studioId)
                    ->where('user_id', $employeeId)
                    ->whereDate('period_start', $periodStart->toDateString())
                    ->whereDate('period_end', $periodEnd->toDateString())
                    ->exists();

                if ($alreadyGenerated) {
                    $skippedEmployees[] = $payrollSetting->employee->full_name . ' already has generated payroll for this period.';
                    continue;
                }

                $computedPayroll = $this->buildGeneratedPayrollData(
                    $payrollSetting,
                    $employeeType,
                    $periodStart,
                    $periodEnd,
                    $hrUser->id,
                    $notes
                );

                $generatedRecords[] = GeneratedPayrollModel::create($computedPayroll);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => count($generatedRecords) > 0
                    ? 'Payroll generated successfully for the selected employees.'
                    : 'No payroll records were generated.',
                'data' => [
                    'generated_count' => count($generatedRecords),
                    'generated_payrolls' => collect($generatedRecords)->map(function (GeneratedPayrollModel $generatedPayroll) {
                        return [
                            'id' => $generatedPayroll->id,
                            'payroll_reference' => $generatedPayroll->payroll_reference,
                            'employee_name' => $generatedPayroll->employee->full_name ?? 'N/A',
                            'net_amount' => number_format((float) $generatedPayroll->net_amount, 2),
                        ];
                    })->values(),
                    'skipped' => $skippedEmployees,
                ],
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error('Failed to generate payroll.', [
                'exception' => $exception,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate payroll records.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display the selected generated payroll details.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view generated payroll details.',
                    'errors' => [],
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $generatedPayroll = GeneratedPayrollModel::with(['employee', 'studio', 'generator', 'payrollSetting', 'reviewer'])
                ->whereIn('studio_id', $assignedStudioIds)
                ->findOrFail($id);
            $attendanceSummary = $generatedPayroll->computation_summary['attendance'] ?? [];

            return response()->json([
                'status' => 'success',
                'message' => 'Generated payroll details loaded successfully.',
                'data' => [
                    'id' => $generatedPayroll->id,
                    'payroll_reference' => $generatedPayroll->payroll_reference,
                    'employee_name' => $generatedPayroll->employee->full_name ?? 'N/A',
                    'employee_email' => $generatedPayroll->employee->email ?? 'N/A',
                    'employee_role' => $this->getRoleDisplay($generatedPayroll->employee_role),
                    'employee_role_raw' => $generatedPayroll->employee_role,
                    'employee_photo' => $generatedPayroll->employee->profile_photo_url ?? asset('assets/images/users/user-3.jpg'),
                    'studio_name' => $generatedPayroll->studio->studio_name ?? 'N/A',
                    'payroll_basis' => $generatedPayroll->payroll_basis,
                    'payroll_basis_display' => $generatedPayroll->payroll_basis === 'booking_and_attendance'
                        ? 'Booking + Attendance'
                        : 'Attendance Only',
                    'employee_type' => $generatedPayroll->employee_type,
                    'employee_type_display' => $generatedPayroll->employee_type === 'studio_photographer'
                        ? 'Studio Photographer'
                        : 'Regular Employee',
                    'period_start' => $generatedPayroll->period_start?->format('F d, Y'),
                    'period_end' => $generatedPayroll->period_end?->format('F d, Y'),
                    'attendance_days_present' => $generatedPayroll->attendance_days_present,
                    'attendance_days_absent' => $generatedPayroll->attendance_days_absent,
                    'approved_leave_days' => (int) ($attendanceSummary['approved_leave_days'] ?? 0),
                    'payable_days' => (int) ($attendanceSummary['payable_days'] ?? $generatedPayroll->attendance_days_present),
                    'attendance_minutes_late' => $generatedPayroll->attendance_minutes_late,
                    'attendance_minutes_undertime' => $generatedPayroll->attendance_minutes_undertime,
                    'worked_hours' => number_format((float) ($attendanceSummary['worked_hours'] ?? 0), 2),
                    'approved_overtime_hours' => number_format((float) ($attendanceSummary['approved_overtime_hours'] ?? 0), 2),
                    'regular_attendance_amount' => number_format((float) ($attendanceSummary['regular_attendance_amount'] ?? $generatedPayroll->attendance_amount), 2),
                    'overtime_amount' => number_format((float) ($attendanceSummary['overtime_amount'] ?? 0), 2),
                    'booking_count' => $generatedPayroll->booking_count,
                    'attendance_amount' => number_format((float) $generatedPayroll->attendance_amount, 2),
                    'booking_amount' => number_format((float) $generatedPayroll->booking_amount, 2),
                    'gross_amount' => number_format((float) $generatedPayroll->gross_amount, 2),
                    'total_deductions' => number_format((float) $generatedPayroll->total_deductions, 2),
                    'net_amount' => number_format((float) $generatedPayroll->net_amount, 2),
                    'deduction_breakdown' => collect($generatedPayroll->deduction_breakdown ?? [])
                        ->mapWithKeys(function ($amount, $key) {
                            return [$key => number_format((float) $amount, 2)];
                        }),
                    'notes' => $generatedPayroll->notes ?: 'No remarks provided.',
                    'generated_at' => $generatedPayroll->generated_at?->format('F d, Y h:i A'),
                    'generated_by' => $generatedPayroll->generator->full_name ?? 'N/A',
                    'status' => $generatedPayroll->status,
                    'status_display' => ucfirst($generatedPayroll->status),
                    'reviewed_at' => $generatedPayroll->reviewed_at?->format('F d, Y h:i A') ?? 'Not reviewed yet.',
                    'reviewed_by' => $generatedPayroll->reviewer->full_name ?? 'Not reviewed yet.',
                    'rejection_reason' => $generatedPayroll->rejection_reason ?: 'No rejection reason provided.',
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load generated payroll details.', [
                'exception' => $exception,
                'generated_payroll_id' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load generated payroll details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Build the generated payroll payload.
     *
     * @return array<string, mixed>
     */
    private function buildGeneratedPayrollData(
        EmployeePayrollModel $payrollSetting,
        string $employeeType,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $generatedBy,
        ?string $notes
    ): array {
        $attendanceMetrics = $this->getAttendanceMetrics($payrollSetting, $periodStart, $periodEnd);
        $bookingMetrics = $employeeType === 'studio_photographer'
            ? $this->getBookingMetrics($payrollSetting, $periodStart, $periodEnd)
            : [
                'booking_count' => 0,
                'booking_amount' => 0,
                'completed_booking_total' => 0,
            ];

        $grossAmount = round((float) $attendanceMetrics['attendance_amount'] + (float) $bookingMetrics['booking_amount'], 2);

        $deductionBreakdown = $this->getDeductionBreakdown(
            $payrollSetting,
            $attendanceMetrics['attendance_days_absent'],
            $attendanceMetrics['attendance_minutes_late'],
            $attendanceMetrics['attendance_minutes_undertime']
        );

        $totalDeductions = round(array_sum($deductionBreakdown), 2);
        $netAmount = round(max($grossAmount - $totalDeductions, 0), 2);

        return [
            'payroll_reference' => $this->generatePayrollReference(),
            'user_id' => $payrollSetting->user_id,
            'studio_id' => $payrollSetting->studio_id,
            'payroll_setting_id' => $payrollSetting->id,
            'generated_by' => $generatedBy,
            'employee_type' => $employeeType,
            'payroll_basis' => $payrollSetting->payroll_basis,
            'employee_role' => $payrollSetting->employee->role,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'attendance_days_present' => $attendanceMetrics['attendance_days_present'],
            'attendance_days_absent' => $attendanceMetrics['attendance_days_absent'],
            'attendance_minutes_late' => $attendanceMetrics['attendance_minutes_late'],
            'attendance_minutes_undertime' => $attendanceMetrics['attendance_minutes_undertime'],
            'booking_count' => $bookingMetrics['booking_count'],
            'attendance_amount' => round((float) $attendanceMetrics['attendance_amount'], 2),
            'booking_amount' => round((float) $bookingMetrics['booking_amount'], 2),
            'gross_amount' => $grossAmount,
            'total_deductions' => $totalDeductions,
            'net_amount' => $netAmount,
            'deduction_breakdown' => $deductionBreakdown,
            'computation_summary' => [
                'attendance' => $attendanceMetrics,
                'booking' => $bookingMetrics,
                'gross_amount' => $grossAmount,
                'net_amount' => $netAmount,
            ],
            'notes' => $notes,
            'generated_at' => now(),
        ];
    }

    /**
     * Get attendance metrics for the payroll period.
     *
     * @return array<string, int|float>
     */
    private function getAttendanceMetrics(EmployeePayrollModel $payrollSetting, Carbon $periodStart, Carbon $periodEnd): array
    {
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $payrollSetting->user_id)
            ->where('studio_id', $payrollSetting->studio_id)
            ->where('is_active', true)
            ->first();

        $attendanceRecords = EmployeeAttendanceModel::query()
            ->where('user_id', $payrollSetting->user_id)
            ->where('studio_id', $payrollSetting->studio_id)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $presentDates = $attendanceRecords
            ->filter(fn (EmployeeAttendanceModel $attendance) => !is_null($attendance->check_in_time))
            ->pluck('attendance_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();
        $daysPresent = $presentDates->count();
        $approvedLeaveDates = $schedule
            ? $this->getApprovedLeaveDatesWithinPeriod($payrollSetting, $schedule, $periodStart, $periodEnd)
            : collect();
        $workBreakdown = $this->getAttendanceWorkBreakdown($payrollSetting, $attendanceRecords, $approvedLeaveDates);
        $approvedOvertimeHours = $workBreakdown['approved_overtime_hours'];
        $workedHours = $workBreakdown['regular_worked_hours'];
        $paidLeaveDays = $approvedLeaveDates->count();
        $payableDays = $daysPresent + $paidLeaveDays;
        $lateMinutes = (int) $attendanceRecords->sum('late_minutes');
        $undertimeMinutes = (int) $attendanceRecords->sum('undertime_minutes');
        $daysAbsent = $this->getAbsentDaysCount($payrollSetting, $periodStart, $periodEnd, $presentDates, $approvedLeaveDates);
        $regularAttendanceAmount = $this->getRegularAttendanceAmount(
            $payrollSetting,
            $schedule,
            $payableDays,
            $paidLeaveDays,
            $workedHours
        );
        $overtimeAmount = round($approvedOvertimeHours * $payrollSetting->calculateHourlyRate(), 2);
        $attendanceAmount = round($regularAttendanceAmount + $overtimeAmount, 2);

        return [
            'attendance_days_present' => $daysPresent,
            'attendance_days_absent' => $daysAbsent,
            'approved_leave_days' => $paidLeaveDays,
            'payable_days' => $payableDays,
            'attendance_minutes_late' => $lateMinutes,
            'attendance_minutes_undertime' => $undertimeMinutes,
            'worked_hours' => $workedHours,
            'approved_overtime_hours' => round($approvedOvertimeHours, 2),
            'regular_attendance_amount' => round($regularAttendanceAmount, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'attendance_amount' => $attendanceAmount,
        ];
    }

    /**
     * Get booking metrics for photographers within the payroll period.
     *
     * @return array<string, int|float>
     */
    private function getBookingMetrics(EmployeePayrollModel $payrollSetting, Carbon $periodStart, Carbon $periodEnd): array
    {
        $assignments = BookingAssignedPhotographerModel::with('booking')
            ->where('photographer_id', $payrollSetting->user_id)
            ->where('studio_id', $payrollSetting->studio_id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$periodStart->toDateTimeString(), $periodEnd->toDateTimeString()])
            ->get();

        $bookingCount = $assignments->count();
        $completedBookingTotal = round((float) $assignments->sum(function (BookingAssignedPhotographerModel $assignment) {
            return (float) ($assignment->booking->total_amount ?? 0);
        }), 2);

        $perBookingAmount = $bookingCount * (float) ($payrollSetting->per_booking_rate ?? 0);
        $commissionAmount = $completedBookingTotal * ((float) ($payrollSetting->booking_commission_percentage ?? 0) / 100);

        return [
            'booking_count' => $bookingCount,
            'completed_booking_total' => round($completedBookingTotal, 2),
            'booking_amount' => round($perBookingAmount + $commissionAmount, 2),
        ];
    }

    /**
     * Get the payroll deductions breakdown.
     *
     * @return array<string, float>
     */
    private function getDeductionBreakdown(
        EmployeePayrollModel $payrollSetting,
        int $daysAbsent,
        int $lateMinutes,
        int $undertimeMinutes
    ): array {
        $absenceDeduction = 0.00;
        $dailyRate = $payrollSetting->calculateDailyRate();

        if ($daysAbsent > 0) {
            if ($payrollSetting->absent_deduction_method === 'deduct_fixed_amount') {
                $absenceDeduction = (float) ($payrollSetting->absent_fixed_deduction ?? 0) * $daysAbsent;
            } elseif (
                $payrollSetting->absent_deduction_method === 'deduct_percentage' &&
                !empty($payrollSetting->absent_percentage_deduction)
            ) {
                $absenceDeduction = ($dailyRate * $daysAbsent) * ((float) $payrollSetting->absent_percentage_deduction / 100);
            } else {
                $absenceDeduction = $daysAbsent * (
                    (float) ($payrollSetting->absence_deduction_per_day ?? 0) > 0
                        ? (float) $payrollSetting->absence_deduction_per_day
                        : $dailyRate
                );
            }
        }

        $lateDeduction = $lateMinutes * (float) ($payrollSetting->late_deduction_per_minute ?? 0);
        $undertimeDeduction = ($undertimeMinutes / 60) * (float) ($payrollSetting->undertime_deduction_per_hour ?? 0);

        return [
            'sss_deduction' => round((float) ($payrollSetting->sss_deduction ?? 0), 2),
            'phic_deduction' => round((float) ($payrollSetting->phic_deduction ?? 0), 2),
            'hdmf_deduction' => round((float) ($payrollSetting->hdmf_deduction ?? 0), 2),
            'tax_withholding' => round((float) ($payrollSetting->tax_withholding ?? 0), 2),
            'sss_loan_deduction' => round((float) ($payrollSetting->sss_loan_deduction ?? 0), 2),
            'hdmf_loan_deduction' => round((float) ($payrollSetting->hdmf_loan_deduction ?? 0), 2),
            'other_deductions' => round((float) ($payrollSetting->other_deductions ?? 0), 2),
            'absence_deduction' => round($absenceDeduction, 2),
            'late_deduction' => round($lateDeduction, 2),
            'undertime_deduction' => round($undertimeDeduction, 2),
        ];
    }

    /**
     * Compute attendance-based amount.
     */
    private function getAttendanceAmount(EmployeePayrollModel $payrollSetting, int $daysPresent, float $workedHours): float
    {
        if ((float) ($payrollSetting->daily_rate ?? 0) > 0) {
            return round($daysPresent * (float) $payrollSetting->daily_rate, 2);
        }

        if ((float) ($payrollSetting->hourly_rate ?? 0) > 0) {
            return round($workedHours * (float) $payrollSetting->hourly_rate, 2);
        }

        if ((float) ($payrollSetting->monthly_salary ?? 0) > 0) {
            return round($daysPresent * $payrollSetting->calculateDailyRate(), 2);
        }

        return 0.00;
    }

    /**
     * Count absent days based on the employee schedule.
     */
    private function getAbsentDaysCount(
        EmployeePayrollModel $payrollSetting,
        Carbon $periodStart,
        Carbon $periodEnd,
        $presentDates,
        $approvedLeaveDates = null
    ): int {
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $payrollSetting->user_id)
            ->where('studio_id', $payrollSetting->studio_id)
            ->where('is_active', true)
            ->first();

        if (!$schedule || empty($schedule->operating_days)) {
            return 0;
        }

        $approvedLeaveDates = collect($approvedLeaveDates ?? $this->getApprovedLeaveDatesWithinPeriod(
            $payrollSetting,
            $schedule,
            $periodStart,
            $periodEnd
        ));
        $presentDateValues = collect($presentDates)->unique()->values();
        $coveredDates = $presentDateValues->merge($approvedLeaveDates)->unique();
        $workingDays = 0;
        $period = CarbonPeriod::create($periodStart->copy()->startOfDay(), $periodEnd->copy()->startOfDay());

        foreach ($period as $date) {
            if ($schedule->worksOnDay(strtolower($date->format('l')))) {
                $workingDays++;
            }
        }

        return max($workingDays - $coveredDates->count(), 0);
    }

    /**
     * Get approved leave dates inside the payroll period that should not count as absences.
     */
    private function getApprovedLeaveDatesWithinPeriod(
        EmployeePayrollModel $payrollSetting,
        EmployeeScheduleModel $schedule,
        Carbon $periodStart,
        Carbon $periodEnd
    ) {
        $approvedLeaves = LeaveRequestModel::query()
            ->where('user_id', $payrollSetting->user_id)
            ->where('studio_id', $payrollSetting->studio_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->whereDate('end_date', '>=', $periodStart->toDateString())
            ->get();

        $leaveDates = collect();

        foreach ($approvedLeaves as $leaveRequest) {
            $rangeStart = Carbon::parse($leaveRequest->start_date)->greaterThan($periodStart)
                ? Carbon::parse($leaveRequest->start_date)
                : $periodStart->copy();
            $rangeEnd = Carbon::parse($leaveRequest->end_date)->lessThan($periodEnd)
                ? Carbon::parse($leaveRequest->end_date)
                : $periodEnd->copy();

            foreach (CarbonPeriod::create($rangeStart->startOfDay(), $rangeEnd->startOfDay()) as $leaveDate) {
                if ($schedule->worksOnDay(strtolower($leaveDate->format('l')))) {
                    $leaveDates->push($leaveDate->toDateString());
                }
            }
        }

        return $leaveDates->unique()->values();
    }

    /**
     * Get regular worked hours for payroll after removing approved overtime from the base bucket.
     */
    private function getAttendanceWorkBreakdown(
        EmployeePayrollModel $payrollSetting,
        $attendanceRecords,
        $approvedLeaveDates
    ): array {
        $approvedLeaveDateValues = collect($approvedLeaveDates)->map(fn ($date) => Carbon::parse($date)->toDateString())->unique();
        $approvedOvertimeRequests = OvertimeRequestModel::query()
            ->where('user_id', $payrollSetting->user_id)
            ->where('studio_id', $payrollSetting->studio_id)
            ->where('status', 'approved')
            ->whereIn('overtime_date', $attendanceRecords->pluck('attendance_date')->map(
                fn ($date) => Carbon::parse($date)->toDateString()
            )->unique()->values())
            ->get()
            ->keyBy(fn (OvertimeRequestModel $overtimeRequest) => $overtimeRequest->overtime_date?->toDateString());

        $overtimeMinutes = 0;
        $regularMinutes = 0;

        foreach ($attendanceRecords as $attendanceRecord) {
            $attendanceDate = $attendanceRecord->attendance_date?->toDateString();

            if (
                !$attendanceDate ||
                $approvedLeaveDateValues->contains($attendanceDate) ||
                !$attendanceRecord->check_in_time ||
                !$attendanceRecord->check_out_time
            ) {
                continue;
            }

            $countedCheckOut = $attendanceRecord->check_out_time->copy();
            $overtimeForRecord = 0;
            $approvedOvertime = $approvedOvertimeRequests->get($attendanceDate);

            if ($approvedOvertime && !empty($attendanceRecord->scheduled_end_time)) {
                $scheduledEnd = Carbon::parse($attendanceDate . ' ' . $attendanceRecord->scheduled_end_time, 'Asia/Manila');

                if ($attendanceRecord->check_out_time->gt($scheduledEnd)) {
                    $effectiveCutoff = $scheduledEnd->copy()->addMinutes((int) round(((float) $approvedOvertime->total_hours) * 60));
                    $countedCheckOut = $attendanceRecord->check_out_time->gt($effectiveCutoff)
                        ? $effectiveCutoff
                        : $attendanceRecord->check_out_time->copy();
                    $overtimeStart = $attendanceRecord->check_in_time->gt($scheduledEnd)
                        ? $attendanceRecord->check_in_time->copy()
                        : $scheduledEnd;

                    if ($countedCheckOut->gt($overtimeStart)) {
                        $overtimeForRecord = $overtimeStart->diffInMinutes($countedCheckOut);
                    }
                }
            }

            $countedMinutes = $attendanceRecord->check_in_time->diffInMinutes($countedCheckOut);
            $regularMinutes += max($countedMinutes - $overtimeForRecord, 0);
            $overtimeMinutes += $overtimeForRecord;
        }

        return [
            'regular_worked_hours' => round($regularMinutes / 60, 2),
            'approved_overtime_hours' => round($overtimeMinutes / 60, 2),
        ];
    }

    /**
     * Compute the regular attendance amount before overtime is added.
     */
    private function getRegularAttendanceAmount(
        EmployeePayrollModel $payrollSetting,
        ?EmployeeScheduleModel $schedule,
        int $payableDays,
        int $paidLeaveDays,
        float $workedHours
    ): float {
        if ((float) ($payrollSetting->daily_rate ?? 0) > 0) {
            return round($payableDays * (float) $payrollSetting->daily_rate, 2);
        }

        if ((float) ($payrollSetting->hourly_rate ?? 0) > 0) {
            $paidLeaveHours = $paidLeaveDays * $this->getScheduledDailyHours($schedule);
            return round(($workedHours + $paidLeaveHours) * (float) $payrollSetting->hourly_rate, 2);
        }

        if ((float) ($payrollSetting->monthly_salary ?? 0) > 0) {
            return round($payableDays * $payrollSetting->calculateDailyRate(), 2);
        }

        return 0.00;
    }

    /**
     * Get the scheduled hours of one workday.
     */
    private function getScheduledDailyHours(?EmployeeScheduleModel $schedule): float
    {
        if (!$schedule || !$schedule->start_time || !$schedule->end_time) {
            return 0.00;
        }

        $startTime = $schedule->start_time instanceof Carbon
            ? $schedule->start_time->copy()
            : Carbon::parse($schedule->start_time, 'Asia/Manila');
        $endTime = $schedule->end_time instanceof Carbon
            ? $schedule->end_time->copy()
            : Carbon::parse($schedule->end_time, 'Asia/Manila');

        if ($endTime->lessThanOrEqualTo($startTime)) {
            return 0.00;
        }

        return round($startTime->diffInMinutes($endTime) / 60, 2);
    }

    /**
     * Generate a unique payroll reference.
     */
    private function generatePayrollReference(): string
    {
        do {
            $reference = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (GeneratedPayrollModel::where('payroll_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the user roles for the selected employee type.
     *
     * @return array<int, string>
     */
    private function getRolesForEmployeeType(string $employeeType): array
    {
        return $employeeType === 'studio_photographer'
            ? ['studio-photographer']
            : ['studio-hr', 'studio-finance'];
    }

    /**
     * Get the payroll basis for the selected employee type.
     */
    private function getPayrollBasisForEmployeeType(string $employeeType): string
    {
        return $employeeType === 'studio_photographer'
            ? 'booking_and_attendance'
            : 'attendance_only';
    }

    /**
     * Get the display label of a role.
     */
    private function getRoleDisplay(string $role): string
    {
        return RoleModel::getFriendlyRoleName($role);
    }

    /**
     * Get the authenticated HR user with role permissions.
     */
    private function getAuthenticatedHrUser(): UserModel
    {
        return UserModel::with('roles.permissions')->findOrFail(auth()->id());
    }

    /**
     * Get studios assigned to the current HR user.
     */
    private function getAssignedStudioIds(int $hrId)
    {
        $studioIds = EmployeeScheduleModel::where('user_id', $hrId)
            ->pluck('studio_id');

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $hrId)->pluck('id');
        }

        return $studioIds->unique()->values();
    }

    /**
     * Check if the HR user has payroll permissions.
     */
    private function hasPayrollPermission(UserModel $user, string $action): bool
    {
        $permissionMap = [
            'view' => ['studio-hr.payroll.view', 'studio-hr.payroll.manage', 'studio-hr.generate-payroll.manage'],
            'create' => ['studio-hr.payroll.create', 'studio-hr.payroll.manage', 'studio-hr.generate-payroll.manage'],
        ];

        foreach ($permissionMap[$action] ?? [] as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
