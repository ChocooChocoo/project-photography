<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each package carries its own discount. The booking package snapshot
 * records the discount that was applied, so later changes to the
 * package do not rewrite past bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['tbl_packages', 'tbl_freelancer_packages'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('discount_percentage', 5, 2)->default(0)->after('package_price');
            });
        }

        Schema::table('tbl_booking_packages', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('package_price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percentage');
        });
    }

    public function down(): void
    {
        foreach (['tbl_packages', 'tbl_freelancer_packages'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('discount_percentage'));
        }

        Schema::table('tbl_booking_packages', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'discount_amount']);
        });
    }
};
