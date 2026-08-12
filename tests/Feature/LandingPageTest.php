<?php

namespace Tests\Feature;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
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
        });
    }

    public function test_guest_sees_landing_page_at_root(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Platinum Photography')
            ->assertSee('Login');
    }

    public function test_authenticated_owner_is_redirected_to_owner_dashboard(): void
    {
        $owner = UserModel::create([
            'role' => 'owner',
            'user_type' => 'studio',
            'first_name' => 'Test',
            'last_name' => 'Owner',
            'email' => 'landing-owner@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);

        $this->actingAs($owner)
            ->get('/')
            ->assertRedirect(route('owner.dashboard'));
    }

    public function test_authenticated_client_is_redirected_to_client_dashboard(): void
    {
        $client = UserModel::create([
            'role' => 'client',
            'user_type' => 'client',
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'landing-client@example.com',
            'mobile_number' => '09170000002',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);

        $this->actingAs($client)
            ->get('/')
            ->assertRedirect(route('client.dashboard'));
    }
}
