<?php

namespace Database\Seeders\Fresh;

use Database\Seeders\Fresh\Concerns\FreshSeedSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Day-to-day studio operations: payroll settings, attendance, generated
 * payslips, and leave / overtime requests.
 *
 * Attendance covers the last 20 operating days of each studio for all 14 of
 * its staff. Presence, lateness, and absence are derived from the user id and
 * the day index, so the pattern is varied but identical on every run.
 *
 * tbl_employee_attendance.check_in_image and check_out_image are the selfie
 * proof columns; both are written as explicit nulls.
 */
class FreshOperationsSeeder
{
    use FreshSeedSupport;

    private const ATTENDANCE_DAYS = 20;

    public function __construct(private ?Command $command = null) {}

    /**
     * @param  array<string, mixed>  $graph
     */
    public function run(array $graph): void
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $studios = $graph['studios'];

        $payrollIds = $this->createPayrollSettings($studios, $now);
        $attendance = $this->createAttendance($studios, $today, $now);
        $this->createGeneratedPayrolls($studios, $payrollIds, $attendance, $today, $now);
        $this->createLeaveRequests($studios, $today, $now);
        $this->createOvertimeRequests($studios, $today, $now);

        $this->command?->info(sprintf(
            'Seeded %d payroll settings and %d attendance rows.',
            count($payrollIds),
            array_sum(array_map(static fn (array $rows): int => count($rows), $attendance))
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $studios
     * @return array<string, int> "studioId:userId" => payroll setting id
     */
    private function createPayrollSettings(array $studios, Carbon $now): array
    {
        $ids = [];

        foreach ($studios as $studio) {
            foreach ($this->staffOf($studio) as $employee) {
                $isPhotographer = $employee['role'] === 'studio-photographer';
                $isManager = $employee['user_type'] === 'Manager';
                $dailyRate = $isManager ? 1650.00 : ($isPhotographer ? 1250.00 : 1050.00);

                $key = $studio['id'].':'.$employee['id'];

                $ids[$key] = (int) DB::table('tbl_employee_payroll')->insertGetId([
                    'user_id' => $employee['id'],
                    'studio_id' => $studio['id'],
                    'created_by' => $studio['owner_id'],
                    'payroll_basis' => $isPhotographer ? 'booking_and_attendance' : 'attendance_only',
                    'daily_rate' => $dailyRate,
                    'monthly_salary' => round($dailyRate * 22, 2),
                    'hourly_rate' => round($dailyRate / 8, 2),
                    'per_booking_rate' => $isPhotographer ? 1800.00 + ($employee['years_of_experience'] ?? 0) * 180 : null,
                    'booking_commission_percentage' => $isPhotographer ? 6.50 : null,
                    'sss_deduction' => 675.00,
                    'phic_deduction' => 450.00,
                    'hdmf_deduction' => 200.00,
                    'tax_withholding' => $isManager ? 1350.00 : 780.00,
                    'sss_loan_deduction' => 0.00,
                    'hdmf_loan_deduction' => 0.00,
                    'other_deductions' => 0.00,
                    'is_taxable' => true,
                    'tax_type' => 'withholding',
                    'subject_to_vat' => false,
                    'vat_percentage' => 12.00,
                    'vat_type' => 'exclusive',
                    'absence_deduction_per_day' => $dailyRate,
                    'undertime_deduction_per_hour' => round($dailyRate / 8, 2),
                    'late_grace_period_minutes' => 15,
                    'late_deduction_per_minute' => round($dailyRate / 480, 2),
                    'absent_deduction_method' => 'deduct_daily_rate',
                    'paid_holidays' => true,
                    'payment_schedule' => $isPhotographer ? 'weekly' : 'semi_monthly',
                    'payday_1' => $isPhotographer ? null : 15,
                    'payday_2' => $isPhotographer ? null : 30,
                    'payday_weekly' => $isPhotographer ? 'friday' : null,
                    'bank_name' => 'Cavite Rural Bank',
                    'bank_account_number' => '00'.str_pad((string) (700000 + $employee['sequence']), 8, '0', STR_PAD_LEFT),
                    'bank_account_name' => 'Studio payroll account',
                    'payment_method' => 'bank_transfer',
                    'is_active' => true,
                    'effective_date' => $now->copy()->startOfYear()->toDateString(),
                    'notes' => 'Fresh seed payroll configuration.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return $ids;
    }

    /**
     * @param  array<int, array<string, mixed>>  $studios
     * @return array<string, array<string, array{present: bool, late: int, undertime: int}>>
     */
    private function createAttendance(array $studios, Carbon $today, Carbon $now): array
    {
        $rows = [];
        $summary = [];

        foreach ($studios as $studio) {
            $dates = $this->recentOperatingDays($studio['operating_days'], $today);

            foreach ($this->staffOf($studio) as $employee) {
                $key = $studio['id'].':'.$employee['id'];
                $summary[$key] = [];

                foreach ($dates as $dayIndex => $date) {
                    $absent = ($employee['id'] + $dayIndex) % 17 === 0;
                    $lateMinutes = ! $absent && ($employee['id'] + $dayIndex) % 7 === 0 ? 12 + ($dayIndex % 3) * 6 : 0;
                    $undertimeMinutes = ! $absent && ($employee['id'] + $dayIndex) % 11 === 0 ? 25 : 0;

                    $summary[$key][$date->toDateString()] = [
                        'present' => ! $absent,
                        'late' => $lateMinutes,
                        'undertime' => $undertimeMinutes,
                    ];

                    $checkIn = $absent ? null : $date->copy()->setTimeFromTimeString($studio['start_time'])->addMinutes($lateMinutes);
                    $checkOut = $absent ? null : $date->copy()->setTimeFromTimeString($studio['end_time'])->subMinutes($undertimeMinutes);

                    $rows[] = [
                        'user_id' => $employee['id'],
                        'studio_id' => $studio['id'],
                        'schedule_id' => $studio['schedule_ids'][$employee['id']] ?? null,
                        'attendance_date' => $date->toDateString(),
                        'scheduled_start_time' => $studio['start_time'],
                        'scheduled_end_time' => $studio['end_time'],
                        'check_in_time' => $checkIn,
                        'check_out_time' => $checkOut,
                        // Selfie proof columns: the seed carries no images.
                        'check_in_image' => null,
                        'check_out_image' => null,
                        'check_in_status' => $absent ? null : ($lateMinutes > 0 ? 'LATE' : 'ON_TIME'),
                        'check_out_status' => $absent ? null : ($undertimeMinutes > 0 ? 'UNDERTIME' : 'ON_TIME'),
                        'late_minutes' => $lateMinutes,
                        'undertime_minutes' => $undertimeMinutes,
                        'check_in_ip' => $absent ? null : '192.168.10.'.(20 + $employee['id'] % 200),
                        'check_out_ip' => $absent ? null : '192.168.10.'.(20 + $employee['id'] % 200),
                        'notes' => $absent ? 'No check-in recorded for this working day.' : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_employee_attendance')->insert($chunk);
        }

        return $summary;
    }

    /**
     * Two semi-monthly payslips per employee, computed from the attendance
     * rows this seeder just wrote rather than from invented totals.
     *
     * @param  array<int, array<string, mixed>>  $studios
     * @param  array<string, int>  $payrollIds
     * @param  array<string, array<string, array{present: bool, late: int, undertime: int}>>  $attendance
     */
    private function createGeneratedPayrolls(array $studios, array $payrollIds, array $attendance, Carbon $today, Carbon $now): void
    {
        $periods = [
            [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->startOfMonth()->addDays(14)],
            [$today->copy()->subMonth()->startOfMonth()->addDays(15), $today->copy()->subMonth()->endOfMonth()->startOfDay()],
        ];

        $rows = [];
        $sequence = 0;

        foreach ($studios as $studio) {
            foreach ($this->staffOf($studio) as $employee) {
                $key = $studio['id'].':'.$employee['id'];
                $isPhotographer = $employee['role'] === 'studio-photographer';

                foreach ($periods as $periodIndex => [$start, $end]) {
                    $sequence++;
                    $days = $this->summariseAttendance($attendance[$key] ?? [], $start, $end);

                    $dailyRate = $employee['user_type'] === 'Manager' ? 1650.00 : ($isPhotographer ? 1250.00 : 1050.00);
                    $attendanceAmount = round($days['present'] * $dailyRate, 2);
                    $bookingCount = $isPhotographer ? 2 + ($employee['id'] + $periodIndex) % 3 : 0;
                    $bookingAmount = round($bookingCount * ($isPhotographer ? 1800.00 : 0), 2);
                    $gross = round($attendanceAmount + $bookingAmount, 2);
                    $deductions = round(675 + 450 + 200 + ($employee['user_type'] === 'Manager' ? 1350 : 780), 2);
                    $approved = $periodIndex === 0;

                    $rows[] = [
                        'payroll_reference' => sprintf('FS-PR-%06d', $sequence),
                        'user_id' => $employee['id'],
                        'studio_id' => $studio['id'],
                        'payroll_setting_id' => $payrollIds[$key],
                        'generated_by' => $studio['owner_id'],
                        'employee_type' => $isPhotographer ? 'studio_photographer' : 'regular_employee',
                        'payroll_basis' => $isPhotographer ? 'booking_and_attendance' : 'attendance_only',
                        'employee_role' => $employee['scoped_role'],
                        'period_start' => $start->toDateString(),
                        'period_end' => $end->toDateString(),
                        'attendance_days_present' => $days['present'],
                        'attendance_days_absent' => $days['absent'],
                        'attendance_minutes_late' => $days['late'],
                        'attendance_minutes_undertime' => $days['undertime'],
                        'booking_count' => $bookingCount,
                        'attendance_amount' => $attendanceAmount,
                        'booking_amount' => $bookingAmount,
                        'gross_amount' => $gross,
                        'total_deductions' => $deductions,
                        'net_amount' => round($gross - $deductions, 2),
                        'deduction_breakdown' => json_encode([
                            'sss' => 675,
                            'phic' => 450,
                            'hdmf' => 200,
                            'tax' => $employee['user_type'] === 'Manager' ? 1350 : 780,
                        ], JSON_THROW_ON_ERROR),
                        'computation_summary' => json_encode([
                            'daily_rate' => $dailyRate,
                            'days_present' => $days['present'],
                            'bookings' => $bookingCount,
                        ], JSON_THROW_ON_ERROR),
                        'status' => $approved ? 'approved' : 'pending',
                        'reviewed_by' => $approved ? $studio['owner_id'] : null,
                        'reviewed_at' => $approved ? $now : null,
                        'generated_at' => $end->copy()->addDay(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_generated_payrolls')->insert($chunk);
        }
    }

    /**
     * @param  array<string, array{present: bool, late: int, undertime: int}>  $days
     * @return array{present: int, absent: int, late: int, undertime: int}
     */
    private function summariseAttendance(array $days, Carbon $start, Carbon $end): array
    {
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'undertime' => 0];

        foreach ($days as $date => $day) {
            if ($date < $start->toDateString() || $date > $end->toDateString()) {
                continue;
            }

            $summary[$day['present'] ? 'present' : 'absent']++;
            $summary['late'] += $day['late'];
            $summary['undertime'] += $day['undertime'];
        }

        // A period with no seeded attendance still needs a plausible payslip.
        if ($summary['present'] === 0 && $summary['absent'] === 0) {
            $summary['present'] = 11;
        }

        return $summary;
    }

    /**
     * @param  array<int, array<string, mixed>>  $studios
     */
    private function createLeaveRequests(array $studios, Carbon $today, Carbon $now): void
    {
        $types = ['vacation_leave', 'sick_leave', 'emergency_leave'];
        $statuses = ['approved', 'pending', 'rejected'];
        $rows = [];
        $sequence = 0;

        foreach ($studios as $studio) {
            $approver = $studio['hr'][0]['id'];
            $staff = $this->staffOf($studio);

            for ($i = 0; $i < 3; $i++) {
                $sequence++;
                $employee = $staff[($i * 5 + 1) % count($staff)];
                $status = $statuses[$i % count($statuses)];
                $start = $today->copy()->subDays(25 - $i * 7);

                $rows[] = [
                    'request_reference' => sprintf('FS-LV-%05d', $sequence),
                    'studio_id' => $studio['id'],
                    'user_id' => $employee['id'],
                    'leave_type' => $types[$i % count($types)],
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->addDay()->toDateString(),
                    'total_days' => 2.00,
                    'reason' => 'Filed in advance and coordinated with the team schedule.',
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? 'Overlaps with a confirmed booking.' : null,
                    'approved_by' => $status === 'approved' ? $approver : null,
                    'approved_at' => $status === 'approved' ? $now : null,
                    'rejected_by' => $status === 'rejected' ? $approver : null,
                    'rejected_at' => $status === 'rejected' ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tbl_leave_requests')->insert($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $studios
     */
    private function createOvertimeRequests(array $studios, Carbon $today, Carbon $now): void
    {
        $statuses = ['approved', 'approved', 'pending', 'rejected'];
        $rows = [];
        $sequence = 0;

        foreach ($studios as $studio) {
            $approver = $studio['hr'][0]['id'];
            $staff = $this->staffOf($studio);

            for ($i = 0; $i < 4; $i++) {
                $sequence++;
                $employee = $staff[($i * 3 + 4) % count($staff)];
                $status = $statuses[$i % count($statuses)];
                $date = $today->copy()->subDays(20 - $i * 4);

                $rows[] = [
                    'request_reference' => sprintf('FS-OT-%05d', $sequence),
                    'studio_id' => $studio['id'],
                    'user_id' => $employee['id'],
                    'overtime_date' => $date->toDateString(),
                    'start_time' => '18:00:00',
                    'end_time' => sprintf('%02d:00:00', 20 + $i % 2),
                    'total_hours' => 2.00 + ($i % 2),
                    'reason' => 'Extended coverage requested for an evening session.',
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? 'Hours already covered by the regular schedule.' : null,
                    'approved_by' => $status === 'approved' ? $approver : null,
                    'approved_at' => $status === 'approved' ? $now : null,
                    'rejected_by' => $status === 'rejected' ? $approver : null,
                    'rejected_at' => $status === 'rejected' ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tbl_overtime_requests')->insert($rows);
    }

    /**
     * All 14 staff of a studio in one flat list.
     *
     * @param  array<string, mixed>  $studio
     * @return array<int, array<string, mixed>>
     */
    private function staffOf(array $studio): array
    {
        return array_merge($studio['hr'], $studio['finance'], $studio['photographers']);
    }

    /**
     * The most recent operating days for a studio, oldest first.
     *
     * @param  array<int, string>  $operatingDays
     * @return array<int, Carbon>
     */
    private function recentOperatingDays(array $operatingDays, Carbon $today): array
    {
        $dates = [];
        $cursor = $today->copy()->subDay();

        while (count($dates) < self::ATTENDANCE_DAYS) {
            if (in_array(strtolower($cursor->englishDayOfWeek), $operatingDays, true)) {
                $dates[] = $cursor->copy();
            }

            $cursor->subDay();
        }

        return array_reverse($dates);
    }
}
