<?php

namespace Tests\Feature\Procurement;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProcurementRoutesTest extends TestCase
{
    /**
     * It registers procurement routes for all involved portals.
     */
    public function test_procurement_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-hr.procurement.index'));
        $this->assertTrue(Route::has('studio-photographer.procurement.index'));
        $this->assertTrue(Route::has('studio-finance.procurement.index'));
        $this->assertTrue(Route::has('owner.procurement.index'));
    }

    /**
     * It registers the overdue procurement escalation command.
     */
    public function test_procurement_escalation_command_is_available(): void
    {
        $this->assertArrayHasKey('procurement:escalate-overdue', Artisan::all());
    }
}
