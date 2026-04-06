<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudioUserRoleAssignmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $roles = DB::table('tbl_roles')
            ->whereIn('name', [
                'owner-super-admin',
                'studio-hr-manager',
                'studio-hr-staff',
                'studio-finance-manager',
                'studio-finance-staff',
                'studio-photographer',
            ])
            ->pluck('id', 'name');

        if ($roles->isEmpty()) {
            $this->command?->warn('No RBAC roles found. Run the roles seeder first.');
            return;
        }

        $this->seedOwnerAssignments($roles->all(), $now);
        $this->seedEmployeeAssignments($roles->all(), $now);
        $this->seedPhotographerAssignments($roles->all(), $now);
    }

    /**
     * Seed owner role assignments per owned studio.
     *
     * @param array<string, int> $roles
     */
    private function seedOwnerAssignments(array $roles, Carbon $now): void
    {
        $ownerRoleId = $roles['owner-super-admin'] ?? null;

        if (!$ownerRoleId) {
            return;
        }

        $studios = DB::table('tbl_studios')
            ->select('id', 'user_id')
            ->whereNotNull('user_id')
            ->get();

        foreach ($studios as $studio) {
            DB::table('tbl_user_roles')->updateOrInsert(
                [
                    'user_id' => $studio->user_id,
                    'role_id' => $ownerRoleId,
                    'studio_id' => $studio->id,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        $this->command?->info('Seeded owner role assignments.');
    }

    /**
     * Seed HR and finance role assignments from employee schedules.
     *
     * @param array<string, int> $roles
     */
    private function seedEmployeeAssignments(array $roles, Carbon $now): void
    {
        $scheduledEmployees = DB::table('tbl_studio_employee_schedule')
            ->join('tbl_users', 'tbl_users.id', '=', 'tbl_studio_employee_schedule.user_id')
            ->select(
                'tbl_users.id as user_id',
                'tbl_users.role as base_role',
                'tbl_users.user_type',
                'tbl_studio_employee_schedule.studio_id'
            )
            ->whereIn('tbl_users.role', ['studio-hr', 'studio-finance'])
            ->get();

        foreach ($scheduledEmployees as $employee) {
            $roleName = $this->resolveStaffRoleName($employee->base_role, $employee->user_type);
            $roleId = $roles[$roleName] ?? null;

            if (!$roleId) {
                continue;
            }

            DB::table('tbl_user_roles')->updateOrInsert(
                [
                    'user_id' => $employee->user_id,
                    'role_id' => $roleId,
                    'studio_id' => $employee->studio_id,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        $this->command?->info('Seeded HR and finance role assignments.');
    }

    /**
     * Seed photographer role assignments.
     *
     * @param array<string, int> $roles
     */
    private function seedPhotographerAssignments(array $roles, Carbon $now): void
    {
        $photographerRoleId = $roles['studio-photographer'] ?? null;

        if (!$photographerRoleId) {
            return;
        }

        $photographerAssignments = DB::table('tbl_studio_photographers')
            ->select('photographer_id as user_id', 'studio_id')
            ->get();

        foreach ($photographerAssignments as $assignment) {
            DB::table('tbl_user_roles')->updateOrInsert(
                [
                    'user_id' => $assignment->user_id,
                    'role_id' => $photographerRoleId,
                    'studio_id' => $assignment->studio_id,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        $unscheduledPhotographers = DB::table('tbl_users')
            ->leftJoin('tbl_user_roles', function ($join) use ($photographerRoleId) {
                $join->on('tbl_user_roles.user_id', '=', 'tbl_users.id')
                    ->where('tbl_user_roles.role_id', '=', $photographerRoleId);
            })
            ->leftJoin('tbl_studio_employee_schedule', 'tbl_studio_employee_schedule.user_id', '=', 'tbl_users.id')
            ->select('tbl_users.id as user_id', 'tbl_studio_employee_schedule.studio_id')
            ->where('tbl_users.role', 'studio-photographer')
            ->whereNull('tbl_user_roles.id')
            ->whereNotNull('tbl_studio_employee_schedule.studio_id')
            ->get();

        foreach ($unscheduledPhotographers as $photographer) {
            DB::table('tbl_user_roles')->updateOrInsert(
                [
                    'user_id' => $photographer->user_id,
                    'role_id' => $photographerRoleId,
                    'studio_id' => $photographer->studio_id,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );
        }

        $this->command?->info('Seeded photographer role assignments.');
    }

    /**
     * Resolve a staff RBAC role name from base role and user_type.
     */
    private function resolveStaffRoleName(string $baseRole, string $userType): string
    {
        $isManager = strtolower($userType) === 'manager';

        return match ($baseRole) {
            'studio-hr' => $isManager ? 'studio-hr-manager' : 'studio-hr-staff',
            'studio-finance' => $isManager ? 'studio-finance-manager' : 'studio-finance-staff',
            default => '',
        };
    }
}
