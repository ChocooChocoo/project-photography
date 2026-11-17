<?php

namespace Tests\Feature\Procurement;

use App\Models\Procurement\ProcurementAssetModel;
use App\Models\Procurement\ProcurementInventoryStockModel;
use App\Models\Procurement\ProcurementRequestItemModel;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Inventory page lists stock and assets received through procurement,
 * scoped to the viewer's studios, for owners and finance staff.
 */
class ProcurementInventoryPageTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;

    private StudiosModel $studio;

    private StudiosModel $otherStudio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->user('owner', 'inv-owner@example.com');
        $otherOwner = $this->user('owner', 'inv-other@example.com');
        $this->studio = $this->studio($this->owner, 'Mine Studio');
        $this->otherStudio = $this->studio($otherOwner, 'Other Studio');

        $this->receive($this->studio, 'Backdrop paper', 2, 5, 'Canon EOS R6', 'SN-MINE-1');
        $this->receive($this->otherStudio, 'Foreign gaffer tape', 50, 5, 'Foreign Lens', 'SN-OTHER-1');
    }

    public function test_owner_sees_only_their_studios_inventory_with_low_stock_badge(): void
    {
        $this->grant($this->owner, 'owner', 'owner.procurement.view', $this->studio);

        $this->actingAs($this->owner)
            ->get(route('owner.procurement.inventory'))
            ->assertOk()
            ->assertSee('Inventory')
            ->assertSee('Backdrop paper')
            ->assertSee('Low stock')
            ->assertSee('Canon EOS R6')
            ->assertSee('SN-MINE-1')
            ->assertDontSee('Foreign gaffer tape')
            ->assertDontSee('SN-OTHER-1');
    }

    public function test_finance_user_sees_only_assigned_studio_inventory(): void
    {
        $finance = $this->user('studio-finance', 'inv-finance@example.com');
        $this->grant($finance, 'studio-finance', 'studio-finance.procurement.view', $this->studio);

        $this->actingAs($finance)
            ->get(route('studio-finance.procurement.inventory'))
            ->assertOk()
            ->assertSee('Backdrop paper')
            ->assertSee('Low stock')
            ->assertSee('SN-MINE-1')
            ->assertDontSee('Foreign gaffer tape')
            ->assertDontSee('SN-OTHER-1');
    }

    public function test_empty_inventory_explains_where_items_come_from(): void
    {
        ProcurementInventoryStockModel::query()->delete();
        ProcurementAssetModel::query()->delete();
        $this->grant($this->owner, 'owner', 'owner.procurement.view', $this->studio);

        $this->actingAs($this->owner)
            ->get(route('owner.procurement.inventory'))
            ->assertOk()
            ->assertSee('Items appear here after a procurement delivery is confirmed as received.');
    }

    public function test_user_without_procurement_permission_is_turned_away(): void
    {
        $this->grant($this->owner, 'owner', 'owner.packages.manage', $this->studio);

        $this->actingAs($this->owner)
            ->getJson(route('owner.procurement.inventory'))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('owner.procurement.inventory'))
            ->assertRedirect(route('owner.dashboard'));
    }

    public function test_inventory_url_is_not_captured_by_the_request_detail_route(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/owner/procurement-inventory', 'GET')
        );
        $this->assertSame('owner.procurement.inventory', $route->getName());

        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/studio-finance/procurement-inventory', 'GET')
        );
        $this->assertSame('studio-finance.procurement.inventory', $route->getName());
    }

    private function user(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role, 'user_type' => 'photographer', 'first_name' => 'Inv', 'last_name' => ucfirst($role),
            'email' => $email, 'mobile_number' => '0917'.random_int(1000000, 9999999), 'password' => 'secret',
            'status' => 'active', 'email_verified' => true, 'onboarding_completed_at' => now(),
        ]);
    }

    private function studio(UserModel $owner, string $name): StudiosModel
    {
        $studio = StudiosModel::create([
            'user_id' => $owner->id, 'studio_name' => $name, 'year_established' => 2020,
            'studio_description' => 'Inventory test studio.', 'starting_price' => 1500, 'status' => 'verified',
        ]);

        $plan = SubscriptionPlanModel::firstOrCreate(['plan_code' => 'STU_INV_TEST'], [
            'user_type' => 'studio', 'plan_type' => 'basic', 'billing_cycle' => 'monthly', 'name' => 'Basic',
            'price' => 590, 'commission_rate' => 5, 'trial_days' => 0, 'priority_level' => 1, 'status' => 'active',
        ]);
        StudioPlanModel::create([
            'studio_id' => $studio->id, 'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => now()->subDays(10)->toDateString(), 'end_date' => now()->addDays(20)->toDateString(),
            'next_billing_date' => now()->addDays(20)->toDateString(), 'amount_paid' => 590,
            'payment_status' => 'paid', 'status' => 'active',
        ]);

        return $studio;
    }

    private function receive(StudiosModel $studio, string $itemName, float $quantity, float $threshold, string $assetName, string $serial): void
    {
        $request = ProcurementRequestModel::create([
            'request_reference' => 'PR-'.str()->upper(str()->random(8)),
            'studio_id' => $studio->id,
            'requester_id' => $studio->user_id,
            'requester_role' => 'owner',
            'status' => 'received',
        ]);
        $item = ProcurementRequestItemModel::create([
            'procurement_request_id' => $request->id, 'item_name' => $assetName,
            'normalized_item_name' => strtolower($assetName), 'category' => 'equipment', 'expense_type' => 'capex',
            'quantity' => 1, 'unit_of_measure' => 'unit',
        ]);

        ProcurementInventoryStockModel::create([
            'studio_id' => $studio->id, 'procurement_request_id' => $request->id,
            'item_name' => $itemName, 'normalized_item_name' => strtolower($itemName),
            'unit_of_measure' => 'roll', 'stock_quantity' => $quantity, 'reorder_threshold' => $threshold,
            'last_recorded_cost' => 450, 'last_received_at' => now(),
        ]);
        ProcurementAssetModel::create([
            'studio_id' => $studio->id, 'procurement_request_id' => $request->id,
            'procurement_request_item_id' => $item->id, 'asset_name' => $assetName,
            'serial_number' => $serial, 'acquisition_cost' => 98000, 'location' => 'Main room', 'status' => 'active',
        ]);
    }

    private function grant(UserModel $user, string $portal, string $permissionName, StudiosModel $studio): void
    {
        $role = RoleModel::create(['name' => $portal.'-inventory-'.str()->random(4), 'portal' => $portal, 'status' => 'active']);
        $permission = PermissionModel::create([
            'name' => $permissionName, 'permission_string' => $permissionName, 'portal' => $portal, 'status' => 'active',
        ]);
        $role->permissions()->attach($permission->id);
        $user->assignRole($role, $studio->id);
    }
}
