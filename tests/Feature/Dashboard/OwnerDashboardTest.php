<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    /**
     * Ensure owner dashboard routes are registered.
     */
    public function test_owner_dashboard_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('owner.dashboard'));
        $this->assertTrue(Route::has('owner.dashboard.filter'));
        $this->assertTrue(Route::has('owner.dashboard.export'));
    }
}
