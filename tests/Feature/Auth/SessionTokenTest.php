<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the CSRF token contract for one session.
 *
 * The test runner skips the CSRF check, so these tests read the token values
 * directly. They do not expect a 419 response.
 */
class SessionTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createUsersTable();
    }

    public function test_login_page_renders_the_session_token(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();

        $rendered = $this->readPageToken($response->getContent());

        $this->assertNotSame('', $rendered);
        $this->assertSame($rendered, session('_token'));
    }

    public function test_two_requests_in_one_session_render_the_same_token(): void
    {
        $first = $this->readPageToken($this->get(route('login'))->getContent());
        $second = $this->readPageToken($this->get(route('login'))->getContent());

        $this->assertNotSame('', $first);
        $this->assertSame($first, $second);
    }

    public function test_successful_login_keeps_the_same_csrf_token(): void
    {
        $user = $this->createUser('owner');

        $pageToken = $this->readPageToken($this->get(route('login'))->getContent());

        $this->assertNotSame('', $pageToken);

        $response = $this->postJson(route('auth.login.store'), [
            'email' => $user->email,
            'password' => 'Password_123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertAuthenticatedAs($user);

        $this->assertSame($pageToken, session('_token'));
    }

    public function test_authenticated_html_response_carries_no_store_headers(): void
    {
        $user = $this->createUser('owner');

        Route::middleware('web')->get('/_w1/session-html', function () {
            return response('<html><body>Signed in</body></html>');
        });

        $response = $this->actingAs($user)->get('/_w1/session-html');

        $response->assertOk();
        $response->assertHeaderContains('Cache-Control', 'no-store');
        $response->assertHeaderContains('Cache-Control', 'no-cache');
        $response->assertHeaderContains('Cache-Control', 'must-revalidate');
        $response->assertHeader('Pragma', 'no-cache');
    }

    public function test_json_response_keeps_its_own_cache_headers(): void
    {
        $user = $this->createUser('owner');

        Route::middleware('web')->get('/_w1/session-json', function () {
            return response()->json(['signed_in' => true]);
        });

        $response = $this->actingAs($user)->get('/_w1/session-json');

        $response->assertOk();
        $this->assertStringNotContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    /**
     * Read the CSRF token from the meta tag of a rendered page.
     */
    private function readPageToken(string $html): string
    {
        if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Session',
            'last_name' => 'Token',
            'email' => 'session.token@example.com',
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
