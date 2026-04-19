<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    /**
     * Ensure admin dashboard routes are registered.
     */
    public function test_admin_dashboard_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('admin.dashboard'));
        $this->assertTrue(Route::has('admin.dashboard.filter'));
        $this->assertTrue(Route::has('admin.dashboard.export'));
    }
}
