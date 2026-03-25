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
        Schema::create('tbl_generated_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_reference', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('payroll_setting_id');
            $table->unsignedBigInteger('generated_by');
            $table->enum('employee_type', ['regular_employee', 'studio_photographer']);
            $table->enum('payroll_basis', ['attendance_only', 'booking_and_attendance']);
            $table->string('employee_role', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('attendance_days_present')->default(0);
            $table->unsignedInteger('attendance_days_absent')->default(0);
            $table->unsignedInteger('attendance_minutes_late')->default(0);
            $table->unsignedInteger('attendance_minutes_undertime')->default(0);
            $table->unsignedInteger('booking_count')->default(0);
            $table->decimal('attendance_amount', 12, 2)->default(0);
            $table->decimal('booking_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->json('deduction_breakdown')->nullable();
            $table->json('computation_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('studio_id');
            $table->index('payroll_setting_id');
            $table->index('generated_by');
            $table->index(['studio_id', 'period_start', 'period_end']);
            $table->unique(['user_id', 'studio_id', 'period_start', 'period_end'], 'generated_payroll_period_unique');

            $table->foreign('user_id')
                ->references('id')
                ->on('tbl_users')
                ->onDelete('cascade');

            $table->foreign('studio_id')
                ->references('id')
                ->on('tbl_studios')
                ->onDelete('cascade');

            $table->foreign('payroll_setting_id')
                ->references('id')
                ->on('tbl_employee_payroll')
                ->onDelete('cascade');

            $table->foreign('generated_by')
                ->references('id')
                ->on('tbl_users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_generated_payrolls');
    }
};
