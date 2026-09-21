<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the login defects: a real form action, CSRF header, a visible error
 * area, keeping the typed email, and a first POST attempt that succeeds.
 */
class FirstAttemptLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createUsersTable();
    }

    public function test_login_page_posts_to_the_login_route_with_csrf_and_error_ui(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('action="' . route('auth.login.store') . '"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="csrf-token"', false);
        $response->assertSee('id="loginErrorAlert"', false);
        $response->assertSee('id="loadingOverlay"', false);
        $response->assertSee('X-CSRF-TOKEN', false);
    }

    public function test_login_page_keeps_the_entered_email_after_a_failed_attempt(): void
    {
        $response = $this->withSession(['_old_input' => ['email' => 'keep.me@example.com']])
            ->get(route('login'));

        $response->assertOk();
        $response->assertSee('value="keep.me@example.com"', false);
    }

    public function test_first_login_attempt_with_valid_credentials_succeeds(): void
    {
        $user = $this->createUser('owner');

        $response = $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'Password_123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_login_returns_a_json_message_the_ui_can_show(): void
    {
        $user = $this->createUser('owner');

        $response = $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
        $response->assertJsonStructure(['message']);
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'First',
            'last_name' => 'Attempt',
            'email' => 'first.attempt@example.com',
            'mobile_number' => '09171234567',
            'password' => Hash::make('Password_123'),
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createUsersTable(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('org_role')->nullable();
            $table->string('email')->unique();
            $table->string('mobile_number')->nullable();
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->string('cover_photo')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('status')->default('active');
            $table->boolean('email_verified')->default(true);
            $table->string('verification_token')->nullable();
            $table->timestamp('token_expiry')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
