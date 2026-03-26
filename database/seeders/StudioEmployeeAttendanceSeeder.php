<?php

namespace Database\Seeders;

use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class StudioEmployeeAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monthStart = Carbon::create(2026, 3, 1, 0, 0, 0, 'Asia/Manila')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $hrSchedules = EmployeeScheduleModel::with(['user', 'studio'])
            ->where('is_active', true)
            ->whereHas('user', function ($query) {
                $query->where('role', 'studio-hr');
            })
            ->get();

        if ($hrSchedules->isEmpty()) {
            $this->command?->warn('No active HR employee schedules found. Attendance seeder skipped.');
            return;
        }

        foreach ($hrSchedules as $schedule) {
            $user = $schedule->user;
            $studio = $schedule->studio;

            if (!$user || !$studio) {
                continue;
            }

            $workingDates = $this->getWorkingDates($schedule, $monthStart, $monthEnd);

            if ($workingDates === []) {
                $this->command?->warn("No March working dates found for {$user->email}.");
                continue;
            }

            $absenceIndexes = $this->getAbsenceIndexes($user->user_type, count($workingDates), (int) $user->id);

            foreach ($workingDates as $index => $attendanceDate) {
                if (in_array($index, $absenceIndexes, true)) {
                    continue;
                }

                $attendancePayload = $this->buildAttendancePayload($schedule, $attendanceDate, $index, (int) $user->id);

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
                        'check_in_ip' => '192.168.1.' . (20 + ($user->id % 50)),
                        'check_out_ip' => '192.168.1.' . (20 + ($user->id % 50)),
                        'check_in_user_agent' => 'Seeded attendance record for payroll testing',
                        'check_out_user_agent' => 'Seeded attendance record for payroll testing',
                        'notes' => 'Seeded March 2026 HR attendance record.',
                    ]
                );
            }

            $this->command?->info("Seeded March attendance: {$user->email}");
        }
    }

    /**
     * Build all working dates for the requested month.
     *
     * @return array<int, Carbon>
     */
    private function getWorkingDates(EmployeeScheduleModel $schedule, Carbon $monthStart, Carbon $monthEnd): array
    {
        $workingDates = [];
        $period = CarbonPeriod::create($monthStart->copy(), $monthEnd->copy());

        foreach ($period as $date) {
            if ($schedule->worksOnDay(strtolower($date->format('l')))) {
                $workingDates[] = $date->copy();
            }
        }

        return $workingDates;
    }

    /**
     * Pick deterministic absence positions so payroll has realistic deductions.
     *
     * @return array<int, int>
     */
    private function getAbsenceIndexes(string $userType, int $workingDayCount, int $userId): array
    {
        if ($workingDayCount < 4) {
            return [];
        }

        $absenceCount = strtolower($userType) === 'manager' ? 1 : 2;
        $candidateIndexes = [
            2 + ($userId % 3),
            9 + ($userId % 4),
            16 + ($userId % 2),
        ];

        $indexes = [];

        foreach ($candidateIndexes as $candidateIndex) {
            if ($candidateIndex < $workingDayCount) {
                $indexes[] = $candidateIndex;
            }

            if (count($indexes) >= $absenceCount) {
                break;
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * Build a realistic attendance row for one working day.
     *
     * @return array<string, mixed>
     */
    private function buildAttendancePayload(
        EmployeeScheduleModel $schedule,
        Carbon $attendanceDate,
        int $workingDayIndex,
        int $userId
    ): array {
        $scheduledStartTime = $this->normalizeScheduleTime($schedule->start_time, '09:00:00');
        $scheduledEndTime = $this->normalizeScheduleTime($schedule->end_time, '18:00:00');

        $scheduledStart = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledStartTime, 'Asia/Manila');
        $scheduledEnd = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledEndTime, 'Asia/Manila');

        $isLate = (($workingDayIndex + $userId) % 5) === 0;
        $isUndertime = (($workingDayIndex + $userId) % 6) === 0;

        $earlyArrivalMinutes = 8 + (($workingDayIndex + $userId) % 10);
        $lateMinutes = $isLate ? 18 + (($workingDayIndex + $userId) % 23) : 0;
        $undertimeMinutes = $isUndertime ? 20 + (($workingDayIndex + $userId) % 41) : 0;
        $overtimeMinutes = $isUndertime ? 0 : 12 + (($workingDayIndex + $userId) % 36);

        $checkInTime = $isLate
            ? $scheduledStart->copy()->addMinutes($lateMinutes)
            : $scheduledStart->copy()->subMinutes($earlyArrivalMinutes);

        $checkOutTime = $isUndertime
            ? $scheduledEnd->copy()->subMinutes($undertimeMinutes)
            : $scheduledEnd->copy()->addMinutes($overtimeMinutes);

        return [
            'scheduled_start_time' => $scheduledStart->format('H:i:s'),
            'scheduled_end_time' => $scheduledEnd->format('H:i:s'),
            'check_in_time' => $checkInTime->toDateTimeString(),
            'check_out_time' => $checkOutTime->toDateTimeString(),
            'check_in_status' => $isLate ? 'LATE' : 'ON_TIME',
            'check_out_status' => $isUndertime ? 'UNDERTIME' : 'ON_TIME',
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
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
