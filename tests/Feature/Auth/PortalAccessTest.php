<?php

namespace Tests\Feature\Auth;

use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    private const ROLES = [
        'admin',
        'owner',
        'client',
        'freelancer',
        'studio-hr',
        'studio-finance',
        'studio-photographer',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        Route::middleware('admin')->get('/_test/portal/admin', fn () => response()->json(['ok' => true]));
        Route::middleware('owner')->get('/_test/portal/owner', fn () => response()->json(['ok' => true]));
        Route::middleware('client')->get('/_test/portal/client', fn () => response()->json(['ok' => true]));
        Route::middleware('freelancer')->get('/_test/portal/freelancer', fn () => response()->json(['ok' => true]));
        Route::middleware('studio.photographer')->get('/_test/portal/studio-photographer', fn () => response()->json(['ok' => true]));
        Route::middleware('studio.hr')->get('/_test/portal/studio-hr', fn () => response()->json(['ok' => true]));
        Route::middleware('studio.finance')->get('/_test/portal/studio-finance', fn () => response()->json(['ok' => true]));
    }

    public static function portalAccessProvider(): array
    {
        $cases = [];

        foreach (self::ROLES as $role) {
            foreach (self::ROLES as $portal) {
                $cases["{$role} -> {$portal}"] = [$role, $portal, $role === $portal ? 200 : 403];
            }
        }

        return $cases;
    }

    #[DataProvider('portalAccessProvider')]
    public function test_role_can_only_access_its_own_portal(string $role, string $portal, int $expectedStatus): void
    {
        $user = $this->createUser($role);

        $this->actingAs($user)
            ->getJson("/_test/portal/{$portal}")
            ->assertStatus($expectedStatus);
    }

    public function test_guest_json_request_is_unauthorized(): void
    {
        $this->getJson('/_test/portal/admin')->assertStatus(401);
    }

    public function test_guest_browser_request_is_redirected_to_login(): void
    {
        $this->get('/_test/portal/admin')->assertRedirect(route('login'));
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
            'password' => 'secret',
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
        });
    }
}
