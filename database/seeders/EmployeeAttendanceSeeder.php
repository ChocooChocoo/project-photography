<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic employee attendance records for payroll generation.
 */
class EmployeeAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now('Asia/Manila');
        $periodStart = $now->copy()->startOfMonth()->subMonth()->startOfMonth();
        $periodEnd = $now->copy()->subDay()->endOfDay();

        $schedules = DB::table('tbl_studio_employee_schedule')
            ->where('is_active', 1)
            ->orderBy('user_id')
            ->get();

        if ($schedules->isEmpty()) {
            $this->command->warn('No active employee schedules were found. Attendance seeding skipped.');
            return;
        }

        $userIds = $schedules->pluck('user_id')->unique()->values();
        $validUserIds = DB::table('tbl_users')
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->flip();

        $seededRows = 0;

        foreach ($schedules as $schedule) {
            if (!$validUserIds->has($schedule->user_id)) {
                continue;
            }

            $operatingDays = json_decode($schedule->operating_days, true);
            if (!is_array($operatingDays) || empty($operatingDays)) {
                continue;
            }

            $operatingDays = array_map('strtolower', $operatingDays);
            $scheduledStart = Carbon::createFromFormat('H:i:s', $schedule->start_time, 'Asia/Manila');
            $scheduledEnd = Carbon::createFromFormat('H:i:s', $schedule->end_time, 'Asia/Manila');

            $period = CarbonPeriod::create($periodStart->copy()->startOfDay(), $periodEnd->copy()->startOfDay());

            foreach ($period as $attendanceDate) {
                if (!in_array(strtolower($attendanceDate->format('l')), $operatingDays, true)) {
                    continue;
                }

                $scenarioSeed = (($schedule->user_id * 1000) + ((int) $attendanceDate->format('Ymd'))) % 100;
                $record = [
                    'user_id' => $schedule->user_id,
                    'studio_id' => $schedule->studio_id,
                    'schedule_id' => $schedule->id,
                    'attendance_date' => $attendanceDate->toDateString(),
                    'scheduled_start_time' => $scheduledStart->format('H:i:s'),
                    'scheduled_end_time' => $scheduledEnd->format('H:i:s'),
                    'check_in_image' => null,
                    'check_out_image' => null,
                    'check_in_ip' => '127.0.0.1',
                    'check_out_ip' => '127.0.0.1',
                    'check_in_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
                    'check_out_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($scenarioSeed < 12) {
                    $record = array_merge($record, [
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'check_in_status' => null,
                        'check_out_status' => null,
                        'late_minutes' => 0,
                        'undertime_minutes' => 0,
                        'notes' => 'Marked absent for the day.',
                    ]);
                } else {
                    $lateMinutes = $scenarioSeed >= 88 ? (($scenarioSeed - 87) * 3) : 0;
                    $undertimeMinutes = ($scenarioSeed >= 70 && $scenarioSeed <= 81) ? (($scenarioSeed - 69) * 4) : 0;

                    $checkIn = $attendanceDate->copy()
                        ->setTimeFromTimeString($scheduledStart->format('H:i:s'))
                        ->addMinutes($lateMinutes > 0 ? $lateMinutes + random_int(1, 5) : random_int(-8, 6));

                    if ($checkIn->lt($attendanceDate->copy()->setTimeFromTimeString($scheduledStart->format('H:i:s'))->subMinutes(15))) {
                        $checkIn = $attendanceDate->copy()->setTimeFromTimeString($scheduledStart->format('H:i:s'))->subMinutes(15);
                    }

                    $checkOut = $attendanceDate->copy()
                        ->setTimeFromTimeString($scheduledEnd->format('H:i:s'))
                        ->subMinutes($undertimeMinutes)
                        ->addMinutes($undertimeMinutes === 0 ? random_int(0, 18) : random_int(0, 4));

                    if ($checkOut->lte($checkIn)) {
                        $checkOut = $checkIn->copy()->addHours(8);
                    }

                    $record = array_merge($record, [
                        'check_in_time' => $checkIn->format('Y-m-d H:i:s'),
                        'check_out_time' => $checkOut->format('Y-m-d H:i:s'),
                        'check_in_status' => $lateMinutes > 0 ? 'LATE' : 'ON_TIME',
                        'check_out_status' => $undertimeMinutes > 0 ? 'UNDERTIME' : 'ON_TIME',
                        'late_minutes' => $lateMinutes,
                        'undertime_minutes' => $undertimeMinutes,
                        'notes' => $this->buildAttendanceNote($lateMinutes, $undertimeMinutes),
                    ]);
                }

                DB::table('tbl_employee_attendance')->updateOrInsert(
                    [
                        'user_id' => $record['user_id'],
                        'attendance_date' => $record['attendance_date'],
                    ],
                    $record
                );

                $seededRows++;
            }
        }

        $this->command->info("Attendance records seeded successfully for payroll testing. Total affected rows: {$seededRows}");
        $this->command->info('Covered period: ' . $periodStart->toDateString() . ' to ' . $periodEnd->toDateString());
    }

    /**
     * Build a realistic attendance note.
     */
    private function buildAttendanceNote(int $lateMinutes, int $undertimeMinutes): ?string
    {
        if ($lateMinutes > 0 && $undertimeMinutes > 0) {
            return 'Employee arrived late and left early due to schedule adjustment.';
        }

        if ($lateMinutes > 0) {
            return 'Employee arrived late due to traffic and still completed the shift.';
        }

        if ($undertimeMinutes > 0) {
            return 'Employee left early with prior notice from the supervisor.';
        }

        return 'Employee completed the shift as scheduled.';
    }
}
