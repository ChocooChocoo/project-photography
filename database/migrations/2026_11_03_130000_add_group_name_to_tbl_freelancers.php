<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freelancers who work together can show one shared group name.
 * Bookings, payments, and reviews stay with each freelancer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_freelancers', function (Blueprint $table) {
            $table->string('group_name', 100)->nullable()->after('brand_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_freelancers', function (Blueprint $table) {
            $table->dropIndex(['group_name']);
            $table->dropColumn('group_name');
        });
    }
};
