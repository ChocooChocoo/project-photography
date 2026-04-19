<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudioPhotographerDashboardTest extends TestCase
{
    /**
     * Ensure studio photographer dashboard routes are registered.
     */
    public function test_studio_photographer_dashboard_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-photographer.dashboard'));
        $this->assertTrue(Route::has('studio-photographer.dashboard.filter'));
        $this->assertTrue(Route::has('studio-photographer.dashboard.export'));
    }

    /**
     * Ensure studio photographer dashboard routes keep the dashboard view permission.
     */
    public function test_studio_photographer_dashboard_routes_require_dashboard_permission(): void
    {
        $expectedMiddleware = 'permission:studio-photographer.dashboard.view';

        foreach (['studio-photographer.dashboard', 'studio-photographer.dashboard.filter', 'studio-photographer.dashboard.export'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] should be registered.");
            $this->assertContains($expectedMiddleware, $route->gatherMiddleware());
        }
    }
}
