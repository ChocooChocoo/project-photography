<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminOtpMail;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_dedicated_admin_login_page_renders_for_guests(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Admin Login')
            ->assertSee(route('login'));
    }

    public function test_admin_login_rejects_wrong_password(): void
    {
        $admin = $this->createUser('admin');

        $this->post(route('admin.login.authenticate'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_login_rejects_non_admin_account(): void
    {
        $owner = $this->createUser('owner');

        $this->post(route('admin.login.authenticate'), [
            'email' => $owner->email,
            'password' => 'secret',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_valid_admin_credentials_send_otp_mail_and_redirect_to_otp_page(): void
    {
        Mail::fake();
        $admin = $this->createUser('admin');

        $this->post(route('admin.login.authenticate'), [
            'email' => $admin->email,
            'password' => 'secret',
        ])->assertRedirect(route('admin.otp'));

        $this->assertGuest();
        $this->assertNotNull(session('admin_otp'));

        Mail::assertSent(AdminOtpMail::class, function (AdminOtpMail $mail) use ($admin) {
            return $mail->hasTo($admin->email)
                && $mail->code === session('admin_otp.code')
                && is_numeric($mail->code)
                && strlen((string) $mail->code) === 6;
        });
    }

    public function test_otp_page_without_pending_session_redirects_to_admin_login(): void
    {
        $this->get(route('admin.otp'))->assertRedirect(route('admin.login'));
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $admin = $this->createUser('admin');

        $this->post(route('admin.login.authenticate'), [
            'email' => $admin->email,
            'password' => 'secret',
        ]);

        $this->post(route('admin.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_correct_otp_logs_in_and_redirects_to_admin_dashboard(): void
    {
        $admin = $this->createUser('admin');

        $this->post(route('admin.login.authenticate'), [
            'email' => $admin->email,
            'password' => 'secret',
        ]);

        $code = session('admin_otp.code');

        $this->post(route('admin.otp.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('admin_otp'));
    }

    public function test_expired_otp_is_rejected(): void
    {
        $admin = $this->createUser('admin');

        $this->post(route('admin.login.authenticate'), [
            'email' => $admin->email,
            'password' => 'secret',
        ]);

        session(['admin_otp.expires_at' => now()->subMinute()]);

        $this->post(route('admin.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_five_wrong_attempts_invalidate_the_code(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $admin = $this->createUser('admin');

        $this->post(route('admin.login.authenticate'), [
            'email' => $admin->email,
            'password' => 'secret',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.otp.verify'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->assertNull(session('admin_otp'));
        $this->assertGuest();
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Portal',
            'last_name' => 'Test',
            'email' => str_replace('-', '_', $role).'@example.com',
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
            $table->boolean('email_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
