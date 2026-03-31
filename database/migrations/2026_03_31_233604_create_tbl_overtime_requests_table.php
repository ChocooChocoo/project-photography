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
        Schema::create('tbl_overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference')->unique();
            $table->foreignId('studio_id')->constrained('tbl_studios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->date('overtime_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_hours', 5, 2)->default(0.00);
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
            $table->index('overtime_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_overtime_requests');
    }
};
