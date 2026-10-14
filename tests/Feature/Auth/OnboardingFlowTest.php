<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_onboarding_page_renders_with_next_and_skip_controls(): void
    {
        $client = $this->createUser('client');

        $response = $this->actingAs($client)->get(route('onboarding'));

        $response->assertOk();
        $html = $response->getOriginalContent()->render();
        $this->assertStringContainsString('Next', $html);
        $this->assertStringContainsString('Skip', $html);
        $this->assertStringContainsString(action([\App\Http\Controllers\OnboardingController::class, 'complete']), $html);
    }

    public function test_onboarding_complete_sets_timestamp_and_redirects_to_dashboard(): void
    {
        $client = $this->createUser('client');

        $this->actingAs($client)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('client.dashboard'));

        $this->assertNotNull($client->fresh()->onboarding_completed_at);
    }

    public function test_onboarding_redirects_to_dashboard_when_already_completed(): void
    {
        $client = $this->createUser('client');
        $client->update(['onboarding_completed_at' => now()]);

        $this->actingAs($client)
            ->get(route('onboarding'))
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_onboarding_redirects_owner_to_owner_dashboard(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('owner.dashboard'));
    }

    public static function tutorialRoles(): array
    {
        return [['client'], ['owner'], ['freelancer']];
    }

    #[DataProvider('tutorialRoles')]
    public function test_first_login_sends_new_accounts_to_tutorial_and_keeps_session(string $role): void
    {
        $user = $this->createUser($role);
        $response = $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertOk()->assertJsonPath('redirect', route('onboarding'));

        $this->assertAuthenticatedAs($user);
        $this->get($response->json('redirect'))->assertOk();
        $this->get(route('login'))->assertRedirect(route('onboarding'));
    }

    #[DataProvider('tutorialRoles')]
    public function test_completed_account_login_skips_tutorial(string $role): void
    {
        $user = $this->createUser($role);
        $user->update(['onboarding_completed_at' => now()]);

        $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertOk()->assertJsonPath('redirect', route($role.'.dashboard'));
    }

    public function test_replay_does_not_reset_or_replace_completion_timestamp(): void
    {
        $client = $this->createUser('client');
        $client->update(['onboarding_completed_at' => now()->subDay()]);
        $completed = $client->fresh()->onboarding_completed_at;

        $this->actingAs($client)->get(route('onboarding', ['replay' => 1]))->assertOk();
        $this->post(route('onboarding.complete'))->assertRedirect(route('client.dashboard'));
        $this->assertEquals($completed, $client->fresh()->onboarding_completed_at);
    }

    public function test_required_password_change_blocks_tutorial_and_completion(): void
    {
        $user = $this->createUser('studio-hr');
        $user->update(['must_change_password' => true]);

        $this->actingAs($user)->get(route('onboarding'))->assertRedirect(route('password.change'));
        $this->post(route('onboarding.complete'))->assertRedirect(route('password.change'));
        $this->assertNull($user->fresh()->onboarding_completed_at);
    }

    public function test_owner_tutorial_is_available_after_required_password_change(): void
    {
        $owner = $this->createUser('owner');
        $owner->update(['must_change_password' => true]);

        $this->actingAs($owner)->post(route('password.change.store'), [
            'current_password' => 'secret',
            'new_password' => 'Changed_123',
            'new_password_confirmation' => 'Changed_123',
        ])->assertRedirect(route('onboarding'));
        $this->get(route('onboarding'))->assertOk();
    }

    public static function excludedRoles(): array
    {
        return [['admin'], ['studio-hr'], ['studio-finance'], ['studio-photographer']];
    }

    #[DataProvider('excludedRoles')]
    public function test_staff_and_admin_are_not_sent_to_business_tutorial(string $role): void
    {
        $user = $this->createUser($role);

        $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertOk()->assertJsonPath('redirect', route($role.'.dashboard'));
        $this->get(route('onboarding', ['replay' => 1]))->assertRedirect(route($role.'.dashboard'));
    }

    public static function roleContent(): array
    {
        return [
            ['client', ['Set your budget', 'Book your session', 'Find your photos', 'Message your booking team']],
            ['owner', ['Add your services', 'Build your packages', 'Add your staff', 'Set staff permissions', 'Manage your bookings']],
            ['freelancer', ['Add your services', 'Build your packages', 'Manage your bookings', 'Deliver your photos']],
        ];
    }

    #[DataProvider('roleContent')]
    public function test_tutorial_content_matches_account_role(string $role, array $headings): void
    {
        $response = $this->actingAs($this->createUser($role))->get(route('onboarding'))->assertOk();
        foreach ($headings as $heading) {
            $response->assertSee($heading);
        }
        $response->assertDontSee('Welcome to Lumora');
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Onboard',
            'last_name' => 'Test',
            'email' => $role.'-onboarding@example.com',
            'mobile_number' => '09170000001',
            'password' => Hash::make('secret'),
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->string('suffix')->nullable();
            $table->string('org_role')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }
}
