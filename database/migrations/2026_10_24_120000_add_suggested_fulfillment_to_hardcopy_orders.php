<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keep the studio's suggested fulfillment apart from the client's choice.
 * The client's accepted choice stays in fulfillment_method and is final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_booking_hardcopy_orders', function (Blueprint $table) {
            $table->enum('suggested_fulfillment_method', ['pickup', 'courier'])->nullable()->after('fulfillment_method');
        });

        // Older orders kept only one value; treat it as the suggestion too.
        DB::table('tbl_booking_hardcopy_orders')->update(['suggested_fulfillment_method' => DB::raw('fulfillment_method')]);
    }

    public function down(): void
    {
        Schema::table('tbl_booking_hardcopy_orders', function (Blueprint $table) {
            $table->dropColumn('suggested_fulfillment_method');
        });
    }
};
