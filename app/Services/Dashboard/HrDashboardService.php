<?php

namespace App\Services\Dashboard;

use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Build analytics for the HR dashboard.
 */
class HrDashboardService extends BaseDashboardService
{
    /**
     * Build the HR dashboard payload.
     *
     * @param  \App\Models\UserModel  $user
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function build(UserModel $user, array $input = []): array
    {
        $filters = $this->resolveFilters($input);
        [$startDate, $endDate] = $this->getBounds($filters);
        $granularity = $this->getTrendGranularity($startDate, $endDate);
        $labels = $this->buildTrendLabels($startDate, $endDate, $granularity);
        $studioIds = $this->getAssignedStudioIds($user);

        $employeeIds = $this->getEmployeeIds($studioIds);
        $employees = UserModel::query()->whereIn('id', $employeeIds)->get();
        $attendance = EmployeeAttendanceModel::query()
            ->whereIn('studio_id', $studioIds)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $payrolls = GeneratedPayrollModel::query()
            ->whereIn('studio_id', $studioIds)
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->get();

        $attendanceTrend = $this->groupTrendValues(
            $attendance,
            fn (EmployeeAttendanceModel $record) => $record->attendance_date,
            fn (EmployeeAttendanceModel $record) => $record->check_in_time ? 1 : 0,
            $granularity
        );
        $lateTrend = $this->groupTrendValues(
            $attendance->where('check_in_status', 'LATE'),
            fn (EmployeeAttendanceModel $record) => $record->attendance_date,
            null,
            $granularity
        );

        $departmentLabels = ['HR', 'Finance', 'Photographers'];
        $departmentValues = [
            $employees->where('role', 'studio-hr')->count(),
            $employees->where('role', 'studio-finance')->count(),
            $employees->where('role', 'studio-photographer')->count(),
        ];

        $payrollSummaryRows = $payrolls
            ->groupBy('employee_role')
            ->map(function (Collection $group, string $role): array {
                return [
                    ucfirst(str_replace('-', ' ', $role)),
                    (string) $group->count(),
                    $this->formatCurrency((float) $group->sum('net_amount')),
                ];
            })
            ->values()
            ->all();

        $attendanceRate = $attendance->count() > 0
            ? ($attendance->whereNotNull('check_in_time')->count() / $attendance->count()) * 100
            : 0;

        return $this->makeDashboardPayload(
            $filters,
            [
                $this->makeKpi(
                    'employees',
                    'Total Employees',
                    (string) $employees->count(),
                    'ti ti-users',
                    'primary',
                    'Assigned Studios',
                    (string) $studioIds->count()
                ),
                $this->makeKpi(
                    'active_staff',
                    'Active Staff',
                    (string) $employees->where('status', 'active')->count(),
                    'ti ti-user-check',
                    'success',
                    'Inactive Staff',
                    (string) $employees->where('status', 'inactive')->count()
                ),
                $this->makeKpi(
                    'attendance_rate',
                    'Attendance Rate',
                    $this->formatPercentage($attendanceRate),
                    'ti ti-calendar-stats',
                    'info',
                    'Late Entries',
                    (string) $attendance->where('check_in_status', 'LATE')->count()
                ),
                $this->makeKpi(
                    'payroll_total',
                    'Payroll Total',
                    $this->formatCurrency((float) $payrolls->sum('net_amount')),
                    'ti ti-report-money',
                    'warning',
                    'Payroll Batches',
                    (string) $payrolls->count()
                ),
            ],
            [
                $this->makeChart('department_distribution', 'Department Distribution', 'donut', [
                    [
                        'name' => 'Employees',
                        'data' => $departmentValues,
                    ],
                ], $departmentLabels),
                $this->makeChart('attendance_trend', 'Attendance Trend', 'line', [
                    [
                        'name' => 'Attendance',
                        'data' => $this->normalizeTrendData($labels, $attendanceTrend),
                    ],
                ], $labels),
                $this->makeChart('late_trend', 'Late / Absentee Pressure', 'bar', [
                    [
                        'name' => 'Late Entries',
                        'data' => $this->normalizeTrendData($labels, $lateTrend),
                    ],
                ], $labels),
            ],
            [
                $this->makeTable(
                    'payroll_summary',
                    'Payroll Summary by Role',
                    ['Role', 'Payroll Count', 'Net Total'],
                    $payrollSummaryRows,
                    'No payroll records found for the selected range.'
                ),
            ],
            [
                'subtitle' => 'Workforce performance across assigned studios.',
                'assigned_studios' => $studioIds->all(),
            ]
        );
    }

    /**
     * Resolve assigned studio IDs using the existing RBAC fallback pattern.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getAssignedStudioIds(UserModel $user): Collection
    {
        $studioIds = $user->getAssignedStudioIds('studio-hr');

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::query()->where('user_id', $user->id)->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::query()->where('user_id', $user->id)->pluck('id');
        }

        return $studioIds->filter()->unique()->values();
    }

    /**
     * Resolve employee IDs for the current HR scope.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $studioIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getEmployeeIds(Collection $studioIds): Collection
    {
        return DB::table('tbl_user_roles')
            ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
            ->whereIn('tbl_user_roles.studio_id', $studioIds)
            ->whereIn('tbl_roles.name', [
                'studio-hr-manager',
                'studio-hr-staff',
                'studio-finance-manager',
                'studio-finance-staff',
                'studio-photographer',
            ])
            ->pluck('tbl_user_roles.user_id')
            ->unique()
            ->values();
    }
}
