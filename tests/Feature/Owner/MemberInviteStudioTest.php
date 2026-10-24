<?php

namespace Tests\Feature\Owner;

use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Thesis panel (RC, T02 Phase 10): an owner must be able to invite a freelancer
 * to any studio they own. The member pages used to work on the first studio only.
 */
class MemberInviteStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_invites_a_freelancer_to_their_second_studio_but_not_to_another_owners(): void
    {
        $owner = $this->user('owner', 'invite-owner@example.com', '09170000031');
        $first = $this->studio($owner, 'First Studio');
        $second = $this->studio($owner, 'Second Studio');
        $foreign = $this->studio($this->user('owner', 'other-owner@example.com', '09170000032'), 'Other Studio');
        $freelancer = $this->user('freelancer', 'invite-freelancer@example.com', '09170000033');

        $role = RoleModel::create(['name' => 'owner-members', 'portal' => 'owner', 'status' => 'active']);
        $role->permissions()->attach(PermissionModel::create([
            'name' => 'owner.members.manage', 'permission_string' => 'owner.members.manage', 'portal' => 'owner', 'status' => 'active',
        ])->id);
        foreach ([$first, $second, $foreign] as $studio) {
            $owner->assignRole($role, $studio->id);
            $this->subscribe($studio);
        }

        $this->actingAs($owner)
            ->get(route('owner.members.invite', ['studio_id' => $second->id]))
            ->assertOk()
            ->assertSee('name="studio_id" value="'.$second->id.'"', false)
            ->assertSee('Second Studio');

        $this->postJson(route('owner.members.invite.store'), [
            'studio_id' => $second->id, 'freelancer_id' => $freelancer->id, 'invitation_message' => 'Please join our second studio.',
        ])->assertOk();
        $this->assertTrue(StudioMemberModel::where('studio_id', $second->id)->where('freelancer_id', $freelancer->id)->exists());

        $this->postJson(route('owner.members.invite.store'), [
            'studio_id' => $foreign->id, 'freelancer_id' => $freelancer->id, 'invitation_message' => 'Please join a studio I do not own.',
        ])->assertStatus(422)->assertJsonValidationErrors('studio_id');
    }

    private function user(string $role, string $email, string $mobile): UserModel
    {
        return UserModel::create([
            'role' => $role, 'user_type' => 'photographer', 'first_name' => 'Test', 'last_name' => ucfirst($role),
            'email' => $email, 'mobile_number' => $mobile, 'password' => 'secret',
            'status' => 'active', 'email_verified' => true, 'onboarding_completed_at' => now(),
        ]);
    }

    private function studio(UserModel $owner, string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $owner->id, 'studio_name' => $name, 'year_established' => 2020,
            'studio_description' => 'Member invite test studio.', 'starting_price' => 1500, 'status' => 'verified',
        ]);
    }

    private function subscribe(StudiosModel $studio): void
    {
        $plan = SubscriptionPlanModel::firstOrCreate(['plan_code' => 'STU_MEMBER_TEST'], [
            'user_type' => 'studio', 'plan_type' => 'basic', 'billing_cycle' => 'monthly', 'name' => 'Basic',
            'price' => 590, 'commission_rate' => 5, 'trial_days' => 0, 'priority_level' => 1, 'status' => 'active',
        ]);
        StudioPlanModel::create([
            'studio_id' => $studio->id, 'plan_id' => $plan->id, 'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => now()->subDays(10)->toDateString(), 'end_date' => now()->addDays(20)->toDateString(),
            'next_billing_date' => now()->addDays(20)->toDateString(), 'amount_paid' => 590, 'payment_status' => 'paid', 'status' => 'active',
        ]);
    }
}
