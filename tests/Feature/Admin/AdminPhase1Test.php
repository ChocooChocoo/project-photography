<?php

namespace Tests\Feature\Admin;

use App\Models\Admin\LocationModel;
use App\Models\Admin\UserModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel as AuthUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin panel + freelancer defects: View Users, plan Update, freelancer Update/Delete,
 * and the freelancer barangay lookup.
 */
class AdminPhase1Test extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $status = 'active', ?string $email = null): AuthUser
    {
        static $n = 0;
        $n++;

        return AuthUser::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'First'.$n,
            'last_name' => 'Last'.$n,
            'email' => $email ?? "{$role}{$n}@example.com",
            'mobile_number' => '0917000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'password' => 'secret',
            'status' => $status,
            'email_verified' => true,
        ]);
    }

    private function makePlan(string $code, array $overrides = []): SubscriptionPlanModel
    {
        return SubscriptionPlanModel::create($overrides + [
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'plan_code' => $code,
            'name' => 'Plan '.$code,
            'price' => 100,
            'commission_rate' => 5,
            'features' => ['One'],
            'support_level' => 'basic',
            'status' => 'active',
        ]);
    }

    public function test_view_users_loads_for_several_roles_and_statuses(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeUser('owner');
        $this->makeUser('client', 'inactive');
        $this->makeUser('freelancer', 'suspended');
        $this->makeUser('studio-hr');

        $this->actingAs($admin)->get(route('admin.user.index'))
            ->assertOk()->assertSee('Studio Owner')->assertSee('SUSPENDED');

        $this->actingAs($admin)->getJson(route('admin.user.data'))
            ->assertOk()->assertJsonCount(4, 'users');
    }

    public function test_plan_edit_page_renders_and_update_saves(): void
    {
        $admin = $this->makeUser('admin');
        $plan = $this->makePlan('P-1');

        $this->actingAs($admin)->get(route('admin.subscription.edit', $plan->id))
            ->assertOk()->assertSee('Edit Subscription Plan')->assertSee('P-1');

        $payload = [
            'user_type' => 'studio', 'plan_type' => 'premium', 'billing_cycle' => 'yearly',
            'plan_code' => 'P-1', 'name' => 'Renamed', 'price' => 250, 'commission_rate' => 4,
            'features' => ['A', 'B'], 'support_level' => 'priority', 'status' => 'active',
        ];

        $this->actingAs($admin)->putJson(route('admin.subscription.update', $plan->id), $payload)
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame('Renamed', $plan->fresh()->name);

        $other = $this->makePlan('P-2');
        $this->actingAs($admin)->putJson(route('admin.subscription.update', $other->id), $payload)
            ->assertStatus(422)->assertJsonValidationErrors('plan_code');
    }

    public function test_admin_can_update_and_delete_a_freelancer_only(): void
    {
        $admin = $this->makeUser('admin');
        $freelancer = $this->makeUser('freelancer');
        $client = $this->makeUser('client');

        $this->actingAs($admin)->putJson(route('admin.freelancer.update', $freelancer->id), [
            'first_name' => 'Newfirst', 'last_name' => 'Newlast', 'mobile_number' => '09171112222', 'status' => 'inactive',
        ])->assertOk();

        $this->assertSame('Newfirst', UserModel::find($freelancer->id)->first_name);
        $this->assertSame('inactive', UserModel::find($freelancer->id)->status);

        $this->actingAs($admin)->deleteJson(route('admin.freelancer.delete', $client->id))->assertNotFound();
        $this->actingAs($admin)->deleteJson(route('admin.freelancer.delete', $freelancer->id))->assertOk();
        $this->assertSoftDeleted('tbl_users', ['id' => $freelancer->id]);
    }

    public function test_freelancer_barangay_lookup_reads_array_cast_and_ignores_inactive(): void
    {
        $freelancer = $this->makeUser('freelancer');
        LocationModel::create([
            'province' => 'Cavite', 'municipality' => 'New Town',
            'barangay' => ['Zulu', 'Alpha'], 'zip_code' => '4100', 'status' => 'active',
        ]);
        LocationModel::create([
            'province' => 'Cavite', 'municipality' => 'Hidden Town',
            'barangay' => ['Secret'], 'zip_code' => '4101', 'status' => 'inactive',
        ]);

        $this->actingAs($freelancer)
            ->postJson(route('freelancer.profile.get-barangays'), ['municipality' => 'New Town'])
            ->assertOk()->assertExactJson(['barangays' => ['Alpha', 'Zulu'], 'zip_code' => '4100']);

        $this->actingAs($freelancer)
            ->postJson(route('freelancer.profile.get-barangays'), ['municipality' => 'Hidden Town'])
            ->assertOk()->assertExactJson(['barangays' => [], 'zip_code' => null]);
    }
}
