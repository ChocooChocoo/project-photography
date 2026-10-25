<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Client test C-08: after a few wrong passwords the login said only
 * "Too Many Attempts.", so a client with the right password did not know
 * to wait. The message now says how long.
 */
class ThrottleMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_on_one_network_can_all_log_in_and_one_persons_typos_do_not_block_others(): void
    {
        // Client test C-09: throttle:5,1 counted every login from one IP, so the
        // sixth person at a studio logging in within a minute was locked out.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        foreach (range(1, 7) as $n) {
            \App\Models\UserModel::create([
                'role' => 'client', 'user_type' => 'customer', 'first_name' => 'Staff', 'last_name' => (string) $n,
                'email' => "staff{$n}@example.com", 'mobile_number' => '0917000000'.$n, 'password' => \Illuminate\Support\Facades\Hash::make('Password_123'),
                'status' => 'active', 'email_verified' => true,
            ]);
        }

        foreach (range(1, 7) as $n) {
            $this->postJson(route('auth.login.store'), ['email' => "staff{$n}@example.com", 'password' => 'Password_123'])->assertOk();
            auth()->logout();
        }

        foreach (range(1, 5) as $i) {
            $this->postJson(route('auth.login.store'), ['email' => 'staff1@example.com', 'password' => 'typo'])->assertStatus(401);
        }
        $this->postJson(route('auth.login.store'), ['email' => 'staff1@example.com', 'password' => 'Password_123'])->assertStatus(429);
        $this->postJson(route('auth.login.store'), ['email' => 'staff2@example.com', 'password' => 'Password_123'])->assertOk();
    }

    public function test_a_throttled_login_says_how_long_to_wait(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('auth.login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-pass'])->assertStatus(401);
        }

        $response = $this->postJson(route('auth.login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-pass'])
            ->assertStatus(429)
            ->assertJsonPath('success', false);

        $this->assertMatchesRegularExpression('/^Too many attempts\. Please wait \d+ seconds? and try again\.$/', $response->json('message'));
    }
}
