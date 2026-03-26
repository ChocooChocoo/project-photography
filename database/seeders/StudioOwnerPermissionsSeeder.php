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
            ['resource' => 'employees', 'action' => 'view', 'description' => 'View employee records and employee lists.'],
            ['resource' => 'employee', 'action' => 'create', 'description' => 'Create and onboard new employees.'],
            ['resource' => 'employee', 'action' => 'edit', 'description' => 'Update employee details and employee status.'],
            ['resource' => 'employee', 'action' => 'delete', 'description' => 'Delete employee accounts and related assignments.'],
            ['resource' => 'schedules', 'action' => 'manage', 'description' => 'Manage employee work schedules.'],
            ['resource' => 'payroll', 'action' => 'view', 'description' => 'View payroll settings and payroll generation data.'],
            ['resource' => 'payroll', 'action' => 'create', 'description' => 'Create payroll settings and generate payroll records.'],
            ['resource' => 'payroll', 'action' => 'edit', 'description' => 'Edit existing payroll settings.'],
            ['resource' => 'payroll', 'action' => 'update', 'description' => 'Update payroll records and payroll configuration.'],
            ['resource' => 'payroll', 'action' => 'delete', 'description' => 'Delete payroll settings.'],
            ['resource' => 'payroll', 'action' => 'manage', 'description' => 'Full payroll management access across payroll actions.'],
            ['resource' => 'dashboard', 'action' => 'view', 'description' => 'View the studio photographer dashboard.'],
            ['resource' => 'studio', 'action' => 'view', 'description' => 'View assigned studio information and details.'],
            ['resource' => 'bookings', 'action' => 'view', 'description' => 'View assigned booking lists and booking details.'],
            ['resource' => 'assignment', 'action' => 'update_status', 'description' => 'Update the status of assigned bookings.'],
            ['resource' => 'online_gallery', 'action' => 'view', 'description' => 'View online galleries for eligible assigned bookings.'],
            ['resource' => 'online_gallery', 'action' => 'create', 'description' => 'Create or upload images to online galleries.'],
            ['resource' => 'online_gallery', 'action' => 'update', 'description' => 'Update online gallery information.'],
            ['resource' => 'online_gallery', 'action' => 'delete', 'description' => 'Delete online galleries or gallery images.'],
        ];

        foreach ($permissions as $permission) {
            $resource = $this->normalizeSegment($permission['resource']);
            $action = $this->normalizeSegment($permission['action']);
            $name = $action . '_' . $resource;
            $permissionString = $resource . ':' . $action;

            DB::table('tbl_permissions')->updateOrInsert(
                ['permission_string' => $permissionString],
                [
                    'name' => $name,
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
}
