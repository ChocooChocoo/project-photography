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
        Schema::table('tbl_permissions', function (Blueprint $table) {
            $table->string('resource', 100)->nullable()->after('name');
            $table->string('action', 50)->nullable()->after('resource');
            $table->string('permission_string', 150)->nullable()->after('action');
        });

        DB::table('tbl_permissions')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function ($permission) {
                $segments = explode('_', (string) $permission->name, 2);
                $action = $segments[0] ?? '';
                $resource = $segments[1] ?? (string) $permission->name;
                $permissionString = $resource . ':' . $action;

                DB::table('tbl_permissions')
                    ->where('id', $permission->id)
                    ->update([
                        'resource' => $resource,
                        'action' => $action,
                        'permission_string' => $permissionString,
                    ]);
            });

        Schema::table('tbl_permissions', function (Blueprint $table) {
            $table->unique('permission_string', 'tbl_permissions_permission_string_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_permissions', function (Blueprint $table) {
            $table->dropUnique('tbl_permissions_permission_string_unique');
            $table->dropColumn(['resource', 'action', 'permission_string']);
        });
    }
};
