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
            if (Schema::hasIndex('tbl_studio_members', 'unique_studio_freelancer')) {
                $table->dropUnique('unique_studio_freelancer');
            }

            if (!Schema::hasColumn('tbl_studio_members', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            if (!Schema::hasIndex('tbl_studio_members', 'unique_studio_freelancer_deleted_at')) {
                $table->unique(['studio_id', 'freelancer_id', 'deleted_at'], 'unique_studio_freelancer_deleted_at');
            }
        });

        Schema::table('tbl_studio_photographers', function (Blueprint $table) {
            if (Schema::hasIndex('tbl_studio_photographers', 'tbl_studio_photographers_studio_id_photographer_id_unique')) {
                $table->dropUnique('tbl_studio_photographers_studio_id_photographer_id_unique');
            }

            if (!Schema::hasColumn('tbl_studio_photographers', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            if (!Schema::hasIndex('tbl_studio_photographers', 'uq_studio_photographers_scope')) {
                $table->unique(['studio_id', 'photographer_id', 'deleted_at'], 'uq_studio_photographers_scope');
            }
        });

        Schema::table('tbl_studio_employee_schedule', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_studio_employee_schedule', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_studio_employee_schedule', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_studio_employee_schedule', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('tbl_studio_photographers', function (Blueprint $table) {
            if (Schema::hasIndex('tbl_studio_photographers', 'uq_studio_photographers_scope')) {
                $table->dropUnique('uq_studio_photographers_scope');
            }

            if (Schema::hasColumn('tbl_studio_photographers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->unique(['studio_id', 'photographer_id']);
        });

        Schema::table('tbl_studio_members', function (Blueprint $table) {
            if (Schema::hasIndex('tbl_studio_members', 'unique_studio_freelancer_deleted_at')) {
                $table->dropUnique('unique_studio_freelancer_deleted_at');
            }

            if (Schema::hasColumn('tbl_studio_members', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->unique(['studio_id', 'freelancer_id'], 'unique_studio_freelancer');
        });
    }
};
