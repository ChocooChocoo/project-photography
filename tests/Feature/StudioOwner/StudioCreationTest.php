<?php

namespace Tests\Feature\StudioOwner;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudioCreationTest extends TestCase
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

        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('permission_string');
            $table->string('portal');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('municipality');
            $table->json('barangay');
            $table->string('zip_code');
            $table->string('status');
            $table->timestamps();
        });

        \App\Models\Admin\LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'Bacoor',
            'barangay' => ['Molino 1', 'Molino 2'],
            'zip_code' => '4102',
            'status' => 'active',
        ]);
    }

    public function test_owner_without_studio_management_permission_can_load_barangays_for_studio_creation(): void
    {
        $owner = UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Studio',
            'last_name' => 'Owner',
            'email' => 'studio-owner@example.com',
            'mobile_number' => '09170000000',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);

        $response = $this->actingAs($owner)->getJson(
            route('owner.studio.get-barangays', ['municipality' => 'Bacoor'])
        );

        $response->assertOk()
            ->assertJsonPath('barangays.0', 'Molino 1')
            ->assertJsonPath('barangays.1', 'Molino 2')
            ->assertJsonPath('zip_code', '4102');
    }
}
