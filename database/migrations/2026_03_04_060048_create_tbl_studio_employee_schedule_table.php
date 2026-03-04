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
        Schema::create('tbl_studio_employee_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Employee user ID
            $table->unsignedBigInteger('studio_id'); // Which studio they work for
            $table->json('operating_days'); // Store selected days as JSON array
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('18:00:00');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // For any special schedule notes
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
                  
            // Indexes
            $table->index('user_id');
            $table->index('studio_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_studio_employee_schedule');
    }
};