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
        // MySQL doesn't support direct ENUM modification, so we need to alter the column
        DB::statement("ALTER TABLE `tbl_booking_assigned_photographers` MODIFY COLUMN `status` ENUM('assigned', 'confirmed', 'on_site', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'assigned'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values
        DB::statement("ALTER TABLE `tbl_booking_assigned_photographers` MODIFY COLUMN `status` ENUM('assigned', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'assigned'");
    }
};