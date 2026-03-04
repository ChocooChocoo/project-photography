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
        // Modify the ENUM to include new roles
        DB::statement("ALTER TABLE tbl_users MODIFY COLUMN role ENUM(
            'admin', 
            'owner', 
            'freelancer', 
            'client', 
            'studio-photographer',
            'studio-staff',
            'studio-hr',
            'studio-finance'
        ) NOT NULL DEFAULT 'client'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM
        DB::statement("ALTER TABLE tbl_users MODIFY COLUMN role ENUM(
            'admin', 
            'owner', 
            'freelancer', 
            'client', 
            'studio-photographer',
            'studio-staff'
        ) NOT NULL DEFAULT 'client'");
    }
};