<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StudioOwnerRolesSeeder::class,
            StudioOwnerPermissionsSeeder::class,
            StudioOwnerRolePermissionSeeder::class,
            StudioEmployeesSeeder::class,
            StudioEmployeePayrollSeeder::class,
            StudioEmployeeAttendanceSeeder::class,
            WeddingPackagesSeeder::class,
            EventPackagesSeeder::class,
            FamilyPortraitPackagesSeeder::class,
            ProductPackagesSeeder::class,
            PetPackagesSeeder::class,
            FashionPackagesSeeder::class,
        ]);
    }
}
