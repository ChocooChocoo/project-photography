<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-text specs (sensor, lens range, and so on) that clients can read
 * on the studio profile before they book.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_studio_equipment', function (Blueprint $table) {
            $table->text('specs')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studio_equipment', function (Blueprint $table) {
            $table->dropColumn('specs');
        });
    }
};
