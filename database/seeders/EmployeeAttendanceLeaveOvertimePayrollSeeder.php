<?php

namespace Database\Seeders;

use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class EmployeeAttendanceLeaveOvertimePayrollSeeder extends Seeder
{
    /**
     * Attendance seeding month for payroll testing.
     */
    private const TARGET_YEAR = 2026;

    /**
     * Attendance seeding month for payroll testing.
     */
    private const TARGET_MONTH = 3;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodStart = Carbon::create(self::TARGET_YEAR, self::TARGET_MONTH, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $hrApprover = UserModel::query()
            ->where('role', 'studio-hr')
            ->where('status', 'active')
            ->orderBy('id')
            ->first();
        $ownerApprover = UserModel::query()
            ->where('role', 'owner')
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        $employeeSchedules = EmployeeScheduleModel::with(['user', 'studio'])
            ->where('is_active', true)
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
                    ->where('status', 'active');
            })
            ->orderBy('user_id')
            ->get();

        if ($employeeSchedules->isEmpty()) {
            $this->command?->warn('No active employee schedules found. Payroll request seeder skipped.');
            return;
        }

        foreach ($employeeSchedules as $schedule) {
            $user = $schedule->user;
            $studio = $schedule->studio;

            if (!$user || !$studio) {
                continue;
            }

            $workingDates = $this->getWorkingDates($schedule, $periodStart, $periodEnd);

            if (count($workingDates) < 6) {
                $this->command?->warn("Not enough working dates found for {$user->email}. Seeder skipped for this employee.");
                continue;
            }

            $scenarioDates = [
                'approved_overtime' => $workingDates[0],
                'approved_leave' => $workingDates[1],
                'pending_overtime' => $workingDates[2],
                'pending_leave' => $workingDates[3],
                'rejected_overtime' => $workingDates[4],
                'rejected_leave' => $workingDates[5],
            ];

            foreach ($workingDates as $workingDayIndex => $attendanceDate) {
                if ($attendanceDate->isSameDay($scenarioDates['approved_leave'])) {
                    EmployeeAttendanceModel::query()
                        ->where('user_id', $user->id)
                        ->whereDate('attendance_date', $attendanceDate->toDateString())
                        ->delete();

                    continue;
                }

                $attendancePayload = $this->buildAttendancePayload($schedule, $attendanceDate, $workingDayIndex, (int) $user->id);

                if ($attendanceDate->isSameDay($scenarioDates['approved_overtime'])) {
                    $attendancePayload = $this->buildApprovedOvertimeAttendancePayload(
                        $schedule,
                        $attendanceDate,
                        (int) $user->id
                    );
                }

                EmployeeAttendanceModel::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'attendance_date' => $attendanceDate->toDateString(),
                    ],
                    [
                        'studio_id' => $studio->id,
                        'schedule_id' => $schedule->id,
                        'scheduled_start_time' => $attendancePayload['scheduled_start_time'],
                        'scheduled_end_time' => $attendancePayload['scheduled_end_time'],
                        'check_in_time' => $attendancePayload['check_in_time'],
                        'check_out_time' => $attendancePayload['check_out_time'],
                        'check_in_image' => null,
                        'check_out_image' => null,
                        'check_in_status' => $attendancePayload['check_in_status'],
                        'check_out_status' => $attendancePayload['check_out_status'],
                        'late_minutes' => $attendancePayload['late_minutes'],
                        'undertime_minutes' => $attendancePayload['undertime_minutes'],
                        'check_in_ip' => '10.10.1.' . (20 + ($user->id % 150)),
                        'check_out_ip' => '10.10.1.' . (20 + ($user->id % 150)),
                        'check_in_user_agent' => 'Seeded payroll attendance test record',
                        'check_out_user_agent' => 'Seeded payroll attendance test record',
                        'notes' => $attendancePayload['notes'],
                    ]
                );
            }

            $this->seedLeaveRequests($schedule, $scenarioDates, $hrApprover, $ownerApprover);
            $this->seedOvertimeRequests($schedule, $scenarioDates, $hrApprover, $ownerApprover);

            $this->command?->info("Seeded payroll test attendance and requests: {$user->email}");
        }
    }

    /**
     * Build all working dates in the target month for the employee schedule.
     *
     * @return array<int, Carbon>
     */
    private function getWorkingDates(EmployeeScheduleModel $schedule, Carbon $periodStart, Carbon $periodEnd): array
    {
        $workingDates = [];
        $period = CarbonPeriod::create($periodStart->copy(), $periodEnd->copy());

        foreach ($period as $date) {
            if ($schedule->worksOnDay(strtolower($date->format('l')))) {
                $workingDates[] = $date->copy();
            }
        }

        return $workingDates;
    }

    /**
     * Create the leave request scenarios for one employee.
     *
     * @param  array<string, Carbon>  $scenarioDates
     */
    private function seedLeaveRequests(
        EmployeeScheduleModel $schedule,
        array $scenarioDates,
        ?UserModel $hrApprover,
        ?UserModel $ownerApprover
    ): void {
        $user = $schedule->user;
        $studio = $schedule->studio;

        if (!$user || !$studio) {
            return;
        }

        $leaveApprover = $user->role === 'studio-hr' ? $ownerApprover : $hrApprover;
        $leaveTypes = [
            'approved' => 'vacation_leave',
            'pending' => 'sick_leave',
            'rejected' => 'emergency_leave',
        ];

        $this->upsertLeaveRequest(
            $schedule,
            'approved',
            $scenarioDates['approved_leave'],
            $leaveTypes['approved'],
            $leaveApprover,
            'Approved seeded leave request for payroll testing.'
        );
        $this->upsertLeaveRequest(
            $schedule,
            'pending',
            $scenarioDates['pending_leave'],
            $leaveTypes['pending'],
            null,
            'Pending seeded leave request for payroll testing.'
        );
        $this->upsertLeaveRequest(
            $schedule,
            'rejected',
            $scenarioDates['rejected_leave'],
            $leaveTypes['rejected'],
            $leaveApprover,
            'Rejected seeded leave request for payroll testing.'
        );
    }

    /**
     * Create the overtime request scenarios for one employee.
     *
     * @param  array<string, Carbon>  $scenarioDates
     */
    private function seedOvertimeRequests(
        EmployeeScheduleModel $schedule,
        array $scenarioDates,
        ?UserModel $hrApprover,
        ?UserModel $ownerApprover
    ): void {
        $user = $schedule->user;

        if (!$user) {
            return;
        }

        $overtimeApprover = $user->role === 'studio-hr' ? $ownerApprover : $hrApprover;

        $this->upsertOvertimeRequest(
            $schedule,
            'approved',
            $scenarioDates['approved_overtime'],
            3.0,
            $overtimeApprover,
            'Approved seeded overtime request for payroll testing.'
        );
        $this->upsertOvertimeRequest(
            $schedule,
            'pending',
            $scenarioDates['pending_overtime'],
            2.0,
            null,
            'Pending seeded overtime request for payroll testing.'
        );
        $this->upsertOvertimeRequest(
            $schedule,
            'rejected',
            $scenarioDates['rejected_overtime'],
            2.5,
            $overtimeApprover,
            'Rejected seeded overtime request for payroll testing.'
        );
    }

    /**
     * Insert or update one leave request scenario.
     */
    private function upsertLeaveRequest(
        EmployeeScheduleModel $schedule,
        string $status,
        Carbon $leaveDate,
        string $leaveType,
        ?UserModel $actor,
        string $reason
    ): void {
        $user = $schedule->user;
        $studio = $schedule->studio;

        if (!$user || !$studio) {
            return;
        }

        $createdAt = $leaveDate->copy()->subDays(3)->setTime(9, 0);
        $payload = [
            'studio_id' => $studio->id,
            'user_id' => $user->id,
            'leave_type' => $leaveType,
            'start_date' => $leaveDate->toDateString(),
            'end_date' => $leaveDate->toDateString(),
            'total_days' => 1.00,
            'reason' => $reason,
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? 'Seeded rejection reason for payroll testing.' : null,
            'approved_by' => $status === 'approved' ? $actor?->id : null,
            'approved_at' => $status === 'approved' ? $createdAt->copy()->addDay()->toDateTimeString() : null,
            'rejected_by' => $status === 'rejected' ? $actor?->id : null,
            'rejected_at' => $status === 'rejected' ? $createdAt->copy()->addDay()->toDateTimeString() : null,
            'cancelled_at' => null,
            'created_at' => $createdAt->toDateTimeString(),
            'updated_at' => $createdAt->copy()->addDay()->toDateTimeString(),
            'deleted_at' => null,
        ];

        LeaveRequestModel::updateOrCreate(
            [
                'request_reference' => sprintf(
                    'SEED-LR-%d-%s-%s',
                    $user->id,
                    strtoupper($status),
                    $leaveDate->format('Ymd')
                ),
            ],
            $payload
        );
    }

    /**
     * Insert or update one overtime request scenario.
     */
    private function upsertOvertimeRequest(
        EmployeeScheduleModel $schedule,
        string $status,
        Carbon $overtimeDate,
        float $totalHours,
        ?UserModel $actor,
        string $reason
    ): void {
        $user = $schedule->user;
        $studio = $schedule->studio;

        if (!$user || !$studio) {
            return;
        }

        $scheduledEndTime = $this->normalizeScheduleTime($schedule->end_time, '18:00:00');
        $startTime = Carbon::parse($overtimeDate->toDateString() . ' ' . $scheduledEndTime, 'Asia/Manila');
        $endTime = $startTime->copy()->addMinutes((int) round($totalHours * 60));
        $createdAt = $overtimeDate->copy()->subDays(2)->setTime(10, 30);

        OvertimeRequestModel::updateOrCreate(
            [
                'request_reference' => sprintf(
                    'SEED-OT-%d-%s-%s',
                    $user->id,
                    strtoupper($status),
                    $overtimeDate->format('Ymd')
                ),
            ],
            [
                'studio_id' => $studio->id,
                'user_id' => $user->id,
                'overtime_date' => $overtimeDate->toDateString(),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'total_hours' => $totalHours,
                'reason' => $reason,
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'Seeded rejection reason for payroll testing.' : null,
                'approved_by' => $status === 'approved' ? $actor?->id : null,
                'approved_at' => $status === 'approved' ? $createdAt->copy()->addDay()->toDateTimeString() : null,
                'rejected_by' => $status === 'rejected' ? $actor?->id : null,
                'rejected_at' => $status === 'rejected' ? $createdAt->copy()->addDay()->toDateTimeString() : null,
                'cancelled_at' => null,
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->copy()->addDay()->toDateTimeString(),
                'deleted_at' => null,
            ]
        );
    }

    /**
     * Build a deterministic attendance row for one working day.
     *
     * @return array<string, mixed>
     */
    private function buildAttendancePayload(
        EmployeeScheduleModel $schedule,
        Carbon $attendanceDate,
        int $workingDayIndex,
        int $userId
    ): array {
        $scheduledStartTime = $this->normalizeScheduleTime($schedule->start_time, '08:00:00');
        $scheduledEndTime = $this->normalizeScheduleTime($schedule->end_time, '18:00:00');

        $scheduledStart = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledStartTime, 'Asia/Manila');
        $scheduledEnd = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledEndTime, 'Asia/Manila');

        $isLate = (($workingDayIndex + $userId) % 5) === 0;
        $isUndertime = (($workingDayIndex + $userId) % 7) === 0;
        $lateMinutes = $isLate ? 10 + (($workingDayIndex + $userId) % 16) : 0;
        $undertimeMinutes = $isUndertime ? 15 + (($workingDayIndex + $userId) % 21) : 0;
        $earlyArrivalMinutes = 6 + (($workingDayIndex + $userId) % 8);
        $regularOvertimeMinutes = $isUndertime ? 0 : 8 + (($workingDayIndex + $userId) % 15);

        $checkInTime = $isLate
            ? $scheduledStart->copy()->addMinutes($lateMinutes)
            : $scheduledStart->copy()->subMinutes($earlyArrivalMinutes);
        $checkOutTime = $isUndertime
            ? $scheduledEnd->copy()->subMinutes($undertimeMinutes)
            : $scheduledEnd->copy()->addMinutes($regularOvertimeMinutes);

        return [
            'scheduled_start_time' => $scheduledStart->format('H:i:s'),
            'scheduled_end_time' => $scheduledEnd->format('H:i:s'),
            'check_in_time' => $checkInTime->toDateTimeString(),
            'check_out_time' => $checkOutTime->toDateTimeString(),
            'check_in_status' => $isLate ? 'LATE' : 'ON_TIME',
            'check_out_status' => $isUndertime ? 'UNDERTIME' : 'ON_TIME',
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'notes' => 'Seeded March 2026 payroll attendance record.',
        ];
    }

    /**
     * Build the approved overtime attendance row so payroll can test capped overtime logic.
     *
     * @return array<string, mixed>
     */
    private function buildApprovedOvertimeAttendancePayload(
        EmployeeScheduleModel $schedule,
        Carbon $attendanceDate,
        int $userId
    ): array {
        $scheduledStartTime = $this->normalizeScheduleTime($schedule->start_time, '08:00:00');
        $scheduledEndTime = $this->normalizeScheduleTime($schedule->end_time, '18:00:00');
        $scheduledStart = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledStartTime, 'Asia/Manila');
        $scheduledEnd = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledEndTime, 'Asia/Manila');
        $checkInTime = (($userId % 3) === 0)
            ? $scheduledStart->copy()->addMinutes(12)
            : $scheduledStart->copy()->subMinutes(9);
        $actualCheckoutOffsetHours = ($userId % 2) === 0 ? 4 : 2;
        $checkOutTime = $scheduledEnd->copy()->addHours($actualCheckoutOffsetHours);
        $lateMinutes = max($scheduledStart->diffInMinutes($checkInTime, false), 0);

        return [
            'scheduled_start_time' => $scheduledStart->format('H:i:s'),
            'scheduled_end_time' => $scheduledEnd->format('H:i:s'),
            'check_in_time' => $checkInTime->toDateTimeString(),
            'check_out_time' => $checkOutTime->toDateTimeString(),
            'check_in_status' => $lateMinutes > 0 ? 'LATE' : 'ON_TIME',
            'check_out_status' => 'ON_TIME',
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => 0,
            'notes' => 'Seeded approved overtime attendance record for payroll testing.',
        ];
    }

    /**
     * Normalize schedule time values from model casts.
     */
    private function normalizeScheduleTime(mixed $value, string $fallback): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->format('H:i:s');
        }

        return $fallback;
    }
}
