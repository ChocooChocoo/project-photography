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
        Schema::table('tbl_roles', function (Blueprint $table) {
            $table->dropUnique('tbl_roles_name_unique');
            $table->softDeletes()->after('updated_at');
            $table->unique(['name', 'deleted_at'], 'tbl_roles_name_deleted_at_unique');
        });

        Schema::table('tbl_permissions', function (Blueprint $table) {
            $table->dropUnique('tbl_permissions_name_unique');
            $table->dropUnique('tbl_permissions_permission_string_unique');
            $table->softDeletes()->after('updated_at');
            $table->unique(['name', 'deleted_at'], 'tbl_permissions_name_deleted_at_unique');
            $table->unique(['permission_string', 'deleted_at'], 'tbl_permissions_permission_string_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_permissions', function (Blueprint $table) {
            $table->dropUnique('tbl_permissions_permission_string_deleted_at_unique');
            $table->dropUnique('tbl_permissions_name_deleted_at_unique');
            $table->dropSoftDeletes();
            $table->unique('name');
            $table->unique('permission_string', 'tbl_permissions_permission_string_unique');
        });

        Schema::table('tbl_roles', function (Blueprint $table) {
            $table->dropUnique('tbl_roles_name_deleted_at_unique');
            $table->dropSoftDeletes();
            $table->unique('name');
        });
    }
};
