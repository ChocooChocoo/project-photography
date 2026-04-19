<?php

namespace Tests\Feature\Payroll;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PayrollSettingsRoutesTest extends TestCase
{
    /**
     * It registers the studio HR payroll settings routes.
     */
    public function test_payroll_settings_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-hr.payroll-settings.index'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.create'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.store'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.employees'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.data'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.show'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.edit'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.update'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.status'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.destroy'));
        $this->assertTrue(Route::has('studio-hr.payroll-settings.bulk-store'));
    }

    /**
     * It aligns studio HR payroll settings routes with seeded payroll permissions.
     */
    public function test_payroll_settings_routes_use_seeded_payroll_permissions(): void
    {
        $expectedRoutePermissions = [
            'studio-hr.payroll-settings.index' => 'permission:studio-hr.payroll.view,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.create' => 'permission:studio-hr.payroll.create,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.store' => 'permission:studio-hr.payroll.create,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.employees' => 'permission:studio-hr.payroll.view,studio-hr.payroll.create,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.data' => 'permission:studio-hr.payroll.view,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.show' => 'permission:studio-hr.payroll.view,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.edit' => 'permission:studio-hr.payroll.edit,studio-hr.payroll.update,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.update' => 'permission:studio-hr.payroll.edit,studio-hr.payroll.update,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.status' => 'permission:studio-hr.payroll.edit,studio-hr.payroll.update,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.destroy' => 'permission:studio-hr.payroll.delete,studio-hr.payroll.manage',
            'studio-hr.payroll-settings.bulk-store' => 'permission:studio-hr.payroll.create,studio-hr.payroll.manage',
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
