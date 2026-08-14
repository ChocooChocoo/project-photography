<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_subscription_plans', function (Blueprint $table) {
            $table->dropUnique('tbl_subscription_plans_plan_code_unique');
            $table->softDeletes()->after('updated_at');
            $table->unique(['plan_code', 'deleted_at'], 'tbl_subscription_plans_plan_code_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_subscription_plans', function (Blueprint $table) {
            $table->dropUnique('tbl_subscription_plans_plan_code_deleted_at_unique');
            $table->dropSoftDeletes();
            $table->unique('plan_code');
        });
    }
};
