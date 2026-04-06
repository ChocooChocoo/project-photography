<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudioOwnerRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $roles = [
            [
                'name' => 'owner-super-admin',
                'portal' => 'owner',
                'description' => 'Full owner portal access across studio management modules.',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'name' => 'studio-hr-manager',
                'portal' => 'studio-hr',
                'description' => 'Oversees all human resources functions in the studio, including hiring, employee relations, policy implementation, and performance management. Ensures a positive work environment and compliance with labor laws.',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'name' => 'studio-hr-staff',
                'portal' => 'studio-hr',
                'description' => 'Supports HR operations by assisting with recruitment, onboarding, maintaining employee records, and handling basic employee concerns. Works under the HR Manager.',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'name' => 'studio-finance-manager',
                'portal' => 'studio-finance',
                'description' => 'Manages the studio\'s financial activities such as budgeting, financial planning, reporting, and expense control. Ensures the studio stays financially healthy and compliant with regulations.',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'name' => 'studio-finance-staff',
                'portal' => 'studio-finance',
                'description' => 'Handles day-to-day financial tasks like processing payments, recording transactions, preparing invoices, and assisting with financial reports under the Finance Manager.',
                'status' => 'active',
                'is_system' => true,
            ],
            [
                'name' => 'studio-photographer',
                'portal' => 'studio-photographer',
                'description' => 'Captures photos for studio projects, including shoots, editing, and ensuring high-quality visual output that meets client or creative requirements.',
                'status' => 'active',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            DB::table('tbl_roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'portal' => $role['portal'],
                    'description' => $role['description'],
                    'status' => $role['status'],
                    'is_system' => $role['is_system'],
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );

            $this->command?->info("Seeded role: {$role['name']}");
        }
    }
}
