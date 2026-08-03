<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_studio_plans', function (Blueprint $table) {
            $table->enum('status', ['active', 'grace', 'expired', 'cancelled', 'pending'])
                ->default('pending')
                ->change();
            $table->timestamp('grace_ends_at')->nullable()->after('trial_ends_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tbl_studio_plans')
            ->where('status', 'grace')
            ->update(['status' => 'expired']);

        Schema::table('tbl_studio_plans', function (Blueprint $table) {
            $table->dropIndex(['grace_ends_at']);
            $table->dropColumn('grace_ends_at');
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])
                ->default('pending')
                ->change();
        });
    }
};
