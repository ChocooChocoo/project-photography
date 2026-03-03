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
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->json('multiple_locations')->nullable()->after('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->dropColumn('multiple_locations');
        });
    }
};