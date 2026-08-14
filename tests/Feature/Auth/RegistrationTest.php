<?php

namespace Tests\Feature\Auth;

use App\Mail\VerificationEmail;
use App\Models\Admin\LocationModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'Imus',
            'barangay' => ['Bucandala III'],
            'zip_code' => '4103',
            'status' => 'active',
        ]);
    }

    public function test_owner_registration_stores_name_suffix_and_organizational_role(): void
    {
        Mail::fake();

        $this->post(route('auth.register.store'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = UserModel::where('email', 'juan.delacruz@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Jr.', $user->suffix);
        $this->assertSame('Business Owner', $user->org_role);
        $this->assertSame('owner', $user->role);

        Mail::assertSent(VerificationEmail::class);
    }

    public function test_registration_accepts_suffix_and_org_role_as_optional(): void
    {
        Mail::fake();

        $this->post(route('auth.register.store'), $this->validPayload([
            'suffix' => '',
            'orgRole' => '',
        ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = UserModel::where('email', 'juan.delacruz@example.com')->first();

        $this->assertNull($user->suffix);
        $this->assertNull($user->org_role);
    }

    public function test_registration_rejects_an_unknown_organizational_role(): void
    {
        Mail::fake();

        $this->postJson(route('auth.register.store'), $this->validPayload([
            'orgRole' => 'Chief Executive Officer',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('orgRole');

        $this->assertDatabaseMissing('tbl_users', ['email' => 'juan.delacruz@example.com']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'userType' => 'owner',
            'firstName' => 'Juan',
            'middleName' => 'Santos',
            'lastName' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'userEmail' => 'juan.delacruz@example.com',
            'userMobile' => '09171234567',
            'userPassword' => 'Password_123',
            'userConfirmPassword' => 'Password_123',
            'municipality' => 'Imus',
            'orgRole' => 'Business Owner',
            'agreeTerms' => 1,
        ], $overrides);
    }

    private function createSchema(): void
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
            $table->string('mobile_number');
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('token_expiry')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('municipality');
            $table->text('barangay')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
