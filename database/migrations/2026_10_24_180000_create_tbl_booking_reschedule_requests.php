<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A client's request to move a booking, and the provider's answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_booking_reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('tbl_bookings')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('tbl_users');
            $table->date('requested_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'alternatives_offered', 'accepted'])->default('pending');
            $table->json('alternatives')->nullable();
            $table->text('response_note')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('tbl_users');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_booking_reschedule_requests');
    }
};
