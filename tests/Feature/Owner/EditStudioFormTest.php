<?php

namespace Tests\Feature\Owner;

use App\Models\Admin\LocationModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Clicking Update on Edit Studio before the barangay list loaded failed with
 * "The barangay field is required." The page now starts with the saved values.
 */
class EditStudioFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_starts_with_the_saved_barangay_and_zip_code(): void
    {
        $owner = UserModel::create([
            'role' => 'owner', 'user_type' => 'photographer', 'first_name' => 'Edit', 'last_name' => 'Owner',
            'email' => 'edit-studio-owner@example.com', 'mobile_number' => '09170000029', 'password' => 'secret',
            'status' => 'active', 'email_verified' => true, 'onboarding_completed_at' => now(),
        ]);
        $studio = StudiosModel::create([
            'user_id' => $owner->id, 'studio_name' => 'Barangay Studio', 'year_established' => 2020,
            'studio_description' => 'Edit studio form test.', 'starting_price' => 1500, 'status' => 'verified',
            'location_id' => LocationModel::create(['municipality' => 'Rosario', 'barangay' => ['Bucandala III'], 'zip_code' => '4106', 'status' => 'active'])->id,
            'barangay' => 'Bucandala III',
        ]);
        $role = RoleModel::create(['name' => 'owner-studios', 'portal' => 'owner', 'status' => 'active']);
        $permission = PermissionModel::create([
            'name' => 'owner.studios.manage', 'permission_string' => 'owner.studios.manage', 'portal' => 'owner', 'status' => 'active',
        ]);
        $role->permissions()->attach($permission->id);
        $owner->assignRole($role, $studio->id);
        $this->withoutMiddleware(\App\Http\Middleware\EnforceStudioSubscriptionAccess::class);

        $html = $this->actingAs($owner)->get(route('owner.studio.edit', $studio->id))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<select[^>]*id="barangaySelect"[^>]*>\s*<option value="Bucandala III" selected>Bucandala III<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<input type="hidden" id="hiddenZipCode" name="zip_code" value="4106">/', $html);
    }
}
