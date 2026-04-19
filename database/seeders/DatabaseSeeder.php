<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            FreelancerMarketplaceBundleSeeder::class,
            StudioEmployeesSeeder::class,
            MultiStudioBundleSeeder::class,
            StudioEmployeePayrollSeeder::class,
            StudioEmployeeAttendanceSeeder::class,
            StudioPhotographerAttendanceSeeder::class,
            EmployeeAttendanceLeaveOvertimePayrollSeeder::class,
            AprilAttendanceAndCompletedBookingsSeeder::class,
            WeddingPackagesSeeder::class,
            EventPackagesSeeder::class,
            FamilyPortraitPackagesSeeder::class,
            ProductPackagesSeeder::class,
            PetPackagesSeeder::class,
            FashionPackagesSeeder::class,
            PrismPineStudioDataSyncSeeder::class,
            SnapshotNormalizationRepairSeeder::class,
            BookingDataIntegrityRepairSeeder::class,
            ChatbotDefaultConfigSeeder::class,
            ProcurementWorkflowSeeder::class,
        ]);
    }
}
