<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_employee_payroll', function (Blueprint $table) {
            // Remove Allowances Section
            $table->dropColumn([
                'rice_allowance',
                'clothing_allowance',
                'laundry_allowance',
                'transportation_allowance',
                'meal_allowance',
                'other_allowances',
                'custom_allowances',
            ]);

            // Remove specified Deductions
            $table->dropColumn([
                'cash_advance_deduction',
                'custom_deductions',
            ]);

            // Remove Overtime Settings
            $table->dropColumn([
                'overtime_enabled',
                'overtime_rate_multiplier',
                'night_differential_rate',
                'night_differential_start',
                'night_differential_end',
                'holiday_overtime_enabled',
                'holiday_overtime_rate',
            ]);

            // Remove Leave Settings
            $table->dropColumn([
                'regular_holidays_per_year',
                'special_holidays_per_year',
                'vacation_leave_days_per_year',
                'sick_leave_days_per_year',
                'emergency_leave_days_per_year',
                'leave_conversion_enabled',
                'leave_conversion_rate',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_employee_payroll', function (Blueprint $table) {
            // Add back Allowances Section
            $table->decimal('rice_allowance', 10, 2)->default(0.00)->after('booking_commission_percentage');
            $table->decimal('clothing_allowance', 10, 2)->default(0.00)->after('rice_allowance');
            $table->decimal('laundry_allowance', 10, 2)->default(0.00)->after('clothing_allowance');
            $table->decimal('transportation_allowance', 10, 2)->default(0.00)->after('laundry_allowance');
            $table->decimal('meal_allowance', 10, 2)->default(0.00)->after('transportation_allowance');
            $table->decimal('other_allowances', 10, 2)->default(0.00)->after('meal_allowance');
            $table->json('custom_allowances')->nullable()->after('other_allowances');

            // Add back specified Deductions
            $table->decimal('cash_advance_deduction', 10, 2)->default(0.00)->after('hdmf_loan_deduction');
            $table->json('custom_deductions')->nullable()->after('other_deductions');

            // Add back Overtime Settings
            $table->boolean('overtime_enabled')->default(true)->after('absent_percentage_deduction');
            $table->decimal('overtime_rate_multiplier', 5, 2)->default(1.25)->after('overtime_enabled');
            $table->decimal('night_differential_rate', 5, 2)->default(1.10)->after('overtime_rate_multiplier');
            $table->time('night_differential_start')->default('22:00:00')->after('night_differential_rate');
            $table->time('night_differential_end')->default('06:00:00')->after('night_differential_start');
            $table->boolean('holiday_overtime_enabled')->default(true)->after('night_differential_end');
            $table->decimal('holiday_overtime_rate', 5, 2)->default(2.00)->after('holiday_overtime_enabled');

            // Add back Leave Settings
            $table->integer('regular_holidays_per_year')->default(12)->after('paid_holidays');
            $table->integer('special_holidays_per_year')->default(5)->after('regular_holidays_per_year');
            $table->integer('vacation_leave_days_per_year')->default(15)->after('special_holidays_per_year');
            $table->integer('sick_leave_days_per_year')->default(15)->after('vacation_leave_days_per_year');
            $table->integer('emergency_leave_days_per_year')->default(3)->after('sick_leave_days_per_year');
            $table->boolean('leave_conversion_enabled')->default(false)->after('emergency_leave_days_per_year');
            $table->decimal('leave_conversion_rate', 5, 2)->nullable()->after('leave_conversion_enabled');
        });
    }
};