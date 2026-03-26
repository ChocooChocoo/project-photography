<?php

namespace Database\Seeders;

use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StudioEmployeePayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $effectiveDate = Carbon::now()->startOfMonth()->toDateString();

        $scheduleEmployees = EmployeeScheduleModel::with(['user', 'studio'])
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['studio-hr', 'studio-finance']);
            })
            ->get();

        foreach ($scheduleEmployees as $scheduleEmployee) {
            $user = $scheduleEmployee->user;
            $studio = $scheduleEmployee->studio;

            if (!$user || !$studio) {
                continue;
            }

            $payrollData = $this->buildRegularEmployeePayroll($user, (int) $studio->user_id, $effectiveDate);

            EmployeePayrollModel::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'studio_id' => $studio->id,
                ],
                $payrollData
            );

            $this->command?->info("Seeded payroll: {$user->email} ({$user->role})");
        }

        $photographers = StudioPhotographersModel::with(['photographer', 'studio'])
            ->get();

        foreach ($photographers as $photographerAssignment) {
            $user = $photographerAssignment->photographer;
            $studio = $photographerAssignment->studio;

            if (!$user || !$studio) {
                continue;
            }

            $payrollData = $this->buildPhotographerPayroll($photographerAssignment, (int) $studio->user_id, $effectiveDate);

            EmployeePayrollModel::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'studio_id' => $studio->id,
                ],
                $payrollData
            );

            $this->command?->info("Seeded payroll: {$user->email} ({$user->role})");
        }
    }

    /**
     * Build realistic payroll data for HR and finance employees.
     *
     * @return array<string, mixed>
     */
    private function buildRegularEmployeePayroll(UserModel $user, int $createdBy, string $effectiveDate): array
    {
        $isManager = strtolower((string) $user->user_type) === 'manager';
        $isHr = $user->role === 'studio-hr';

        $monthlySalary = match (true) {
            $isHr && $isManager => 42000.00,
            $isHr && !$isManager => 26000.00,
            !$isHr && $isManager => 45000.00,
            default => 28000.00,
        };

        $dailyRate = round($monthlySalary / 22, 2);
        $hourlyRate = round($dailyRate / 8, 2);

        $sssDeduction = $isManager ? 1800.00 : 1350.00;
        $phicDeduction = $isManager ? 650.00 : 450.00;
        $hdmfDeduction = 200.00;
        $taxWithholding = $isManager ? 2200.00 : 900.00;
        $otherDeductions = $isHr ? 350.00 : 500.00;

        return [
            'created_by' => $createdBy,
            'payroll_basis' => 'attendance_only',
            'daily_rate' => $dailyRate,
            'monthly_salary' => $monthlySalary,
            'hourly_rate' => $hourlyRate,
            'per_booking_rate' => null,
            'booking_commission_percentage' => null,
            'sss_deduction' => $sssDeduction,
            'phic_deduction' => $phicDeduction,
            'hdmf_deduction' => $hdmfDeduction,
            'tax_withholding' => $taxWithholding,
            'sss_loan_deduction' => 0.00,
            'hdmf_loan_deduction' => 0.00,
            'other_deductions' => $otherDeductions,
            'is_taxable' => true,
            'tax_type' => 'withholding',
            'tax_percentage' => null,
            'tax_code' => $isManager ? 'M1' : 'S1',
            'subject_to_vat' => false,
            'vat_percentage' => 12.00,
            'vat_type' => 'exclusive',
            'absence_deduction_per_day' => $dailyRate,
            'undertime_deduction_per_hour' => $hourlyRate,
            'late_grace_period_minutes' => 15,
            'late_deduction_per_minute' => round($hourlyRate / 60, 2),
            'absent_deduction_method' => 'deduct_daily_rate',
            'absent_fixed_deduction' => null,
            'absent_percentage_deduction' => null,
            'paid_holidays' => true,
            'payment_schedule' => 'semi_monthly',
            'payday_1' => 15,
            'payday_2' => 30,
            'payday_weekly' => null,
            'bank_name' => $isHr ? 'BDO Unibank' : 'BPI',
            'bank_account_number' => $this->makeBankAccountNumber($user->id, $isManager ? 91 : 37),
            'bank_account_name' => $user->full_name,
            'payment_method' => 'bank_transfer',
            'is_active' => true,
            'effective_date' => $effectiveDate,
            'expiry_date' => null,
            'notes' => $isHr
                ? 'Attendance-based payroll for HR staff with standard statutory deductions.'
                : 'Attendance-based payroll for finance staff with standard statutory deductions.',
        ];
    }

    /**
     * Build realistic payroll data for studio photographers.
     *
     * @return array<string, mixed>
     */
    private function buildPhotographerPayroll(StudioPhotographersModel $assignment, int $createdBy, string $effectiveDate): array
    {
        $experience = max((int) ($assignment->years_of_experience ?? 0), 1);
        $baseDailyRate = 1150.00 + ($experience * 90.00);
        $dailyRate = round(min($baseDailyRate, 2400.00), 2);
        $hourlyRate = round($dailyRate / 8, 2);
        $perBookingRate = round(1800.00 + ($experience * 220.00), 2);
        $bookingCommission = $experience >= 7 ? 8.50 : ($experience >= 4 ? 7.00 : 5.50);

        $sssDeduction = $experience >= 7 ? 1350.00 : 1100.00;
        $phicDeduction = $experience >= 7 ? 500.00 : 400.00;
        $hdmfDeduction = 200.00;
        $taxWithholding = $experience >= 7 ? 950.00 : 650.00;
        $otherDeductions = 250.00;

        return [
            'created_by' => $createdBy,
            'payroll_basis' => 'booking_and_attendance',
            'daily_rate' => $dailyRate,
            'monthly_salary' => null,
            'hourly_rate' => $hourlyRate,
            'per_booking_rate' => $perBookingRate,
            'booking_commission_percentage' => $bookingCommission,
            'sss_deduction' => $sssDeduction,
            'phic_deduction' => $phicDeduction,
            'hdmf_deduction' => $hdmfDeduction,
            'tax_withholding' => $taxWithholding,
            'sss_loan_deduction' => 0.00,
            'hdmf_loan_deduction' => 0.00,
            'other_deductions' => $otherDeductions,
            'is_taxable' => true,
            'tax_type' => 'withholding',
            'tax_percentage' => null,
            'tax_code' => 'P1',
            'subject_to_vat' => false,
            'vat_percentage' => 12.00,
            'vat_type' => 'exclusive',
            'absence_deduction_per_day' => $dailyRate,
            'undertime_deduction_per_hour' => $hourlyRate,
            'late_grace_period_minutes' => 20,
            'late_deduction_per_minute' => round($hourlyRate / 60, 2),
            'absent_deduction_method' => 'deduct_daily_rate',
            'absent_fixed_deduction' => null,
            'absent_percentage_deduction' => null,
            'paid_holidays' => true,
            'payment_schedule' => 'weekly',
            'payday_1' => null,
            'payday_2' => null,
            'payday_weekly' => 'friday',
            'bank_name' => 'UnionBank',
            'bank_account_number' => $this->makeBankAccountNumber($assignment->photographer_id, 58),
            'bank_account_name' => $assignment->photographer->full_name,
            'payment_method' => 'bank_transfer',
            'is_active' => true,
            'effective_date' => $effectiveDate,
            'expiry_date' => null,
            'notes' => 'Attendance plus per-booking payroll setup for studio photographer, including booking commission.',
        ];
    }

    /**
     * Generate a stable seeded bank account number.
     */
    private function makeBankAccountNumber(int $userId, int $prefix): string
    {
        return (string) ($prefix . str_pad((string) $userId, 10, '0', STR_PAD_LEFT));
    }
}
