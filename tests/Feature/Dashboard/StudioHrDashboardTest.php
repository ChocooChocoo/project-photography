<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudioHrDashboardTest extends TestCase
{
    /**
     * Ensure HR dashboard routes are registered.
     */
    public function test_studio_hr_dashboard_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-hr.dashboard'));
        $this->assertTrue(Route::has('studio-hr.dashboard.filter'));
        $this->assertTrue(Route::has('studio-hr.dashboard.export'));
    }

    /**
     * Ensure HR dashboard routes keep the dashboard view permission.
     */
    public function test_studio_hr_dashboard_routes_require_dashboard_permission(): void
    {
        $expectedMiddleware = 'permission:studio-hr.dashboard.view';

        foreach (['studio-hr.dashboard', 'studio-hr.dashboard.filter', 'studio-hr.dashboard.export'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] should be registered.");
            $this->assertContains($expectedMiddleware, $route->gatherMiddleware());
        }
    }
}
