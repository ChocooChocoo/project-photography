<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('trial_days')->default(0)->after('commission_rate');
        });

        Schema::table('tbl_studio_plans', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('next_billing_date');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_subscription_plans', function (Blueprint $table) {
            $table->dropColumn('trial_days');
        });

        Schema::table('tbl_studio_plans', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });
    }
};
