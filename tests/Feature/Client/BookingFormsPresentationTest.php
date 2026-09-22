<?php

namespace Tests\Feature\Client;

use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
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

class BookingFormsPresentationTest extends TestCase
{
    private UserModel $client;
    private StudiosModel $studio;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');

        $this->client = $this->createUser('client');
        $owner = $this->createUser('owner');
        $owner->update(['email' => 'booking-owner@example.com']);

        LocationModel::create([
            'municipality' => 'Dasmariñas',
            'status' => 'active',
        ]);

        $category = CategoriesModel::create([
            'category_name' => 'Weddings',
            'description' => null,
            'status' => 'active',
        ]);

        $this->studio = StudiosModel::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'location_id' => 1,
            'studio_name' => 'Booking Studio',
            'status' => 'verified',
            'studio_logo' => null,
            'starting_price' => 5000,
            'downpayment_percentage' => 30,
        ]);

        $this->createSubscription($this->studio, 3);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_booking_forms_page_keeps_full_payment_option_without_discount_claim(): void
    {
        $response = $this->actingAs($this->client)->get(route('client.booking-forms', [
            'type' => 'studio',
            'id' => $this->studio->id,
        ]));

        $response->assertOk();

        $html = $response->getOriginalContent()->render();
        $this->assertStringContainsString('Full Payment', $html);
        $this->assertStringNotContainsString('5% OFF', $html);
        $this->assertStringNotContainsString('5% off', $html);
    }

    public function test_booking_forms_package_selection_renders_cover_images_when_present(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('packages/prewedding.jpg', 'image-content');

        PackagesModel::create([
            'studio_id' => $this->studio->id,
            'category_id' => 1,
            'package_name' => 'Pre-Wedding Package',
            'package_description' => 'A beautiful pre-wedding shoot.',
            'package_price' => 8000,
            'duration' => 4,
            'maximum_edited_photos' => 20,
            'package_inclusions' => json_encode(['Studio'], true),
            'coverage_scope' => json_encode(['In-Studio'], true),
            'online_gallery' => true,
            'photographer_count' => 1,
            'package_location' => json_encode(['In-Studio'], true),
            'allow_time_customization' => false,
            'allow_multiple_locations' => false,
            'max_locations' => 1,
            'cover_images' => ['packages/prewedding.jpg'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->client)->get(route('client.booking-forms', [
            'type' => 'studio',
            'id' => $this->studio->id,
        ]));

        $response->assertOk();

        $html = $response->getOriginalContent()->render();
        $this->assertStringContainsString('package.cover_thumbnail', $html);
    }

    public function test_booking_forms_page_uses_the_shared_email_format_check(): void
    {
        $response = $this->actingAs($this->client)->get(route('client.booking-forms', [
            'type' => 'studio',
            'id' => $this->studio->id,
        ]));

        $response->assertOk();

        $html = $response->getOriginalContent()->render();

        // The page loads the shared email format script.
        $this->assertStringContainsString('assets/js/pages/email-format.js', $html);
        $this->assertStringContainsString('window.PlatinumEmail.isValid', $html);

        // The old local regex is gone.
        $this->assertStringNotContainsString('const emailRegex', $html);
        $this->assertStringNotContainsString('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $html);
    }

    public function test_shared_email_format_script_publishes_the_pinned_global(): void
    {
        $scriptPath = public_path('assets/js/pages/email-format.js');

        $this->assertFileExists($scriptPath);

        $script = file_get_contents($scriptPath);
        $this->assertStringContainsString('window.PlatinumEmail', $script);
        $this->assertStringContainsString('isValid', $script);
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Booking',
            'last_name' => 'Test',
            'email' => $role.'-booking-forms@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
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
            $table->foreignId('category_id')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('studio_name');
            $table->string('status');
            $table->string('studio_logo')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('starting_price', 10, 2)->default(0);
            $table->decimal('downpayment_percentage', 5, 2)->default(30);
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->softDeletes();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('municipality');
            $table->softDeletes();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->string('package_name');
            $table->text('package_description')->nullable();
            $table->decimal('package_price', 10, 2);
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->json('package_inclusions')->nullable();
            $table->json('coverage_scope')->nullable();
            $table->boolean('online_gallery')->default(false);
            $table->integer('photographer_count')->default(1);
            $table->json('package_location')->nullable();
            $table->boolean('allow_time_customization')->default(false);
            $table->boolean('allow_multiple_locations')->default(false);
            $table->integer('max_locations')->default(1);
            $table->json('cover_images')->nullable();
            $table->softDeletes();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->json('operating_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_client_budget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('category_id')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studio_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->string('name', 100);
            $table->decimal('percentage', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
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
    }
}
