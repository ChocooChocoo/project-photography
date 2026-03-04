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
        // Modify the ENUM to include Staff and Manager
        DB::statement("ALTER TABLE tbl_users MODIFY COLUMN user_type ENUM(
            'Photographer',
            'Customer',
            'Admin',
            'Staff',
            'Manager'
        ) NOT NULL DEFAULT 'Customer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM
        DB::statement("ALTER TABLE tbl_users MODIFY COLUMN user_type ENUM(
            'Photographer',
            'Customer',
            'Admin'
        ) NOT NULL DEFAULT 'Customer'");
    }
};