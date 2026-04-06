<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check whether an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_roles', 'portal')) {
                $table->string('portal', 50)->default('studio')->after('name');
            }
        });

        Schema::table('tbl_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_permissions', 'portal')) {
                $table->string('portal', 50)->default('studio')->after('name');
            }
        });

        if (!$this->indexExists('tbl_roles', 'tbl_roles_portal_index')) {
            Schema::table('tbl_roles', function (Blueprint $table) {
                $table->index('portal', 'tbl_roles_portal_index');
            });
        }

        if (!$this->indexExists('tbl_permissions', 'tbl_permissions_portal_index')) {
            Schema::table('tbl_permissions', function (Blueprint $table) {
                $table->index('portal', 'tbl_permissions_portal_index');
            });
        }

        Schema::table('tbl_user_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_user_roles', 'studio_id')) {
                $table->foreignId('studio_id')->nullable()->after('role_id')->constrained('tbl_studios')->onDelete('cascade');
            }
        });

        if (!$this->indexExists('tbl_user_roles', 'tbl_user_roles_user_id_index')) {
            Schema::table('tbl_user_roles', function (Blueprint $table) {
                $table->index('user_id', 'tbl_user_roles_user_id_index');
            });
        }

        if (!$this->indexExists('tbl_user_roles', 'tbl_user_roles_role_id_index')) {
            Schema::table('tbl_user_roles', function (Blueprint $table) {
                $table->index('role_id', 'tbl_user_roles_role_id_index');
            });
        }

        DB::table('tbl_roles')
            ->whereNull('portal')
            ->orWhere('portal', '')
            ->update(['portal' => 'studio']);

        DB::table('tbl_permissions')
            ->whereNull('portal')
            ->orWhere('portal', '')
            ->update(['portal' => 'studio']);

        $userRoles = DB::table('tbl_user_roles')->select('id', 'user_id')->get();

        foreach ($userRoles as $userRole) {
            $studioId = DB::table('tbl_studio_employee_schedule')
                ->where('user_id', $userRole->user_id)
                ->value('studio_id');

            if (!$studioId) {
                $studioId = DB::table('tbl_studio_photographers')
                    ->where('photographer_id', $userRole->user_id)
                    ->value('studio_id');
            }

            if ($studioId) {
                DB::table('tbl_user_roles')
                    ->where('id', $userRole->id)
                    ->update(['studio_id' => $studioId]);
            }
        }

        if (Schema::hasTable('tbl_rbac')) {
            Schema::drop('tbl_rbac');
        }

        Schema::table('tbl_user_roles', function (Blueprint $table) {
            if ($this->indexExists('tbl_user_roles', 'tbl_user_roles_user_id_role_id_unique')) {
                $table->dropUnique('tbl_user_roles_user_id_role_id_unique');
            }

            if (!$this->indexExists('tbl_user_roles', 'tbl_user_roles_user_id_role_id_studio_id_unique')) {
                $table->unique(['user_id', 'role_id', 'studio_id'], 'tbl_user_roles_user_id_role_id_studio_id_unique');
            }

            if (!$this->indexExists('tbl_user_roles', 'tbl_user_roles_studio_id_index')) {
                $table->index('studio_id', 'tbl_user_roles_studio_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_user_roles', function (Blueprint $table) {
            if ($this->indexExists('tbl_user_roles', 'tbl_user_roles_user_id_role_id_studio_id_unique')) {
                $table->dropUnique('tbl_user_roles_user_id_role_id_studio_id_unique');
            }

            if ($this->indexExists('tbl_user_roles', 'tbl_user_roles_studio_id_index')) {
                $table->dropIndex('tbl_user_roles_studio_id_index');
            }

            if ($this->indexExists('tbl_user_roles', 'tbl_user_roles_user_id_index')) {
                $table->dropIndex('tbl_user_roles_user_id_index');
            }

            if ($this->indexExists('tbl_user_roles', 'tbl_user_roles_role_id_index')) {
                $table->dropIndex('tbl_user_roles_role_id_index');
            }

            if (!$this->indexExists('tbl_user_roles', 'tbl_user_roles_user_id_role_id_unique')) {
                $table->unique(['user_id', 'role_id'], 'tbl_user_roles_user_id_role_id_unique');
            }

            if (Schema::hasColumn('tbl_user_roles', 'studio_id')) {
                $table->dropConstrainedForeignId('studio_id');
            }
        });

        Schema::table('tbl_permissions', function (Blueprint $table) {
            if ($this->indexExists('tbl_permissions', 'tbl_permissions_portal_index')) {
                $table->dropIndex('tbl_permissions_portal_index');
            }

            if (Schema::hasColumn('tbl_permissions', 'portal')) {
                $table->dropColumn('portal');
            }
        });

        Schema::table('tbl_roles', function (Blueprint $table) {
            if ($this->indexExists('tbl_roles', 'tbl_roles_portal_index')) {
                $table->dropIndex('tbl_roles_portal_index');
            }

            if (Schema::hasColumn('tbl_roles', 'portal')) {
                $table->dropColumn('portal');
            }
        });
    }
};
