<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Reference data first: everything below keys off these rows.
            CaviteLocationSeeder::class,
            CategorySeeder::class,
            RbacSeeder::class,

            // Prism & Pine must exist, and own packages and services, before
            // StudioEmployeesSeeder can attach staff to a non-bundled studio.
            PrismPineStudioSeeder::class,
            WeddingPackagesSeeder::class,
            EventPackagesSeeder::class,
            FamilyPortraitPackagesSeeder::class,
            ProductPackagesSeeder::class,
            PetPackagesSeeder::class,
            FashionPackagesSeeder::class,
            PrismPineStudioDataSyncSeeder::class,

            FreelancerMarketplaceBundleSeeder::class,
            StudioEmployeesSeeder::class,
            MultiStudioBundleSeeder::class,
            StudioEmployeePayrollSeeder::class,
            StudioEmployeeAttendanceSeeder::class,
            StudioPhotographerAttendanceSeeder::class,
            EmployeeAttendanceLeaveOvertimePayrollSeeder::class,
            AprilAttendanceAndCompletedBookingsSeeder::class,
            SnapshotNormalizationRepairSeeder::class,
            BookingDataIntegrityRepairSeeder::class,
            ChatbotDefaultConfigSeeder::class,
            ProcurementWorkflowSeeder::class,
            CoverageGapsAndAdminSeeder::class,
        ]);
    }
}
