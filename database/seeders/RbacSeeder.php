<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            StudioOwnerRolesSeeder::class,
            StudioOwnerPermissionsSeeder::class,
            StudioOwnerRolePermissionSeeder::class,
            StudioUserRoleAssignmentsSeeder::class,
        ]);
    }
}
