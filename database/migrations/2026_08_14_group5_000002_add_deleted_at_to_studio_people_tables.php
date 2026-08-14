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
        Schema::table('tbl_studio_members', function (Blueprint $table) {
            $table->dropUnique('unique_studio_freelancer');
            $table->softDeletes()->after('updated_at');
            $table->unique(['studio_id', 'freelancer_id', 'deleted_at'], 'unique_studio_freelancer_deleted_at');
        });

        Schema::table('tbl_studio_photographers', function (Blueprint $table) {
            $table->dropUnique('tbl_studio_photographers_studio_id_photographer_id_unique');
            $table->softDeletes()->after('updated_at');
            $table->unique(['studio_id', 'photographer_id', 'deleted_at'], 'tbl_studio_photographers_studio_id_photographer_id_deleted_at_unique');
        });

        Schema::table('tbl_studio_employee_schedule', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_studio_employee_schedule', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tbl_studio_photographers', function (Blueprint $table) {
            $table->dropUnique('tbl_studio_photographers_studio_id_photographer_id_deleted_at_unique');
            $table->dropSoftDeletes();
            $table->unique(['studio_id', 'photographer_id']);
        });

        Schema::table('tbl_studio_members', function (Blueprint $table) {
            $table->dropUnique('unique_studio_freelancer_deleted_at');
            $table->dropSoftDeletes();
            $table->unique(['studio_id', 'freelancer_id'], 'unique_studio_freelancer');
        });
    }
};
