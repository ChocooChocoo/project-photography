<?php

namespace Tests\Feature\Owner;

use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression coverage for the owner studio update endpoint. Editing a studio
 * must not require re-uploading the owner profile photo, and a validation
 * failure must tell the user which field was wrong instead of failing quietly.
 */
class StudioUpdateTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Storage::fake('public');

        $this->owner = UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Studio',
            'last_name' => 'Owner',
            'email' => 'studio-update-owner@example.com',
            'mobile_number' => '09170002001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\OwnerMiddleware::class,
            \App\Http\Middleware\EnforceStudioSubscriptionAccess::class,
            \App\Http\Middleware\CheckStudioRegistrationLimit::class,
            \App\Http\Middleware\CheckPermissionMiddleware::class,
        ]);
    }

    public function test_update_without_reuploading_profile_photo_succeeds(): void
    {
        $studio = $this->createStudio('Existing Studio');

        $payload = $this->validStudioPayload();
        unset($payload['owner_profile_photo']);

        $response = $this->actingAs($this->owner)->put(
            route('owner.studio.update', $studio->id),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Studio updated successfully!');

        $studio->refresh();
        $this->assertSame('Lens & Light Studio', $studio->studio_name);
        $this->assertSame('A full-service photography studio in Cavite.', $studio->studio_description);
    }

    public function test_update_validation_failure_surfaces_the_field_message(): void
    {
        $studio = $this->createStudio('Existing Studio');

        $payload = $this->validStudioPayload();
        unset($payload['owner_profile_photo']);
        unset($payload['studio_name']);

        $response = $this->actingAs($this->owner)->put(
            route('owner.studio.update', $studio->id),
            $payload
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('studio_name')
            ->assertJsonPath('success', false);

        $message = (string) $response->json('message');
        $this->assertNotSame('', $message);
        $this->assertNotSame('Validation failed.', $message);
        $this->assertStringContainsStringIgnoringCase('studio name', $message);
    }

    private function createStudio(string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => $name,
            'status' => 'verified',
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
            'service_categories' => [$category->id],
            'starting_price' => 10000,
            'downpayment_percentage' => 30,
            'operating_days' => ['monday', 'tuesday'],
            'start_time' => '08:00',
            'end_time' => '17:00',
            'max_clients_per_day' => 5,
            'advance_booking_days' => 3,
            'permit_expiry_date' => '2027-01-31',
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
            $table->softDeletes();
            $table->timestamps();
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
