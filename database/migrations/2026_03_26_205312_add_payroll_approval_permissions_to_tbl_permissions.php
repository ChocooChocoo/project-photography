<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = now();

        $permissions = [
            [
                'name' => 'approve_payroll',
                'resource' => 'payroll',
                'action' => 'approve',
                'permission_string' => 'payroll:approve',
                'description' => 'Approve generated payroll records from the finance module.',
                'status' => 'active',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'reject_payroll',
                'resource' => 'payroll',
                'action' => 'reject',
                'permission_string' => 'payroll:reject',
                'description' => 'Reject generated payroll records and provide rejection reasons.',
                'status' => 'active',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('tbl_permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tbl_permissions')
            ->whereIn('name', ['approve_payroll', 'reject_payroll'])
            ->delete();
    }
};
