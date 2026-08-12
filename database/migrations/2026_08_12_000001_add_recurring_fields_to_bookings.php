<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->string('booking_frequency')->default('one_time');
            $table->json('recurrence_pattern')->nullable();
            $table->unsignedBigInteger('parent_booking_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_frequency', 'recurrence_pattern', 'parent_booking_id']);
        });
    }
};
