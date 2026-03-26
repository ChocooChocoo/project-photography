<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudioOwnerRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $rolePermissions = [
            'studio-hr-manager' => [
                'employees:view',
                'employee:create',
                'employee:edit',
                'employee:delete',
                'schedules:manage',
                'payroll:view',
                'payroll:create',
                'payroll:edit',
                'payroll:update',
                'payroll:delete',
                'payroll:manage',
            ],
            'studio-hr-staff' => [
                'employees:view',
                'employee:create',
                'employee:edit',
                'schedules:manage',
                'payroll:view',
                'payroll:create',
                'payroll:edit',
                'payroll:update',
            ],
            'studio-finance-manager' => [
                'payroll:view',
                'payroll:create',
                'payroll:edit',
                'payroll:update',
                'payroll:delete',
                'payroll:manage',
            ],
            'studio-finance-staff' => [
                'payroll:view',
                'payroll:create',
                'payroll:edit',
                'payroll:update',
            ],
            'studio-photographer' => [
                'dashboard:view',
                'studio:view',
                'bookings:view',
                'assignment:update_status',
                'online_gallery:view',
                'online_gallery:create',
                'online_gallery:update',
                'online_gallery:delete',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionStrings) {
            $role = DB::table('tbl_roles')->where('name', $roleName)->first();

            if (!$role) {
                $this->command?->warn("Skipped role-permission mapping. Role not found: {$roleName}");
                continue;
            }

            $permissionIds = DB::table('tbl_permissions')
                ->whereIn('permission_string', $permissionStrings)
                ->pluck('id', 'permission_string');

            foreach ($permissionStrings as $permissionString) {
                $permissionId = $permissionIds[$permissionString] ?? null;

                if (!$permissionId) {
                    $this->command?->warn("Skipped missing permission: {$permissionString}");
                    continue;
                }

                DB::table('tbl_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => $now,
                        'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                    ]
                );
            }

            $this->command?->info("Seeded role permissions for: {$roleName}");
        }
    }
}
