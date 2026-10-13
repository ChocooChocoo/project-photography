<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->boolean('is_freelancer')->default(false);
        });
        Schema::table('tbl_user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->dateTime('expires_at')->nullable()->index();
            // NULL booking IDs cannot enforce permanent-grant uniqueness in MySQL.
            $table->string('grant_scope', 40)->default('permanent');
            $table->dropUnique('tbl_user_roles_user_id_role_id_studio_id_unique');
            $table->unique(['user_id', 'role_id', 'studio_id', 'grant_scope'], 'user_roles_booking_unique');
        });
        (new \Database\Seeders\StudioFreelancerRoleSeeder)->run();
    }

    public function down(): void
    {
        // Revoke temporary grants before rollback; never delete working grants implicitly.
        if (DB::table('tbl_user_roles')->whereNotNull('booking_id')->exists()) {
            throw new RuntimeException('Revoke booking-scoped freelancer grants before rolling back.');
        }
        Schema::table('tbl_user_roles', function (Blueprint $table) {
            $table->dropUnique('user_roles_booking_unique');
            $table->dropIndex(['booking_id']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['booking_id', 'expires_at', 'grant_scope']);
            $table->unique(['user_id', 'role_id', 'studio_id'], 'tbl_user_roles_user_id_role_id_studio_id_unique');
        });
        Schema::table('tbl_booking_assigned_photographers', fn (Blueprint $table) => $table->dropColumn('is_freelancer'));
    }
};
