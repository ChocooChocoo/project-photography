<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add a new temporary column
        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->json('package_locations')->nullable()->after('package_location');
        });

        // Convert existing single values to JSON arrays
        DB::table('tbl_packages')->get()->each(function ($package) {
            if ($package->package_location) {
                DB::table('tbl_packages')
                    ->where('id', $package->id)
                    ->update([
                        'package_locations' => json_encode([$package->package_location])
                    ]);
            }
        });

        // Drop the old column and rename the new one
        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->dropColumn('package_location');
        });

        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->renameColumn('package_locations', 'package_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_packages', function (Blueprint $table) {
            // Add back the old enum column
            $table->enum('package_location_old', ['In-Studio', 'On-Location'])->nullable()->after('id');
        });

        // Convert JSON arrays back to single values (take first location)
        DB::table('tbl_packages')->get()->each(function ($package) {
            $locations = json_decode($package->package_location ?? '[]', true);
            $firstLocation = is_array($locations) && count($locations) > 0 ? $locations[0] : null;
            
            DB::table('tbl_packages')
                ->where('id', $package->id)
                ->update([
                    'package_location_old' => $firstLocation
                ]);
        });

        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->dropColumn('package_location');
            $table->renameColumn('package_location_old', 'package_location');
        });
    }
};