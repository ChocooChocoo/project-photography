<?php

namespace Tests\Feature\Payroll;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GeneratePayrollRoutesTest extends TestCase
{
    /**
     * It registers the studio HR generate payroll routes.
     */
    public function test_generate_payroll_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-hr.generate-payroll.index'));
        $this->assertTrue(Route::has('studio-hr.generate-payroll.employees'));
        $this->assertTrue(Route::has('studio-hr.generate-payroll.store'));
        $this->assertTrue(Route::has('studio-hr.generate-payroll.show'));
    }

    /**
     * It aligns studio HR generate payroll routes with seeded payroll permissions.
     */
    public function test_generate_payroll_routes_use_seeded_payroll_permissions(): void
    {
        $expectedRoutePermissions = [
            'studio-hr.generate-payroll.index' => 'permission:studio-hr.payroll.view,studio-hr.payroll.manage,studio-hr.generate-payroll.manage',
            'studio-hr.generate-payroll.employees' => 'permission:studio-hr.payroll.view,studio-hr.payroll.manage,studio-hr.generate-payroll.manage',
            'studio-hr.generate-payroll.store' => 'permission:studio-hr.payroll.create,studio-hr.payroll.manage,studio-hr.generate-payroll.manage',
            'studio-hr.generate-payroll.show' => 'permission:studio-hr.payroll.view,studio-hr.payroll.manage,studio-hr.generate-payroll.manage',
        ];

        foreach ($expectedRoutePermissions as $routeName => $expectedPermissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] should be registered.");
            $this->assertContains(
                $expectedPermissionMiddleware,
                $route->gatherMiddleware(),
                "Route [{$routeName}] should use [{$expectedPermissionMiddleware}]."
            );
        }
    }
}
