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
        Schema::table('tbl_employee_attendance', function (Blueprint $table) {
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('check_in_image');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
            $table->unsignedInteger('check_in_distance_meters')->nullable()->after('check_in_longitude');
            $table->string('check_in_location_status')->nullable()->after('check_in_distance_meters');

            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_out_image');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
            $table->unsignedInteger('check_out_distance_meters')->nullable()->after('check_out_longitude');
            $table->string('check_out_location_status')->nullable()->after('check_out_distance_meters');
        });

        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->decimal('attendance_latitude', 10, 7)->nullable()->after('barangay');
            $table->decimal('attendance_longitude', 10, 7)->nullable()->after('attendance_latitude');
            $table->unsignedInteger('attendance_radius_meters')->default(100)->after('attendance_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_employee_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_latitude',
                'check_in_longitude',
                'check_in_distance_meters',
                'check_in_location_status',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_distance_meters',
                'check_out_location_status',
            ]);
        });

        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_latitude',
                'attendance_longitude',
                'attendance_radius_meters',
            ]);
        });
    }
};
