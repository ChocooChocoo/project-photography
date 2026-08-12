<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `tbl_bookings` MODIFY COLUMN `cancelled_by` ENUM('client', 'studio', 'system') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `tbl_bookings` MODIFY COLUMN `cancelled_by` ENUM('client', 'studio') NULL");
    }
};
