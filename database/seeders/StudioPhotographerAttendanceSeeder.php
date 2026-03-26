<?php

namespace Database\Seeders;

use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioPhotographer\PhotographerAttendanceModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class StudioPhotographerAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monthStart = Carbon::create(2026, 3, 1, 0, 0, 0, 'Asia/Manila')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $photographerAssignments = StudioPhotographersModel::with(['photographer', 'studio'])
            ->where('status', 'active')
            ->get();

        if ($photographerAssignments->isEmpty()) {
            $this->command?->warn('No active studio photographer assignments found. Attendance seeder skipped.');
            return;
        }

        foreach ($photographerAssignments as $assignment) {
            $photographer = $assignment->photographer;
            $studio = $assignment->studio;

            if (!$photographer || !$studio) {
                continue;
            }

            $workingDates = $this->getWorkingDates($studio->operating_days, $monthStart, $monthEnd);

            if ($workingDates === []) {
                $this->command?->warn("No March working dates found for {$photographer->email}.");
                continue;
            }

            $absenceIndexes = $this->getAbsenceIndexes(count($workingDates), (int) $photographer->id);

            foreach ($workingDates as $index => $attendanceDate) {
                if (in_array($index, $absenceIndexes, true)) {
                    continue;
                }

                $attendancePayload = $this->buildAttendancePayload(
                    $studio->start_time,
                    $studio->end_time,
                    $attendanceDate,
                    $index,
                    (int) $photographer->id
                );

                PhotographerAttendanceModel::updateOrCreate(
                    [
                        'user_id' => $photographer->id,
                        'attendance_date' => $attendanceDate->toDateString(),
                    ],
                    [
                        'studio_id' => $studio->id,
                        'schedule_id' => null,
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
                        'check_in_ip' => '192.168.2.' . (40 + ($photographer->id % 60)),
                        'check_out_ip' => '192.168.2.' . (40 + ($photographer->id % 60)),
                        'check_in_user_agent' => 'Seeded photographer attendance record for March testing',
                        'check_out_user_agent' => 'Seeded photographer attendance record for March testing',
                        'notes' => 'Seeded March 2026 studio photographer attendance record.',
                    ]
                );
            }

            $this->command?->info("Seeded March photographer attendance: {$photographer->email}");
        }
    }

    /**
     * Build all working dates for March based on studio operating days.
     *
     * @param mixed $operatingDays
     * @return array<int, Carbon>
     */
    private function getWorkingDates($operatingDays, Carbon $monthStart, Carbon $monthEnd): array
    {
        if (is_string($operatingDays)) {
            $decoded = json_decode($operatingDays, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $operatingDays = $decoded;
        }

        if (!is_array($operatingDays) || empty($operatingDays)) {
            $operatingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }

        $normalizedDays = array_map(static fn ($day) => strtolower((string) $day), $operatingDays);
        $workingDates = [];
        $period = CarbonPeriod::create($monthStart->copy(), $monthEnd->copy());

        foreach ($period as $date) {
            if (in_array(strtolower($date->format('l')), $normalizedDays, true)) {
                $workingDates[] = $date->copy();
            }
        }

        return $workingDates;
    }

    /**
     * Pick deterministic March absences for seeded photographer records.
     *
     * @return array<int, int>
     */
    private function getAbsenceIndexes(int $workingDayCount, int $userId): array
    {
        if ($workingDayCount < 5) {
            return [];
        }

        $candidateIndexes = [
            3 + ($userId % 2),
            10 + ($userId % 3),
            17 + ($userId % 4),
        ];

        $indexes = [];

        foreach ($candidateIndexes as $candidateIndex) {
            if ($candidateIndex < $workingDayCount) {
                $indexes[] = $candidateIndex;
            }

            if (count($indexes) >= 2) {
                break;
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * Build a realistic March attendance row for one photographer work day.
     *
     * @param mixed $startTime
     * @param mixed $endTime
     * @return array<string, mixed>
     */
    private function buildAttendancePayload($startTime, $endTime, Carbon $attendanceDate, int $workingDayIndex, int $userId): array
    {
        $scheduledStartTime = $this->normalizeScheduleTime($startTime, '09:00:00');
        $scheduledEndTime = $this->normalizeScheduleTime($endTime, '18:00:00');

        $scheduledStart = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledStartTime, 'Asia/Manila');
        $scheduledEnd = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledEndTime, 'Asia/Manila');

        $isLate = (($workingDayIndex + $userId) % 4) === 0;
        $isUndertime = (($workingDayIndex + $userId) % 6) === 0;

        $earlyArrivalMinutes = 6 + (($workingDayIndex + $userId) % 9);
        $lateMinutes = $isLate ? 12 + (($workingDayIndex + $userId) % 19) : 0;
        $undertimeMinutes = $isUndertime ? 15 + (($workingDayIndex + $userId) % 26) : 0;
        $overtimeMinutes = $isUndertime ? 0 : 20 + (($workingDayIndex + $userId) % 31);

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
     * Normalize time values from the studio model cast.
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
