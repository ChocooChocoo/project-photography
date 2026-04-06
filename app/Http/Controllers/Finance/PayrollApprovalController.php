<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdatePayrollApprovalRequest;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Handle finance payroll approval workflows.
 */
class PayrollApprovalController extends Controller
{
    /**
     * Display the finance payroll approval page.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $financeUser = $this->getAuthenticatedFinanceUser();

        if (!$this->canAccessPayrollApprovalPage($financeUser)) {
            return redirect()->route('studio-finance.dashboard')
                ->with('error', 'You do not have permission to access payroll approvals.');
        }

        $assignedStudioIds = $this->getAssignedStudioIds($financeUser->id);

        if ($assignedStudioIds->isEmpty()) {
            return redirect()->route('studio-finance.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $generatedPayrolls = GeneratedPayrollModel::with(['employee', 'studio', 'generator', 'reviewer'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 ELSE 2 END")
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get();

        $canApprovePayroll = $this->hasPayrollPermission($financeUser, 'approve');
        $canRejectPayroll = $this->hasPayrollPermission($financeUser, 'reject');

        return view('studio-finance.payroll-approvals', compact(
            'generatedPayrolls',
            'canApprovePayroll',
            'canRejectPayroll'
        ));
    }

    /**
     * Display the selected generated payroll details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();

            if (!$this->canAccessPayrollApprovalPage($financeUser)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view payroll approval details.',
                    'errors' => [],
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($financeUser->id);
            $generatedPayroll = GeneratedPayrollModel::with(['employee', 'studio', 'generator', 'payrollSetting', 'reviewer'])
                ->whereIn('studio_id', $assignedStudioIds)
                ->findOrFail($id);
            $attendanceSummary = $generatedPayroll->computation_summary['attendance'] ?? [];

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll approval details loaded successfully.',
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
                    'can_approve' => $this->hasPayrollPermission($financeUser, 'approve') && $generatedPayroll->status !== 'approved',
                    'can_reject' => $this->hasPayrollPermission($financeUser, 'reject') && $generatedPayroll->status !== 'approved',
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load finance payroll approval details.', [
                'exception' => $exception,
                'generated_payroll_id' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load payroll approval details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Update the payroll approval status.
     *
     * @param  \App\Http\Requests\Finance\UpdatePayrollApprovalRequest  $request
     * @param  string  $id
     * @param  string  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePayrollApprovalRequest $request, string $id, string $action): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $assignedStudioIds = $this->getAssignedStudioIds($financeUser->id);
            $generatedPayroll = GeneratedPayrollModel::whereIn('studio_id', $assignedStudioIds)->findOrFail($id);

            $validated = $request->validated();
            $normalizedAction = strtolower($action);

            if (!in_array($normalizedAction, ['approve', 'reject'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected payroll approval action is invalid.',
                    'errors' => [],
                ], 422);
            }

            if ($normalizedAction === 'approve') {
                $generatedPayroll->update([
                    'status' => 'approved',
                    'reviewed_by' => $financeUser->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payroll approved successfully.',
                    'data' => [
                        'payroll_id' => $generatedPayroll->id,
                        'status' => $generatedPayroll->status,
                        'status_display' => ucfirst($generatedPayroll->status),
                        'reviewed_by' => $financeUser->full_name,
                        'reviewed_at' => $generatedPayroll->reviewed_at?->format('F d, Y h:i A'),
                    ],
                ]);
            }

            $generatedPayroll->update([
                'status' => 'rejected',
                'reviewed_by' => $financeUser->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll rejected successfully.',
                'data' => [
                    'payroll_id' => $generatedPayroll->id,
                    'status' => $generatedPayroll->status,
                    'status_display' => ucfirst($generatedPayroll->status),
                    'reviewed_by' => $financeUser->full_name,
                    'reviewed_at' => $generatedPayroll->reviewed_at?->format('F d, Y h:i A'),
                    'rejection_reason' => $generatedPayroll->rejection_reason,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to update payroll approval status.', [
                'exception' => $exception,
                'generated_payroll_id' => $id,
                'action' => $action,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payroll approval status.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Get the authenticated finance user with loaded permissions.
     *
     * @return \App\Models\UserModel
     */
    private function getAuthenticatedFinanceUser(): UserModel
    {
        return UserModel::with('roles.permissions')->findOrFail(auth()->id());
    }

    /**
     * Get studios assigned to the current finance user.
     *
     * @param  int  $financeUserId
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getAssignedStudioIds(int $financeUserId)
    {
        $financeUser = UserModel::find($financeUserId);
        $studioIds = $financeUser ? $financeUser->getAssignedStudioIds('studio-finance') : collect();

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::where('user_id', $financeUserId)
                ->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $financeUserId)->pluck('id');
        }

        return $studioIds->unique()->values();
    }

    /**
     * Determine whether the finance user can access the approval page.
     *
     * @param  \App\Models\UserModel  $user
     * @return bool
     */
    private function canAccessPayrollApprovalPage(UserModel $user): bool
    {
        return $this->hasPayrollPermission($user, 'view')
            || $this->hasPayrollPermission($user, 'approve')
            || $this->hasPayrollPermission($user, 'reject');
    }

    /**
     * Check if the finance user has the required payroll permission.
     *
     * @param  \App\Models\UserModel  $user
     * @param  string  $action
     * @return bool
     */
    private function hasPayrollPermission(UserModel $user, string $action): bool
    {
        $permissionMap = [
            'view' => ['studio-finance.payroll.view', 'studio-finance.payroll.manage'],
            'approve' => ['studio-finance.payroll.approve', 'studio-finance.payroll.manage'],
            'reject' => ['studio-finance.payroll.reject', 'studio-finance.payroll.manage'],
        ];

        foreach ($permissionMap[$action] ?? [] as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the display label of a role.
     *
     * @param  string  $role
     * @return string
     */
    private function getRoleDisplay(string $role): string
    {
        $roles = [
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
        ];

        return $roles[$role] ?? ucfirst(str_replace('-', ' ', $role));
    }
}
