<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();

        DB::table('tbl_roles')->updateOrInsert(
            ['name' => 'owner-super-admin'],
            [
                'portal' => 'owner',
                'description' => 'Full owner portal access across studio management modules.',
                'status' => 'active',
                'is_system' => true,
                'updated_at' => $now,
                'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]
        );

        $ownerPermissions = [
            'owner.dashboard.view',
            'owner.studios.manage',
            'owner.bookings.manage',
            'owner.online-gallery.manage',
            'owner.schedules.manage',
            'owner.members.manage',
            'owner.photographers.manage',
            'owner.employees.manage',
            'owner.leave-requests.manage',
            'owner.overtime-requests.manage',
            'owner.roles.manage',
            'owner.permissions.manage',
            'owner.payroll.manage',
            'owner.packages.manage',
            'owner.services.manage',
            'owner.subscription.manage',
            'owner.inquiries.manage',
            'owner.chatbot.manage',
        ];

        foreach ($ownerPermissions as $permissionString) {
            $segments = explode('.', $permissionString);
            $portal = $segments[0] ?? 'owner';
            $resource = $segments[1] ?? 'dashboard';
            $action = $segments[2] ?? 'view';

            DB::table('tbl_permissions')->updateOrInsert(
                ['permission_string' => $permissionString],
                [
                    'name' => $portal . '_' . $action . '_' . str_replace('-', '_', $resource),
                    'portal' => $portal,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => ucwords(str_replace(['.', '-'], ' ', $permissionString)),
                    'status' => 'active',
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        $roleId = DB::table('tbl_roles')->where('name', 'owner-super-admin')->value('id');
        $permissionIds = DB::table('tbl_permissions')->whereIn('permission_string', $ownerPermissions)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('tbl_role_permissions')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        $ownedStudios = DB::table('tbl_studios')
            ->select('id', 'user_id')
            ->whereNotNull('user_id')
            ->get();

        foreach ($ownedStudios as $studio) {
            DB::table('tbl_user_roles')->updateOrInsert(
                [
                    'user_id' => $studio->user_id,
                    'role_id' => $roleId,
                    'studio_id' => $studio->id,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        if (Schema::hasTable('tbl_rbac')) {
            Schema::drop('tbl_rbac');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_rbac');

        $roleId = DB::table('tbl_roles')->where('name', 'owner-super-admin')->value('id');
        $ownerPermissionStrings = [
            'owner.dashboard.view',
            'owner.studios.manage',
            'owner.bookings.manage',
            'owner.online-gallery.manage',
            'owner.schedules.manage',
            'owner.members.manage',
            'owner.photographers.manage',
            'owner.employees.manage',
            'owner.leave-requests.manage',
            'owner.overtime-requests.manage',
            'owner.roles.manage',
            'owner.permissions.manage',
            'owner.payroll.manage',
            'owner.packages.manage',
            'owner.services.manage',
            'owner.subscription.manage',
            'owner.inquiries.manage',
            'owner.chatbot.manage',
        ];

        if ($roleId) {
            DB::table('tbl_user_roles')->where('role_id', $roleId)->delete();
            DB::table('tbl_role_permissions')->where('role_id', $roleId)->delete();
            DB::table('tbl_roles')->where('id', $roleId)->delete();
        }

        DB::table('tbl_permissions')->whereIn('permission_string', $ownerPermissionStrings)->delete();
    }
};
