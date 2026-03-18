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
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            // Add on_site tracking fields
            $table->timestamp('on_site_at')->nullable()->after('confirmed_at');
            $table->timestamp('client_confirmed_at')->nullable()->after('on_site_at');
            $table->text('client_confirmation_notes')->nullable()->after('client_confirmed_at');
            
            // Add new status to enum if needed (but we'll handle via logic, not changing enum)
            // We'll use 'confirmed' -> 'in_progress' with on_site_at as a marker
        });

        // Add comment for documentation
        DB::statement("ALTER TABLE `tbl_booking_assigned_photographers` COMMENT 'on_site_at: When photographer marked on-site, client_confirmed_at: When client verified presence'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->dropColumn(['on_site_at', 'client_confirmed_at', 'client_confirmation_notes']);
        });
    }
};