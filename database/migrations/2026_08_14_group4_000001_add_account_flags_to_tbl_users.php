<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('status');
            $table->timestamp('onboarding_completed_at')->nullable()->after('must_change_password');
            $table->timestamp('deleted_at')->nullable()->after('onboarding_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['must_change_password', 'onboarding_completed_at', 'deleted_at']);
        });
    }
};
