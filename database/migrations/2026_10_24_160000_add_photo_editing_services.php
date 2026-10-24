<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo editing services a package offers (price per service), and the
 * services a client booked (snapshot of name => price).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['tbl_packages', 'tbl_freelancer_packages'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('editing_prices')->nullable()->after('enhanced_photos_price');
            });
        }

        Schema::table('tbl_booking_packages', function (Blueprint $table) {
            $table->json('editing_services')->nullable()->after('enhancement_amount');
        });
    }

    public function down(): void
    {
        foreach (['tbl_packages', 'tbl_freelancer_packages'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('editing_prices'));
        }

        Schema::table('tbl_booking_packages', fn (Blueprint $table) => $table->dropColumn('editing_services'));
    }
};
