<?php

namespace Tests\Feature\Owner;

use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Client report (T02): "Create Package isn't working for the new studio."
 * These tests go through the real owner routes and their middleware.
 */
class PackageCreateAccessTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;

    private CategoriesModel $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = UserModel::create([
            'role' => 'owner', 'user_type' => 'photographer', 'first_name' => 'Pkg', 'last_name' => 'Owner',
            'email' => 'pkg-access-owner@example.com', 'mobile_number' => '09170000019', 'password' => 'secret',
            'status' => 'active', 'email_verified' => true, 'onboarding_completed_at' => now(),
        ]);
        $this->category = CategoriesModel::create(['category_name' => 'Portraits', 'status' => 'active']);
    }

    public function test_owner_with_two_studios_opens_create_page_and_sees_why_a_studio_is_unavailable(): void
    {
        $old = $this->createStudio('Old Studio');
        $this->subscribe($old);
        $new = $this->createStudio('New Studio');
        $pending = $this->createStudio('Pending Studio', 'pending');
        $this->grantPackages([$old, $new, $pending]);

        $html = $this->actingAs($this->owner)
            ->get(route('owner.packages.create'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.$old->id.'"\s*>\s*Old Studio\s*<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option value="'.$new->id.'" disabled>\s*New Studio \(needs an active subscription\)/', $html);
        $this->assertMatchesRegularExpression('/<option value="'.$pending->id.'" disabled>\s*Pending Studio \(awaiting verification\)/', $html);
    }

    public function test_saving_for_an_unsubscribed_studio_returns_the_subscription_message(): void
    {
        $old = $this->createStudio('Old Studio');
        $this->subscribe($old);
        $new = $this->createStudio('New Studio');
        $this->grantPackages([$old, $new]);

        $this->actingAs($this->owner)
            ->postJson(route('owner.packages.store'), $this->payload($new))
            ->assertForbidden()
            ->assertJsonPath('message', 'An active subscription or grace period is required for this studio.')
            ->assertJsonPath('redirect', route('owner.subscription.index'));

        $this->assertSame(0, PackagesModel::count());
    }

    public function test_subscribed_new_studio_with_no_photographers_can_save_a_package(): void
    {
        $old = $this->createStudio('Old Studio');
        $this->subscribe($old);
        $new = $this->createStudio('New Studio');
        $this->subscribe($new);
        $this->grantPackages([$old, $new]);

        $this->actingAs($this->owner)
            ->postJson(route('owner.packages.store'), $this->payload($new, ['photographer_count' => 0]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($new->id, (int) PackagesModel::firstOrFail()->studio_id);
    }

    public function test_create_page_stays_closed_when_no_owned_studio_has_a_subscription(): void
    {
        $first = $this->createStudio('First Studio');
        $second = $this->createStudio('Second Studio');
        $this->grantPackages([$first, $second]);

        $this->actingAs($this->owner)
            ->get(route('owner.packages.create'))
            ->assertRedirect(route('owner.subscription.index'));
    }

    public function test_saving_for_a_pending_studio_explains_why(): void
    {
        // An owner with no verified studio at all stops earlier, at the permit
        // notice, so this owner also has a verified studio.
        $this->subscribe($verified = $this->createStudio('Verified Studio'));
        $pending = $this->createStudio('Pending Studio', 'pending');
        $this->subscribe($pending);
        $this->grantPackages([$verified, $pending]);

        $this->actingAs($this->owner)
            ->postJson(route('owner.packages.store'), $this->payload($pending))
            ->assertStatus(422)
            ->assertJsonPath('message', 'This studio is awaiting verification and cannot have packages yet.');
    }

    public function test_create_script_declares_the_submit_button_text_before_using_it(): void
    {
        $blade = file_get_contents(resource_path('views/owner/create-packages.blade.php'));
        $declared = strpos($blade, 'const originalText');

        $this->assertNotFalse($declared);
        $this->assertSame(1, substr_count($blade, 'const originalText'));
        // The first use comes after the one declaration.
        $this->assertGreaterThan($declared, strpos($blade, '.html(originalText)'));

        // Every non-validation error shows the server message.
        $this->assertStringContainsString("text: body.message || 'Failed to create package. Please try again.'", $blade);
    }

    private function createStudio(string $name, string $status = 'verified'): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => $name,
            'year_established' => 2020,
            'studio_description' => 'Package access test studio.',
            'starting_price' => 1500,
            'status' => $status,
        ]);
    }

    private function subscribe(StudiosModel $studio): StudioPlanModel
    {
        $plan = SubscriptionPlanModel::firstOrCreate(['plan_code' => 'STU_PKG_TEST'], [
            'user_type' => 'studio', 'plan_type' => 'basic', 'billing_cycle' => 'monthly', 'name' => 'Basic',
            'price' => 590, 'commission_rate' => 5, 'trial_days' => 0, 'priority_level' => 1, 'status' => 'active',
        ]);

        return StudioPlanModel::create([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'next_billing_date' => now()->addDays(20)->toDateString(),
            'amount_paid' => 590,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);
    }

    private function grantPackages(array $studios): void
    {
        $role = RoleModel::create(['name' => 'owner-packages', 'portal' => 'owner', 'status' => 'active']);
        $permission = PermissionModel::create([
            'name' => 'owner.packages.manage',
            'permission_string' => 'owner.packages.manage',
            'portal' => 'owner',
            'status' => 'active',
        ]);
        $role->permissions()->attach($permission->id);

        foreach ($studios as $studio) {
            $this->owner->assignRole($role, $studio->id);
        }
    }

    private function payload(StudiosModel $studio, array $extra = []): array
    {
        return array_merge([
            'studio_id' => $studio->id,
            'category_id' => $this->category->id,
            'package_name' => 'Gold',
            'package_description' => 'A gold package for portraits',
            'package_inclusions' => json_encode(['Edited photos']),
            'package_location' => ['In-Studio'],
            'allow_time_customization' => 0,
            'duration' => 2,
            'maximum_edited_photos' => 10,
            'package_price' => 5000,
            'online_gallery' => 1,
            'photographer_count' => 1,
            'status' => 'active',
        ], $extra);
    }
}
