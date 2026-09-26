<?php

namespace Tests\Feature\Auth;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the login email feedback: the message stays hidden until a failed
 * submit, and a valid address never raises the invalid-email message.
 */
class LoginEmailFeedbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createUsersTable();
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('id="loginForm"', false);
        $response->assertSee('id="email"', false);
        $response->assertSee('Please enter a valid email address.', false);
    }

    public function test_email_feedback_is_hidden_until_submit(): void
    {
        $html = $this->get(route('login'))->getContent();
        $emailTag = $this->emailInputTag($html);

        $this->assertNotSame('', $emailTag, 'The login form must render the email input.');
        $this->assertStringNotContainsString('is-invalid', $emailTag, 'The email field must render without an error state.');
        $this->assertStringNotContainsString('style="display: block"', $emailTag);

        // The keystroke handler clears the error. It never reveals it.
        $this->assertStringContainsString('$(this).siblings(\'.invalid-feedback\').hide();', $html);
        $this->assertStringNotContainsString('$(this).siblings(\'.invalid-feedback\').show();', $html);
    }

    public function test_valid_email_uses_the_shared_email_rule(): void
    {
        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('window.PlatinumEmail.isValid', $html);
        $this->assertStringContainsString('assets/js/pages/email-format.js', $html);
    }

    public function test_server_rejects_an_invalid_email_and_accepts_a_valid_one(): void
    {
        $invalid = $this->postJson(route('auth.login.store'), [
            'email' => 'not-an-email',
            'password' => 'Password_123',
        ]);

        $invalid->assertStatus(422);
        $invalid->assertJsonPath('errors.email.0', 'Please enter a valid email address.');

        $valid = $this->postJson(route('auth.login.store'), [
            'email' => 'valid.user@example.com',
            'password' => 'Password_123',
        ]);

        $valid->assertStatus(401);
        $this->assertArrayNotHasKey('email', $valid->json('errors') ?? []);
    }

    private function emailInputTag(string $html): string
    {
        preg_match('/<input[^>]*id="email"[^>]*>/', $html, $matches);

        return $matches[0] ?? '';
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
