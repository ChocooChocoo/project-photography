<?php

namespace Tests\Feature\StudioManagement;

use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudioCommercialSettingsTest extends TestCase
{
    private UserModel $owner;

    private array $uploadedPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createOwner();
    }

    protected function tearDown(): void
    {
        foreach ($this->uploadedPaths as $path) {
            Storage::disk('public')->delete($path);
        }

        parent::tearDown();
    }

    public function test_creating_studio_stores_maximum_price_linkedin_downpayment_and_permit_expiry(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.studio.store'), $this->validStudioPayload([
                'maximum_price' => 50000,
                'linkedin_url' => 'https://linkedin.com/company/test-studio',
                'requires_downpayment' => 0,
                'permit_expiry_date' => '2027-01-31',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tbl_studios', [
            'user_id' => $this->owner->id,
            'maximum_price' => 50000,
            'linkedin_url' => 'https://linkedin.com/company/test-studio',
            'requires_downpayment' => 0,
            'resubmission_count' => 0,
        ]);

        $studio = StudiosModel::where('user_id', $this->owner->id)->first();
        $this->assertSame('2027-01-31', $studio->permit_expiry_date->format('Y-m-d'));

        $this->trackUploads($studio);
    }

    public function test_creating_studio_defaults_to_requires_downpayment_when_field_is_absent(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.studio.store'), $this->validStudioPayload())
            ->assertOk()
            ->assertJsonPath('success', true);

        $studio = StudiosModel::where('user_id', $this->owner->id)->first();

        $this->assertTrue((bool) $studio->requires_downpayment);
        $this->assertNull($studio->maximum_price);
        $this->assertNull($studio->linkedin_url);

        $this->trackUploads($studio);
    }

    public function test_maximum_price_must_be_greater_than_or_equal_to_starting_price(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.studio.store'), $this->validStudioPayload([
                'starting_price' => 10000,
                'maximum_price' => 5000,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('maximum_price');
    }

    public function test_linkedin_url_must_be_a_valid_url(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.studio.store'), $this->validStudioPayload([
                'linkedin_url' => 'not-a-valid-url',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('linkedin_url');
    }

    public function test_permit_expiry_date_is_required_when_creating_a_studio(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.studio.store'), $this->validStudioPayload([
                'permit_expiry_date' => '',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('permit_expiry_date');
    }

    public function test_updating_studio_stores_commercial_fields_and_allows_nullable_permit_expiry_date(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\OwnerMiddleware::class,
            \App\Http\Middleware\EnforceStudioSubscriptionAccess::class,
            \App\Http\Middleware\CheckStudioRegistrationLimit::class,
            \App\Http\Middleware\CheckPermissionMiddleware::class,
        ]);

        $studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Existing Studio',
            'status' => 'verified',
            'permit_expiry_date' => '2027-01-31',
            'maximum_price' => 30000,
        ]);

        $this->actingAs($this->owner)
            ->put(route('owner.studio.update', $studio->id), $this->validStudioPayload([
                'studio_name' => 'Existing Studio',
                'maximum_price' => 60000,
                'linkedin_url' => 'https://linkedin.com/company/existing-studio',
                'requires_downpayment' => 0,
                'permit_expiry_date' => '2028-06-30',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $studio->fresh();

        $this->assertSame('60000.00', $fresh->maximum_price);
        $this->assertSame('https://linkedin.com/company/existing-studio', $fresh->linkedin_url);
        $this->assertFalse((bool) $fresh->requires_downpayment);
        $this->assertSame('2028-06-30', $fresh->permit_expiry_date->format('Y-m-d'));

        // Permit expiry may be omitted on edit (existing value preserved).
        $this->actingAs($this->owner)
            ->put(route('owner.studio.update', $studio->id), $this->validStudioPayload([
                'studio_name' => 'Existing Studio',
                'permit_expiry_date' => '',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('2028-06-30', $studio->fresh()->permit_expiry_date->format('Y-m-d'));
    }

    private function createOwner(): UserModel
    {
        return UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Commercial',
            'last_name' => 'Owner',
            'email' => 'commercial-owner@example.com',
            'mobile_number' => '09170000003',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function validStudioPayload(array $overrides = []): array
    {
        $category = CategoriesModel::firstOrCreate(
            ['category_name' => 'Wedding Photography'],
            ['description' => 'Wedding coverage.', 'status' => 'active']
        );

        return array_merge([
            'studio_name' => 'Lens & Light Studio',
            'studio_type' => 'photography_studio',
            'year_established' => 2020,
            'studio_description' => 'A full-service photography studio in Cavite.',
            'studio_logo' => UploadedFile::fake()->create('logo.jpg', 200, 'image/jpeg'),
            'province' => 'Cavite',
            'municipality' => 'Imus',
            'barangay' => 'Bucandala III',
            'attendance_latitude' => '14.4297',
            'attendance_longitude' => '120.9367',
            'attendance_radius_meters' => 100,
            'street' => '123 Test Street',
            'zip_code' => '4103',
            'contact_number' => '09170000000',
            'studio_email' => 'studio@example.com',
            'facebook_url' => 'https://facebook.com/teststudio',
            'instagram_url' => 'https://instagram.com/teststudio',
            'website_url' => 'https://teststudio.com',
            'service_categories' => [$category->id],
            'starting_price' => 10000,
            'downpayment_percentage' => 30,
            'operating_days' => ['monday', 'tuesday'],
            'start_time' => '08:00',
            'end_time' => '17:00',
            'max_clients_per_day' => 5,
            'advance_booking_days' => 3,
            'business_permit' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
            'owner_id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            'owner_profile_photo' => UploadedFile::fake()->create('profile.jpg', 200, 'image/jpeg'),
            'permit_expiry_date' => '2027-01-31',
        ], $overrides);
    }

    private function trackUploads(?StudiosModel $studio): void
    {
        if (! $studio) {
            return;
        }

        foreach ([$studio->studio_logo, $studio->business_permit, $studio->owner_id_document] as $path) {
            if ($path) {
                $this->uploadedPaths[] = $path;
            }
        }
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
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('category_id')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('studio_name');
            $table->string('studio_type')->nullable();
            $table->integer('year_established')->nullable();
            $table->text('studio_description')->nullable();
            $table->string('studio_logo')->nullable();
            $table->string('starting_price')->nullable();
            $table->decimal('maximum_price', 10, 2)->nullable();
            $table->decimal('downpayment_percentage', 8, 2)->nullable();
            $table->boolean('requires_downpayment')->default(true);
            $table->date('permit_expiry_date')->nullable();
            $table->unsignedInteger('resubmission_count')->default(0);
            $table->string('linkedin_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('studio_email')->nullable();
            $table->decimal('attendance_latitude', 10, 7)->nullable();
            $table->decimal('attendance_longitude', 10, 7)->nullable();
            $table->integer('attendance_radius_meters')->nullable();
            $table->json('operating_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('max_clients_per_day')->nullable();
            $table->integer('advance_booking_days')->nullable();
            $table->string('business_permit')->nullable();
            $table->string('owner_id_document')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_note')->nullable();
            $table->softDeletes();
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
        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('location_id')->nullable();
            $table->text('operating_days')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->integer('booking_limit')->nullable();
            $table->integer('advance_booking')->nullable();
            $table->timestamps();
        });
        Schema::create('pvt_studio_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->timestamps();
        });

        LocationModel::create([
            'province' => 'Cavite',
            'municipality' => 'Imus',
            'barangay' => ['Bucandala III', 'Bayan Luma III'],
            'zip_code' => '4103',
            'status' => 'active',
        ]);
    }
}
