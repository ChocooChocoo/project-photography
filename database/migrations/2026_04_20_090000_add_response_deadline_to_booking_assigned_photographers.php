<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->timestamp('response_deadline')->nullable()->after('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->dropColumn('response_deadline');
        });
    }
};
