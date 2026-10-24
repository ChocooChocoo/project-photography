<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let studio owners set the downpayment as a percentage (existing
 * downpayment_percentage) or a fixed amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->enum('downpayment_type', ['percentage', 'fixed'])->default('percentage')->after('downpayment_percentage');
            $table->decimal('downpayment_amount', 10, 2)->nullable()->after('downpayment_type');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->dropColumn(['downpayment_type', 'downpayment_amount']);
        });
    }
};
