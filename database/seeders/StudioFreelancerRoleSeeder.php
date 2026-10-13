<?php

namespace Database\Seeders;

use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use Illuminate\Database\Seeder;

class StudioFreelancerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = RoleModel::firstOrCreate(['name' => 'studio-freelancer'], [
            'portal' => 'freelancer', 'description' => 'Temporary work on one assigned studio booking.',
            'status' => 'active', 'is_system' => true,
        ]);
        foreach (['view', 'update-status', 'upload'] as $action) {
            $permission = PermissionModel::firstOrCreate(['permission_string' => 'freelancer.studio-assignments.'.$action], [
                'name' => 'freelancer_studio_assignments_'.str_replace('-', '_', $action),
                'portal' => 'freelancer', 'resource' => 'studio-assignments', 'action' => $action,
                'description' => 'Assigned studio booking: '.$action, 'status' => 'active',
            ]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
