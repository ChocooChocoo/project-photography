<?php

namespace Tests\Feature\Owner;

use App\Models\StudioOwner\RoleModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression coverage for the owner role update endpoint. The edit modal may
 * send the role details, the permissions, or a partial payload. None of those
 * may be rejected just because name and status are absent, but a real duplicate
 * name must still fail.
 */
class RolePermissionUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        $this->withoutMiddleware([
            \App\Http\Middleware\OwnerMiddleware::class,
            \App\Http\Middleware\EnforceStudioSubscriptionAccess::class,
            \App\Http\Middleware\CheckStudioRegistrationLimit::class,
            \App\Http\Middleware\CheckPermissionMiddleware::class,
            \App\Http\Middleware\PermitVerificationMiddleware::class,
        ]);
    }

    public function test_permissions_only_update_succeeds(): void
    {
        $owner = $this->createOwner();
        $role = $this->createRole('studio-hr-manager', 'studio-hr');

        $response = $this->actingAs($owner)->putJson(route('owner.role.update', $role->id), [
            'permissions' => [1, 2, 3],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // A permission-only payload must not blank out the role details.
        $role->refresh();
        $this->assertSame('studio-hr-manager', $role->name);
        $this->assertSame('active', $role->status);
    }

    public function test_name_only_update_succeeds_and_keeps_status(): void
    {
        $owner = $this->createOwner();
        $role = $this->createRole('studio-hr-manager', 'studio-hr');

        $response = $this->actingAs($owner)->putJson(route('owner.role.update', $role->id), [
            'name' => 'studio-hr-lead',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $role->refresh();
        $this->assertSame('studio-hr-lead', $role->name);
        $this->assertSame('active', $role->status);
    }

    public function test_status_only_update_succeeds_and_keeps_name(): void
    {
        $owner = $this->createOwner();
        $role = $this->createRole('studio-hr-manager', 'studio-hr');

        $response = $this->actingAs($owner)->putJson(route('owner.role.update', $role->id), [
            'status' => 'inactive',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $role->refresh();
        $this->assertSame('studio-hr-manager', $role->name);
        $this->assertSame('inactive', $role->status);
    }

    public function test_duplicate_name_is_still_rejected(): void
    {
        $owner = $this->createOwner();
        $this->createRole('studio-hr-manager', 'studio-hr');
        $role = $this->createRole('studio-hr-staff', 'studio-hr');

        $response = $this->actingAs($owner)->putJson(route('owner.role.update', $role->id), [
            'name' => 'studio-hr-manager',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $role->refresh();
        $this->assertSame('studio-hr-staff', $role->name);
    }

    public function test_description_can_be_cleared(): void
    {
        $owner = $this->createOwner();
        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $role->update(['description' => 'Old description']);

        $response = $this->actingAs($owner)->putJson(route('owner.role.update', $role->id), [
            'description' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($role->fresh()->description);
    }

    private function createOwner(): UserModel
    {
        return UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Role',
            'last_name' => 'Owner',
            'email' => 'role-owner@example.com',
            'mobile_number' => '09170001001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createRole(string $name, string $portal): RoleModel
    {
        return RoleModel::create([
            'name' => $name,
            'portal' => $portal,
            'status' => 'active',
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
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['name', 'deleted_at']);
        });
    }
}
