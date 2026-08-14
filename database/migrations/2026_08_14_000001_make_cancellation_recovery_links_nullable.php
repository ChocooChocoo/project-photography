<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'studio_id')) {
                $table->unsignedBigInteger('studio_id')->nullable()->change();
            }
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'original_assignment_id')) {
                $table->unsignedBigInteger('original_assignment_id')->nullable()->change();
            }
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'deadline')) {
                $table->timestamp('deadline')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'studio_id')) {
                $table->unsignedBigInteger('studio_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'original_assignment_id')) {
                $table->unsignedBigInteger('original_assignment_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'deadline')) {
                $table->timestamp('deadline')->nullable(false)->change();
            }
        });
    }
};
