<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_system_revenue', function (Blueprint $table) {
            $table->string('stripe_invoice_id')->nullable()->after('subscription_id')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_system_revenue', function (Blueprint $table) {
            $table->dropUnique(['stripe_invoice_id']);
            $table->dropColumn('stripe_invoice_id');
        });
    }
};
