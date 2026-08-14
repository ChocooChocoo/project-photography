<?php

namespace Tests\Feature\Client;

use App\Models\ClientFavoriteModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientFavoritesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_client_can_favorite_a_verified_studio(): void
    {
        $client = $this->createUser('client');
        $studio = $this->createStudio('Verified Studio');

        $response = $this->actingAs($client)->postJson(route('client.favorites.toggle'), [
            'studio_id' => $studio->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('favorited', true);

        $this->assertDatabaseHas('tbl_client_favorites', [
            'client_id' => $client->id,
            'studio_id' => $studio->id,
        ]);
    }

    public function test_client_can_remove_a_favorite(): void
    {
        $client = $this->createUser('client');
        $studio = $this->createStudio('Verified Studio');

        $this->actingAs($client)->postJson(route('client.favorites.toggle'), [
            'studio_id' => $studio->id,
        ])->assertJsonPath('favorited', true);

        $this->actingAs($client)->postJson(route('client.favorites.toggle'), [
            'studio_id' => $studio->id,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('favorited', false);

        $this->assertDatabaseMissing('tbl_client_favorites', [
            'client_id' => $client->id,
            'studio_id' => $studio->id,
        ]);
    }

    public function test_unverified_studio_cannot_be_favorited(): void
    {
        $client = $this->createUser('client');
        $studio = $this->createStudio('Pending Studio');
        $studio->update(['status' => 'pending']);

        $this->actingAs($client)->postJson(route('client.favorites.toggle'), [
            'studio_id' => $studio->id,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('tbl_client_favorites', 0);
    }

    public function test_non_client_cannot_toggle_favorites(): void
    {
        $owner = $this->createUser('owner');
        $studio = $this->createStudio('Verified Studio');

        $this->actingAs($owner)->postJson(route('client.favorites.toggle'), [
            'studio_id' => $studio->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('tbl_client_favorites', 0);
    }

    public function test_dashboard_lists_favorited_studios_with_package_cover_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('packages/wedding.jpg', 'image-content');

        $client = $this->createUser('client');
        $studio = $this->createStudio('Favorite Studio');
        $this->createSubscription($studio, 3);

        $package = PackagesModel::create([
            'studio_id' => $studio->id,
            'category_id' => 1,
            'package_name' => 'Wedding Package',
            'package_price' => 5000,
            'status' => 'active',
            'cover_images' => ['packages/wedding.jpg'],
        ]);

        $favorite = ClientFavoriteModel::create([
            'client_id' => $client->id,
            'studio_id' => $studio->id,
        ]);

        $response = $this->actingAs($client)->get(route('client.dashboard'));

        $response->assertOk();

        $favoriteStudios = $response->viewData('favoriteStudios');
        $this->assertTrue($favoriteStudios->contains('id', $studio->id));
        $this->assertContains($studio->id, $response->viewData('favoriteStudioIds'));
        $this->assertContains($studio->id, $response->viewData('featuredStudioIds'));

        $html = $response->getOriginalContent()->render();
        $this->assertStringContainsString('My Favorites', $html);
        $this->assertStringContainsString('Favorite Studio', $html);
        $this->assertStringContainsString('storage/packages/wedding.jpg', $html);

        $favorite->delete();
    }

    public function test_dashboard_favorites_section_shows_empty_state_when_none(): void
    {
        $client = $this->createUser('client');
        $studio = $this->createStudio('Verified Studio');
        $this->createSubscription($studio, 3);

        $response = $this->actingAs($client)->get(route('client.dashboard'));

        $response->assertOk();
        $this->assertEmpty($response->viewData('favoriteStudios'));
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Favorite',
            'last_name' => 'Test',
            'email' => $role.'-favorite@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => 1,
            'studio_name' => $name,
            'status' => 'verified',
        ]);
    }

    private function createSubscription(StudiosModel $studio, int $priorityLevel): StudioPlanModel
    {
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'plan_code' => 'PLAN-' . str()->upper(str()->random(8)),
            'name' => 'Plan ' . $priorityLevel,
            'price' => 590,
            'commission_rate' => 5,
            'trial_days' => 0,
            'priority_level' => $priorityLevel,
            'status' => 'active',
        ]);

        return StudioPlanModel::create([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-' . str()->upper(str()->random(10)),
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'next_billing_date' => '2026-09-01',
            'amount_paid' => 590,
            'payment_status' => 'paid',
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
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->string('studio_logo')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('starting_price', 10, 2)->default(0);
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_freelancers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('brand_name')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('starting_price', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('municipality');
            $table->softDeletes();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->softDeletes();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->string('package_name');
            $table->decimal('package_price', 10, 2);
            $table->string('status');
            $table->json('cover_images')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('pvt_freelancer_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('category_id');
            $table->timestamps();
        });
        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code')->unique();
            $table->softDeletes();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('priority_level')->default(0);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_client_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('studio_id');
            $table->timestamps();
            $table->unique(['client_id', 'studio_id']);
        });
    }
}
