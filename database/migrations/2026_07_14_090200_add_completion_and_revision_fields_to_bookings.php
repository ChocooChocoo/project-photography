<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('expires_at');
            $table->timestamp('revision_requested_at')->nullable()->after('completed_at');
            $table->timestamp('revision_deadline')->nullable()->after('revision_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'revision_requested_at', 'revision_deadline']);
        });
    }
};
