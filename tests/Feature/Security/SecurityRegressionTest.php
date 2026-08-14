<?php

namespace Tests\Feature\Security;

use App\Models\Admin\LocationModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_login_endpoint_is_rate_limited_after_rapid_attempts(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson('/auth/login', [
                'email' => 'not-an-email',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/auth/login', [
            'email' => 'not-an-email',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_register_endpoint_is_rate_limited_after_rapid_attempts(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson('/auth/register', [
                'userType' => 'client',
                'firstName' => 'Rate',
                'lastName' => 'Limit',
                'userEmail' => 'rate-limit@example.com',
                'userMobile' => '09170000001',
                'userPassword' => 'secret123',
                'municipality' => 'Dasmariñas',
            ])->assertStatus(422);
        }

        $this->postJson('/auth/register', [
            'userType' => 'client',
            'firstName' => 'Rate',
            'lastName' => 'Limit',
            'userEmail' => 'rate-limit@example.com',
            'userMobile' => '09170000001',
            'userPassword' => 'secret123',
            'municipality' => 'Dasmariñas',
        ])->assertStatus(429);
    }

    public function test_csrf_middleware_protects_the_web_group(): void
    {
        $this->get('/auth/login')->assertOk();

        $groups = app(\Illuminate\Routing\Router::class)->getMiddlewareGroups();

        $this->assertContains(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            $groups['web'],
            'CSRF validation must guard the web middleware group'
        );
    }

    public function test_payment_webhook_routes_are_exempt_from_csrf(): void
    {
        $this->post('/webhook/paymongo', [])
            ->assertStatus(400);
    }

    public function test_gallery_upload_rejects_non_image_files(): void
    {
        $freelancer = $this->createUser('freelancer');

        $this->actingAs($freelancer)
            ->postJson('/freelancer/online-gallery/999/upload', [
                'images' => [UploadedFile::fake()->create('notes.txt', 100, 'text/plain')],
            ])
            ->assertStatus(500)
            ->assertJson(['success' => false]);
    }

    public function test_gallery_upload_rejects_oversized_files(): void
    {
        $freelancer = $this->createUser('freelancer');

        $this->actingAs($freelancer)
            ->postJson('/freelancer/online-gallery/999/upload', [
                'images' => [UploadedFile::fake()->create('huge.png', 6000, 'image/png')],
            ])
            ->assertStatus(500)
            ->assertJson(['success' => false]);
    }

    public function test_client_cannot_access_owner_only_routes(): void
    {
        $client = $this->createUser('client');

        $this->actingAs($client)->getJson('/owner/dashboard')->assertStatus(403);
    }

    public function test_client_cannot_access_freelancer_only_routes(): void
    {
        $client = $this->createUser('client');

        $this->actingAs($client)->getJson('/freelancer/dashboard')->assertStatus(403);
    }

    public function test_owner_cannot_access_admin_only_routes(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->getJson('/admin/dashboard')->assertStatus(403);
    }

    public function test_wrong_role_cannot_submit_role_specific_post_endpoints(): void
    {
        $client = $this->createUser('client');

        $this->actingAs($client)
            ->postJson('/owner/online-gallery/1/upload', ['images' => []])
            ->assertStatus(403);
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Security',
            'last_name' => 'Test',
            'email' => $role.'-'.uniqid().'@example.com',
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
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('municipality');
            $table->json('barangay')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'Dasmariñas',
            'barangay' => ['Zone 1'],
            'status' => 'active',
        ]);
    }
}
