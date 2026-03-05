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
        Schema::create('tbl_employee_payroll', function (Blueprint $table) {
            $table->id();
            
            // Employee and Studio References
            $table->unsignedBigInteger('user_id')->comment('Reference to tbl_users (employee)');
            $table->unsignedBigInteger('studio_id')->comment('Reference to tbl_studios');
            $table->unsignedBigInteger('created_by')->comment('Studio owner who created this payroll setting');
            
            // Payroll Basis
            $table->enum('payroll_basis', ['attendance_only', 'booking_and_attendance'])
                  ->default('attendance_only')
                  ->comment('attendance_only for HR/Finance, booking_and_attendance for photographers');
            
            // Basic Salary Information
            $table->decimal('daily_rate', 10, 2)->nullable()->comment('Daily rate for attendance-based calculation');
            $table->decimal('monthly_salary', 10, 2)->nullable()->comment('Fixed monthly salary if applicable');
            $table->decimal('hourly_rate', 10, 2)->nullable()->comment('Computed or manual hourly rate');
            $table->decimal('per_booking_rate', 10, 2)->nullable()->comment('Rate per booking for photographers');
            $table->decimal('booking_commission_percentage', 5, 2)->nullable()->comment('Commission percentage from bookings');
            
            // Allowances
            $table->decimal('rice_allowance', 10, 2)->default(0.00);
            $table->decimal('clothing_allowance', 10, 2)->default(0.00);
            $table->decimal('laundry_allowance', 10, 2)->default(0.00);
            $table->decimal('transportation_allowance', 10, 2)->default(0.00);
            $table->decimal('meal_allowance', 10, 2)->default(0.00);
            $table->decimal('other_allowances', 10, 2)->default(0.00);
            $table->json('custom_allowances')->nullable()->comment('JSON array of custom allowance types and amounts');
            
            // Deduction Settings
            $table->decimal('sss_deduction', 10, 2)->default(0.00)->comment('SSS monthly contribution');
            $table->decimal('phic_deduction', 10, 2)->default(0.00)->comment('PhilHealth monthly contribution');
            $table->decimal('hdmf_deduction', 10, 2)->default(0.00)->comment('Pag-IBIG monthly contribution');
            $table->decimal('tax_withholding', 10, 2)->default(0.00)->comment('Withholding tax amount');
            $table->decimal('sss_loan_deduction', 10, 2)->default(0.00);
            $table->decimal('hdmf_loan_deduction', 10, 2)->default(0.00);
            $table->decimal('cash_advance_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions', 10, 2)->default(0.00);
            $table->json('custom_deductions')->nullable()->comment('JSON array of custom deduction types and amounts');
            
            // Tax Settings
            $table->boolean('is_taxable')->default(true);
            $table->enum('tax_type', ['withholding', 'graduated', 'exempt'])->default('withholding');
            $table->decimal('tax_percentage', 5, 2)->nullable()->comment('For percentage-based tax');
            $table->string('tax_code', 50)->nullable()->comment('Tax code/classification');
            
            // VAT Settings
            $table->boolean('subject_to_vat')->default(false);
            $table->decimal('vat_percentage', 5, 2)->default(12.00)->comment('VAT rate (default 12%)');
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('exclusive');
            
            // Absence and Undertime Settings
            $table->decimal('absence_deduction_per_day', 10, 2)->nullable()->comment('Amount deducted per day of absence');
            $table->decimal('undertime_deduction_per_hour', 10, 2)->nullable()->comment('Amount deducted per hour of undertime');
            $table->integer('late_grace_period_minutes')->default(15)->comment('Grace period in minutes before deducting');
            $table->decimal('late_deduction_per_minute', 10, 2)->nullable()->comment('Amount deducted per minute late after grace period');
            $table->enum('absent_deduction_method', ['deduct_daily_rate', 'deduct_fixed_amount', 'deduct_percentage'])
                  ->default('deduct_daily_rate');
            $table->decimal('absent_fixed_deduction', 10, 2)->nullable()->comment('Fixed amount deduction for absence');
            $table->decimal('absent_percentage_deduction', 5, 2)->nullable()->comment('Percentage deduction for absence');
            
            // Overtime Settings
            $table->boolean('overtime_enabled')->default(true);
            $table->decimal('overtime_rate_multiplier', 5, 2)->default(1.25)->comment('e.g., 1.25 = 125% of hourly rate');
            $table->decimal('night_differential_rate', 5, 2)->default(1.10)->comment('10% additional for night shift');
            $table->time('night_differential_start')->default('22:00:00');
            $table->time('night_differential_end')->default('06:00:00');
            $table->boolean('holiday_overtime_enabled')->default(true);
            $table->decimal('holiday_overtime_rate', 5, 2)->default(2.00)->comment('Double pay for holidays');
            
            // Holiday and Leave Settings
            $table->integer('regular_holidays_per_year')->default(12);
            $table->integer('special_holidays_per_year')->default(5);
            $table->boolean('paid_holidays')->default(true);
            $table->integer('vacation_leave_days_per_year')->default(15);
            $table->integer('sick_leave_days_per_year')->default(15);
            $table->integer('emergency_leave_days_per_year')->default(3);
            $table->boolean('leave_conversion_enabled')->default(false)->comment('Convert unused leave to cash');
            $table->decimal('leave_conversion_rate', 5, 2)->nullable()->comment('Percentage of daily rate for leave conversion');
            
            // Payment Schedule
            $table->enum('payment_schedule', ['weekly', 'bi_weekly', 'semi_monthly', 'monthly'])->default('semi_monthly');
            $table->integer('payday_1')->nullable()->comment('First payday of month (1-31)');
            $table->integer('payday_2')->nullable()->comment('Second payday of month (1-31) for semi-monthly');
            $table->enum('payday_weekly', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])->nullable();
            
            // Banking Information
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 255)->nullable();
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check'])->default('bank_transfer');
            
            // Status and Metadata
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable()->comment('When these settings take effect');
            $table->date('expiry_date')->nullable()->comment('When these settings expire (if applicable)');
            $table->text('notes')->nullable();
            
            // Audit Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('studio_id');
            $table->index('created_by');
            $table->index('is_active');
            $table->index('payroll_basis');
            $table->index(['studio_id', 'is_active']);
            $table->index(['user_id', 'studio_id']);
            
            // Foreign Keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
                  
            $table->foreign('studio_id')
                  ->references('id')
                  ->on('tbl_studios')
                  ->onDelete('cascade');
                  
            $table->foreign('created_by')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
                  
            // Ensure one active payroll setting per employee per studio
            $table->unique(['user_id', 'studio_id'], 'unique_employee_payroll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_employee_payroll');
    }
};