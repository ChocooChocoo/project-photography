<?php

namespace Tests\Feature\StudioManagement;

use App\Http\Controllers\Client\BookingController;
use App\Http\Controllers\StudioOwner\DiscountRuleController;
use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\ClientBudgetModel;
use App\Models\StudioOwner\DiscountRuleModel;
use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiscountRuleTest extends TestCase
{
    private UserModel $owner;

    private UserModel $otherOwner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'discount-owner@example.com');
        $this->otherOwner = $this->createUser('owner', 'other-owner@example.com');

        Route::get('/_test/owner/discounts', [DiscountRuleController::class, 'index']);
        Route::post('/_test/owner/discounts', [DiscountRuleController::class, 'store']);
        Route::put('/_test/owner/discounts/{rule}', [DiscountRuleController::class, 'update']);
        Route::delete('/_test/owner/discounts/{rule}', [DiscountRuleController::class, 'destroy']);
        Route::get('/_test/client/booking-create/{type}/{id}', [BookingController::class, 'create']);

        foreach ([
            'client.dashboard' => '/_test/client/dashboard',
            'client.my-bookings.index' => '/_test/client/bookings',
            'client.my-bookings.history' => '/_test/client/bookings/history',
            'client.online-gallery.index' => '/_test/client/gallery',
            'client.budget.index' => '/_test/client/budget',
            'client.profile' => '/_test/client/profile',
            'notifications.unread-count' => '/_test/notifications/unread',
            'notifications.recent' => '/_test/notifications/recent',
            'notifications.mark-all-read' => '/_test/notifications/read-all',
            'notifications.mark-read' => '/_test/notifications/read/{id}',
            'notifications.index' => '/_test/notifications',
            'auth.logout' => '/_test/auth/logout',
        ] as $name => $uri) {
            Route::match(['get', 'post'], $uri, fn () => 'ok')->name($name);
        }
    }

    public function test_owner_can_create_a_discount_rule_for_their_studio(): void
    {
        $studio = $this->createStudio($this->owner);

        $this->actingAs($this->owner)
            ->post('/_test/owner/discounts', [
                'studio_id' => $studio->id,
                'name' => 'Early Bird Special',
                'percentage' => 10,
                'description' => '10% off for bookings made 30 days ahead.',
                'is_active' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tbl_studio_discount_rules', [
            'studio_id' => $studio->id,
            'name' => 'Early Bird Special',
            'percentage' => '10.00',
            'description' => '10% off for bookings made 30 days ahead.',
            'is_active' => 1,
        ]);
    }

    public function test_discount_rule_validation_rejects_invalid_percentage_and_missing_name(): void
    {
        $studio = $this->createStudio($this->owner);

        $this->actingAs($this->owner)
            ->post('/_test/owner/discounts', [
                'studio_id' => $studio->id,
                'name' => '',
                'percentage' => 150,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'percentage']);
    }

    public function test_owner_cannot_create_a_discount_rule_for_someone_elses_studio(): void
    {
        $otherStudio = $this->createStudio($this->otherOwner);

        $this->actingAs($this->owner)
            ->post('/_test/owner/discounts', [
                'studio_id' => $otherStudio->id,
                'name' => 'Sneaky Discount',
                'percentage' => 5,
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('tbl_studio_discount_rules', 0);
    }

    public function test_owner_can_update_a_discount_rule(): void
    {
        $studio = $this->createStudio($this->owner);
        $rule = $this->createRule($studio, 'Old Name', 5);

        $this->actingAs($this->owner)
            ->put('/_test/owner/discounts/'.$rule->id, [
                'studio_id' => $studio->id,
                'name' => 'New Name',
                'percentage' => 15,
                'description' => 'Updated description.',
                'is_active' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $rule->fresh();

        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('15.00', $fresh->percentage);
        $this->assertFalse((bool) $fresh->is_active);
    }

    public function test_owner_cannot_update_another_owners_discount_rule(): void
    {
        $studio = $this->createStudio($this->otherOwner);
        $rule = $this->createRule($studio, 'Their Rule', 5);

        $this->actingAs($this->owner)
            ->put('/_test/owner/discounts/'.$rule->id, [
                'studio_id' => $studio->id,
                'name' => 'Hijacked',
                'percentage' => 5,
            ])
            ->assertStatus(403);
    }

    public function test_destroy_soft_deletes_the_rule_and_index_excludes_it(): void
    {
        $studio = $this->createStudio($this->owner);
        $rule = $this->createRule($studio, 'Keep Me', 5);

        $this->actingAs($this->owner)
            ->delete('/_test/owner/discounts/'.$rule->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull(DiscountRuleModel::withTrashed()->find($rule->id)->deleted_at);
        $this->assertNull(DiscountRuleModel::find($rule->id));
        $this->assertDatabaseCount('tbl_studio_discount_rules', 1);
    }

    public function test_discount_rules_page_lists_the_owners_rules(): void
    {
        $studio = $this->createStudio($this->owner);
        $this->createRule($studio, 'Weekend Rate', 10);
        $this->createRule($studio, 'Loyalty Discount', 20);

        $this->actingAs($this->owner)
            ->get('/_test/owner/discounts')
            ->assertOk()
            ->assertSee('Weekend Rate')
            ->assertSee('Loyalty Discount');
    }

    public function test_active_discount_rules_are_passed_to_and_displayed_on_the_booking_form(): void
    {
        $studio = $this->createStudio($this->owner);
        $this->createRule($studio, 'Early Bird Special', 10);
        $this->createRule($studio, 'Inactive Rule', 50, false);

        $this->seedBookingContext($studio);

        $response = $this->actingAs($this->createUser('client', 'booking-client@example.com'))
            ->get('/_test/client/booking-create/studio/'.$studio->id)
            ->assertOk();

        $response->assertViewHas('discounts', function ($discounts) {
            return $discounts->count() === 1
                && $discounts->first()->name === 'Early Bird Special'
                && (int) $discounts->first()->percentage === 10;
        });

        $response->assertSee('Discounts available')
            ->assertSee('Early Bird Special')
            ->assertSee('10% OFF');
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000005',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(UserModel $owner): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Discount Studio',
            'status' => 'verified',
            'downpayment_percentage' => 30,
            'requires_downpayment' => true,
            'permit_expiry_date' => Carbon::today()->addYear(),
        ]);
    }

    private function createRule(StudiosModel $studio, string $name, int $percentage, bool $active = true): DiscountRuleModel
    {
        return DiscountRuleModel::create([
            'studio_id' => $studio->id,
            'name' => $name,
            'percentage' => $percentage,
            'description' => 'Test rule description.',
            'is_active' => $active,
        ]);
    }

    private function seedBookingContext(StudiosModel $studio): void
    {
        $category = CategoriesModel::create([
            'category_name' => 'Wedding Photography',
            'description' => 'Wedding coverage.',
            'status' => 'active',
        ]);

        StudioPackagesModel::create([
            'studio_id' => $studio->id,
            'category_id' => $category->id,
            'package_name' => 'Basic Package',
            'package_description' => 'Standard coverage.',
            'package_inclusions' => ['4 hours coverage'],
            'duration' => 4,
            'maximum_edited_photos' => 100,
            'coverage_scope' => 'On-Site',
            'package_price' => 10000,
            'package_location' => ['In-Studio'],
            'allow_multiple_locations' => false,
            'max_locations' => 1,
            'status' => 'active',
        ]);

        StudioScheduleModel::create([
            'studio_id' => $studio->id,
            'operating_days' => [strtolower(Carbon::today()->format('l'))],
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'booking_limit' => 5,
            'advance_booking' => 3,
        ]);

        \App\Models\StudioPlanModel::create([
            'studio_id' => $studio->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'end_date' => Carbon::today()->addDays(30),
        ]);

        LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'Imus',
            'barangay' => ['Bucandala III'],
            'zip_code' => '4103',
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
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
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
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal');
            $table->text('description')->nullable();
            $table->string('status');
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('studio_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->decimal('downpayment_percentage', 8, 2)->nullable();
            $table->boolean('requires_downpayment')->default(true);
            $table->date('permit_expiry_date')->nullable();
            $table->integer('max_clients_per_day')->nullable();
            $table->integer('advance_booking_days')->nullable();
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
        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->text('operating_days')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->integer('booking_limit')->nullable();
            $table->integer('advance_booking')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->date('end_date')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->date('grace_ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->string('package_name');
            $table->text('package_description')->nullable();
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->nullable();
            $table->string('coverage_scope')->nullable();
            $table->decimal('package_price', 10, 2);
            $table->json('package_location')->nullable();
            $table->boolean('allow_multiple_locations')->default(false);
            $table->integer('max_locations')->default(1);
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_client_budget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->string('budget_name')->nullable();
            $table->decimal('maximum_budget', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
