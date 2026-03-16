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
        Schema::create('tbl_employee_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            
            $table->date('attendance_date');
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            
            $table->datetime('check_in_time')->nullable();
            $table->datetime('check_out_time')->nullable();
            
            $table->string('check_in_image')->nullable();
            $table->string('check_out_image')->nullable();
            
            $table->enum('check_in_status', ['ON_TIME', 'LATE'])->nullable();
            $table->enum('check_out_status', ['ON_TIME', 'UNDERTIME'])->nullable();
            
            $table->integer('late_minutes')->default(0);
            $table->integer('undertime_minutes')->default(0);
            
            $table->string('check_in_ip')->nullable();
            $table->string('check_out_ip')->nullable();
            $table->text('check_in_user_agent')->nullable();
            $table->text('check_out_user_agent')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
                  
            $table->foreign('studio_id')
                  ->references('id')
                  ->on('tbl_studios')
                  ->onDelete('cascade');
                  
            $table->foreign('schedule_id')
                  ->references('id')
                  ->on('tbl_studio_employee_schedule')
                  ->onDelete('set null');

            // Indexes for performance
            $table->index('user_id');
            $table->index('studio_id');
            $table->index('attendance_date');
            $table->index('check_in_status');
            $table->index('schedule_id');
            
            // Composite index for common queries
            $table->index(['user_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_employee_attendance');
    }
};