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
            'owner-super-admin' => [
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
            ],
            'studio-hr-manager' => [
                'studio-hr.dashboard.view',
                'studio-hr.employees.view',
                'studio-hr.employee.create',
                'studio-hr.employee.edit',
                'studio-hr.employee.delete',
                'studio-hr.leave-requests.manage',
                'studio-hr.overtime-requests.manage',
                'studio-hr.schedules.manage',
                'studio-hr.attendance.view',
                'studio-hr.payroll.view',
                'studio-hr.payroll.create',
                'studio-hr.payroll.edit',
                'studio-hr.payroll.update',
                'studio-hr.payroll.delete',
                'studio-hr.payroll.manage',
                'studio-hr.generate-payroll.manage',
            ],
            'studio-hr-staff' => [
                'studio-hr.dashboard.view',
                'studio-hr.employees.view',
                'studio-hr.employee.create',
                'studio-hr.employee.edit',
                'studio-hr.leave-requests.manage',
                'studio-hr.overtime-requests.manage',
                'studio-hr.schedules.manage',
                'studio-hr.attendance.view',
                'studio-hr.payroll.view',
                'studio-hr.payroll.create',
                'studio-hr.payroll.edit',
                'studio-hr.payroll.update',
                'studio-hr.generate-payroll.manage',
            ],
            'studio-finance-manager' => [
                'studio-finance.dashboard.view',
                'studio-finance.leave-requests.manage',
                'studio-finance.overtime-requests.manage',
                'studio-finance.attendance.view',
                'studio-finance.payroll.view',
                'studio-finance.payroll.create',
                'studio-finance.payroll.edit',
                'studio-finance.payroll.update',
                'studio-finance.payroll.delete',
                'studio-finance.payroll.approve',
                'studio-finance.payroll.reject',
                'studio-finance.payroll.manage',
            ],
            'studio-finance-staff' => [
                'studio-finance.dashboard.view',
                'studio-finance.leave-requests.manage',
                'studio-finance.overtime-requests.manage',
                'studio-finance.attendance.view',
                'studio-finance.payroll.view',
                'studio-finance.payroll.create',
                'studio-finance.payroll.edit',
                'studio-finance.payroll.update',
            ],
            'studio-photographer' => [
                'studio-photographer.dashboard.view',
                'studio-photographer.leave-requests.manage',
                'studio-photographer.overtime-requests.manage',
                'studio-photographer.attendance.view',
                'studio-photographer.studio.view',
                'studio-photographer.bookings.view',
                'studio-photographer.assignment.update_status',
                'studio-photographer.online_gallery.view',
                'studio-photographer.online_gallery.create',
                'studio-photographer.online_gallery.update',
                'studio-photographer.online_gallery.delete',
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
