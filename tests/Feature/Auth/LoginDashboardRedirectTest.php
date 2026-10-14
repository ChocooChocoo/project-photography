<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A user who logs in must land on the dashboard of their role and stay signed in,
 * not bounce back to the login page.
 */
class LoginDashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public static function roleProvider(): array
    {
        return array_map(fn (string $role) => [$role], [
            'owner', 'client', 'freelancer', 'studio-hr', 'studio-finance', 'studio-photographer',
        ]);
    }

    #[DataProvider('roleProvider')]
    public function test_login_redirect_reaches_the_role_dashboard_and_keeps_the_session(string $role): void
    {
        $user = UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Redirect',
            'last_name' => ucfirst($role),
            'email' => str_replace('-', '_', $role).'@example.com',
            'mobile_number' => '09170000000',
            'password' => Hash::make('Password_123'),
            'status' => 'active',
            'email_verified' => true,
            'onboarding_completed_at' => now(),
        ]);

        $login = $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'Password_123',
        ])->assertOk()->assertJsonPath('success', true);

        $dashboard = $login->json('redirect');
        $this->assertSame(route($role.'.dashboard'), $dashboard);

        // The session survives: a signed-in user who opens the login page is sent
        // to the same dashboard, not shown the form again. (Dashboards themselves
        // are not rendered here; their data setup is covered by the portal tests.)
        $this->assertAuthenticatedAs($user);
        $this->get(route('login'))->assertRedirect($dashboard);
    }
}
