<?php

namespace Tests\Feature\StudioManagement;

use App\Models\Admin\LocationModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudioBarangayLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_municipality_with_spaces_returns_stable_barangays_and_zip(): void
    {
        $owner = $this->createOwner();
        LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'New Town',
            'barangay' => ['Zulu', 'Alpha', 'alpha'],
            'zip_code' => '4100',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.studio.get-barangays', ['municipality' => 'New Town']))
            ->assertOk()
            ->assertExactJson([
                'barangays' => ['Alpha', 'alpha', 'Zulu'],
                'zip_code' => '4100',
            ]);
    }

    public function test_unknown_and_inactive_municipalities_return_empty_lookup(): void
    {
        $owner = $this->createOwner();
        LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'Hidden Town',
            'barangay' => ['Secret'],
            'zip_code' => '4101',
            'status' => 'inactive',
        ]);

        foreach (['Hidden Town', 'Missing Town'] as $municipality) {
            $this->actingAs($owner)
                ->get(route('owner.studio.get-barangays', ['municipality' => $municipality]))
                ->assertOk()
                ->assertExactJson(['barangays' => [], 'zip_code' => null]);
        }
    }

    public function test_lookup_keeps_owner_authentication_but_does_not_require_studio_permission_or_subscription(): void
    {
        $lookup = Route::getRoutes()->getByName('owner.studio.get-barangays');
        $create = Route::getRoutes()->getByName('owner.studio.create');
        $middleware = $lookup->gatherMiddleware();
        $createMiddleware = $create->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains(\App\Http\Middleware\OwnerMiddleware::class, $middleware);
        $this->assertNotContains('permission:owner.studios.manage', $middleware);
        $this->assertNotContains(\App\Http\Middleware\EnforceStudioSubscriptionAccess::class, $createMiddleware);
        $this->assertNotContains('permission:owner.studios.manage', $createMiddleware);

        $this->get(route('owner.studio.get-barangays', ['municipality' => 'New Town']))
            ->assertRedirect(route('login'));
    }

    public function test_locations_schema_has_soft_delete_contract_for_location_model(): void
    {
        $this->assertTrue(Schema::hasColumn('tbl_locations', 'deleted_at'));
    }

    private function createOwner(): UserModel
    {
        return UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Barangay',
            'last_name' => 'Owner',
            'email' => 'barangay-owner@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }
}
