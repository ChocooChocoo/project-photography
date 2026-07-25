<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudioOwnerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $permissions = [
            ['portal' => 'owner', 'permission_string' => 'owner.dashboard.view', 'resource' => 'dashboard', 'action' => 'view', 'description' => 'View the owner dashboard.'],
            ['portal' => 'owner', 'permission_string' => 'owner.studios.manage', 'resource' => 'studios', 'action' => 'manage', 'description' => 'Manage studios in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.bookings.manage', 'resource' => 'bookings', 'action' => 'manage', 'description' => 'Manage booking workflows in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.online-gallery.manage', 'resource' => 'online-gallery', 'action' => 'manage', 'description' => 'Manage online galleries in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.schedules.manage', 'resource' => 'schedules', 'action' => 'manage', 'description' => 'Manage studio schedules in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.members.manage', 'resource' => 'members', 'action' => 'manage', 'description' => 'Manage members in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.photographers.manage', 'resource' => 'photographers', 'action' => 'manage', 'description' => 'Manage studio photographers in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.employees.manage', 'resource' => 'employees', 'action' => 'manage', 'description' => 'Manage employees in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.leave-requests.manage', 'resource' => 'leave-requests', 'action' => 'manage', 'description' => 'Manage HR leave requests in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.overtime-requests.manage', 'resource' => 'overtime-requests', 'action' => 'manage', 'description' => 'Manage HR overtime requests in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.roles.manage', 'resource' => 'roles', 'action' => 'manage', 'description' => 'Manage RBAC roles in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.permissions.manage', 'resource' => 'permissions', 'action' => 'manage', 'description' => 'Manage RBAC permissions in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.payroll.manage', 'resource' => 'payroll', 'action' => 'manage', 'description' => 'Manage payroll settings in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.packages.manage', 'resource' => 'packages', 'action' => 'manage', 'description' => 'Manage packages in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.services.manage', 'resource' => 'services', 'action' => 'manage', 'description' => 'Manage services in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.subscription.manage', 'resource' => 'subscription', 'action' => 'manage', 'description' => 'Manage subscriptions in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.chatbot.manage', 'resource' => 'chatbot', 'action' => 'manage', 'description' => 'Manage chatbot configuration and conversations in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.procurement.view', 'resource' => 'procurement', 'action' => 'view', 'description' => 'View procurement oversight records in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.procurement.approve', 'resource' => 'procurement', 'action' => 'approve', 'description' => 'Approve, reject, or return procurement requests in the owner portal.'],
            ['portal' => 'owner', 'permission_string' => 'owner.procurement.report', 'resource' => 'procurement', 'action' => 'report', 'description' => 'View procurement oversight summaries and reports in the owner portal.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.dashboard.view', 'resource' => 'dashboard', 'action' => 'view', 'description' => 'View the HR dashboard.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.employees.view', 'resource' => 'employees', 'action' => 'view', 'description' => 'View employee records and employee lists.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.employee.create', 'resource' => 'employee', 'action' => 'create', 'description' => 'Create and onboard new employees.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.employee.edit', 'resource' => 'employee', 'action' => 'edit', 'description' => 'Update employee details and employee status.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.employee.delete', 'resource' => 'employee', 'action' => 'delete', 'description' => 'Delete employee accounts and related assignments.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.leave-requests.manage', 'resource' => 'leave-requests', 'action' => 'manage', 'description' => 'Manage HR leave request screens and employee leave request workflows.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.overtime-requests.manage', 'resource' => 'overtime-requests', 'action' => 'manage', 'description' => 'Manage HR overtime request screens and employee overtime request workflows.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.schedules.manage', 'resource' => 'schedules', 'action' => 'manage', 'description' => 'Manage employee work schedules.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.attendance.view', 'resource' => 'attendance', 'action' => 'view', 'description' => 'View HR attendance dashboards and attendance records.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.payroll.view', 'resource' => 'payroll', 'action' => 'view', 'description' => 'View payroll settings and payroll generation data.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.payroll.create', 'resource' => 'payroll', 'action' => 'create', 'description' => 'Create payroll settings and generate payroll records.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.payroll.edit', 'resource' => 'payroll', 'action' => 'edit', 'description' => 'Edit existing payroll settings.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.payroll.update', 'resource' => 'payroll', 'action' => 'update', 'description' => 'Update payroll records and payroll configuration.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.payroll.delete', 'resource' => 'payroll', 'action' => 'delete', 'description' => 'Delete payroll settings.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.payroll.manage', 'resource' => 'payroll', 'action' => 'manage', 'description' => 'Full payroll management access across payroll actions.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.generate-payroll.manage', 'resource' => 'generate-payroll', 'action' => 'manage', 'description' => 'Generate and review payroll runs in the HR portal.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.procurement.manage', 'resource' => 'procurement', 'action' => 'manage', 'description' => 'Create and manage procurement requests in the HR portal.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.dashboard.view', 'resource' => 'dashboard', 'action' => 'view', 'description' => 'View the finance dashboard.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.leave-requests.manage', 'resource' => 'leave-requests', 'action' => 'manage', 'description' => 'Manage finance leave request screens.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.overtime-requests.manage', 'resource' => 'overtime-requests', 'action' => 'manage', 'description' => 'Manage finance overtime request screens.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.attendance.view', 'resource' => 'attendance', 'action' => 'view', 'description' => 'View finance attendance screens and attendance details.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.view', 'resource' => 'payroll', 'action' => 'view', 'description' => 'View payroll approval queues and payroll records.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.create', 'resource' => 'payroll', 'action' => 'create', 'description' => 'Create finance-side payroll records when allowed.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.edit', 'resource' => 'payroll', 'action' => 'edit', 'description' => 'Edit finance payroll records when allowed.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.update', 'resource' => 'payroll', 'action' => 'update', 'description' => 'Update finance payroll workflows and status data.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.delete', 'resource' => 'payroll', 'action' => 'delete', 'description' => 'Delete finance payroll records when allowed.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.approve', 'resource' => 'payroll', 'action' => 'approve', 'description' => 'Approve payroll submissions.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.reject', 'resource' => 'payroll', 'action' => 'reject', 'description' => 'Reject payroll submissions.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.payroll.manage', 'resource' => 'payroll', 'action' => 'manage', 'description' => 'Full payroll approval management access.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.procurement.view', 'resource' => 'procurement', 'action' => 'view', 'description' => 'View procurement queues in the finance portal.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.procurement.review', 'resource' => 'procurement', 'action' => 'review', 'description' => 'Review procurement requests in the finance portal.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.procurement.order', 'resource' => 'procurement', 'action' => 'order', 'description' => 'Generate purchase orders and record deliveries in the finance portal.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.procurement.payment', 'resource' => 'procurement', 'action' => 'payment', 'description' => 'Process procurement payments in the finance portal.'],
            ['portal' => 'studio-finance', 'permission_string' => 'studio-finance.inventory.manage', 'resource' => 'inventory', 'action' => 'manage', 'description' => 'Manage inventory records created through procurement.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.dashboard.view', 'resource' => 'dashboard', 'action' => 'view', 'description' => 'View the studio photographer dashboard.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.leave-requests.manage', 'resource' => 'leave-requests', 'action' => 'manage', 'description' => 'Manage photographer leave request screens.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.overtime-requests.manage', 'resource' => 'overtime-requests', 'action' => 'manage', 'description' => 'Manage photographer overtime request screens.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.attendance.view', 'resource' => 'attendance', 'action' => 'view', 'description' => 'View photographer attendance screens.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.studio.view', 'resource' => 'studio', 'action' => 'view', 'description' => 'View assigned studio information and details.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.bookings.view', 'resource' => 'bookings', 'action' => 'view', 'description' => 'View assigned booking lists and booking details.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.assignment.update_status', 'resource' => 'assignment', 'action' => 'update_status', 'description' => 'Update the status of assigned bookings.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.online_gallery.view', 'resource' => 'online_gallery', 'action' => 'view', 'description' => 'View online galleries for eligible assigned bookings.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.online_gallery.create', 'resource' => 'online_gallery', 'action' => 'create', 'description' => 'Create or upload images to online galleries.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.online_gallery.update', 'resource' => 'online_gallery', 'action' => 'update', 'description' => 'Update online gallery information.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.online_gallery.delete', 'resource' => 'online_gallery', 'action' => 'delete', 'description' => 'Delete online galleries or gallery images.'],
            ['portal' => 'studio-photographer', 'permission_string' => 'studio-photographer.procurement.manage', 'resource' => 'procurement', 'action' => 'manage', 'description' => 'Create and manage procurement requests in the photographer portal.'],
        ];

        foreach ($permissions as $permission) {
            $portal = $permission['portal'];
            $resource = $this->normalizeSegment($permission['resource']);
            $action = $this->normalizeSegment($permission['action']);
            $name = $this->buildUniquePermissionName($portal, $action, $resource);
            $permissionString = $permission['permission_string'] ?? $portal.'.'.str_replace('_', '-', $resource).'.'.$action;

            DB::table('tbl_permissions')->updateOrInsert(
                ['permission_string' => $permissionString],
                [
                    'name' => $name,
                    'portal' => $portal,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $permission['description'],
                    'status' => 'active',
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );

            $this->command?->info("Seeded permission: {$permissionString}");
        }
    }

    /**
     * Normalize a permission resource or action segment.
     */
    private function normalizeSegment(string $value): string
    {
        $normalizedValue = strtolower(trim($value));
        $normalizedValue = preg_replace('/[^a-z0-9]+/', '_', $normalizedValue) ?? '';

        return trim($normalizedValue, '_');
    }

    /**
     * Build a globally unique permission name across portals.
     */
    private function buildUniquePermissionName(string $portal, string $action, string $resource): string
    {
        return $this->normalizeSegment($portal).'_'.$action.'_'.$resource;
    }
}
