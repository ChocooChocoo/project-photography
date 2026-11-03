<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * On-location bookings store the venue pin the client drops on the map.
 * A photographer's on-site check-in records how far they were from the
 * venue (or the studio) when they arrived.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->decimal('venue_latitude', 10, 7)->nullable()->after('province');
            $table->decimal('venue_longitude', 10, 7)->nullable()->after('venue_latitude');
        });

        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->unsignedInteger('arrival_distance_meters')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->dropColumn(['venue_latitude', 'venue_longitude']);
        });

        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->dropColumn('arrival_distance_meters');
        });
    }
};
