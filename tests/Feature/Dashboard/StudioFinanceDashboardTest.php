<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudioFinanceDashboardTest extends TestCase
{
    /**
     * Ensure finance dashboard routes are registered.
     */
    public function test_studio_finance_dashboard_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-finance.dashboard'));
        $this->assertTrue(Route::has('studio-finance.dashboard.filter'));
        $this->assertTrue(Route::has('studio-finance.dashboard.export'));
    }

    /**
     * Ensure finance dashboard routes keep the dashboard view permission.
     */
    public function test_studio_finance_dashboard_routes_require_dashboard_permission(): void
    {
        $expectedMiddleware = 'permission:studio-finance.dashboard.view';

        foreach (['studio-finance.dashboard', 'studio-finance.dashboard.filter', 'studio-finance.dashboard.export'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] should be registered.");
            $this->assertContains($expectedMiddleware, $route->gatherMiddleware());
        }
    }
}
