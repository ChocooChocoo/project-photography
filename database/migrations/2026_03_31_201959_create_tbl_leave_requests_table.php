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
        Schema::create('tbl_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->unique();
            $table->foreignId('studio_id')->constrained('tbl_studios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->enum('leave_type', [
                'vacation_leave',
                'sick_leave',
                'emergency_leave',
                'maternity_leave',
                'paternity_leave',
                'bereavement_leave',
                'unpaid_leave',
                'other_leave',
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 2)->default(1.00);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['studio_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_leave_requests');
    }
};
