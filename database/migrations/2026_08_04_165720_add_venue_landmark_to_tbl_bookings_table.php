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
            $table->string('venue_landmark')->nullable()->after('venue_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->dropColumn('venue_landmark');
        });
    }
};
