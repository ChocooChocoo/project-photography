<?php

namespace Tests\Feature\Admin;

use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PendingStudiosReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_pending_studios_page_shows_standardized_rejection_reasons(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('owner');
        $this->createStudio($owner);

        $this->actingAs($admin)
            ->get(route('admin.studio.pending'))
            ->assertOk()
            ->assertSee('Incomplete documents')
            ->assertSee('Expired permit')
            ->assertSee('Unclear permit photo')
            ->assertSee('Name mismatch')
            ->assertSee('Invalid business type')
            ->assertSee('Other');
    }

    public function test_pending_studios_page_shows_resubmission_count_and_permit_expiry(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('owner');
        $this->createStudio($owner);

        $this->actingAs($admin)
            ->get(route('admin.studio.pending'))
            ->assertOk()
            ->assertSee('Resubmissions')
            ->assertSee('2')
            ->assertSee('May 30, 2027');
    }

    public function test_pending_studios_page_has_in_app_document_viewer_modal(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('owner');
        $this->createStudio($owner);

        $this->actingAs($admin)
            ->get(route('admin.studio.pending'))
            ->assertOk()
            ->assertSee('Document Viewer')
            ->assertSee('Open in New Tab')
            ->assertSee('storage/permits/abc.pdf')
            ->assertSee('storage/ids/xyz.jpg');
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Review',
            'last_name' => 'Test',
            'email' => str_replace('-', '_', $role).'@example.com',
            'mobile_number' => '09170000001',
            'password' => Hash::make('secret'),
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(UserModel $owner): StudiosModel
    {
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Lens & Light Studio',
            'studio_type' => 'photography_studio',
            'year_established' => 2019,
            'studio_description' => 'A full-service photography studio.',
            'status' => 'pending',
            'business_permit' => 'permits/abc.pdf',
            'owner_id_document' => 'ids/xyz.jpg',
        ]);

        $studio->forceFill([
            'resubmission_count' => 2,
            'permit_expiry_date' => '2027-05-30',
        ])->save();

        return $studio;
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
            $table->softDeletes();
        });

        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province')->nullable();
            $table->string('municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('studio_name');
            $table->string('studio_type');
            $table->integer('year_established')->nullable();
            $table->text('studio_description')->nullable();
            $table->string('studio_logo')->nullable();
            $table->string('starting_price')->nullable();
            $table->json('operating_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('max_clients_per_day')->nullable();
            $table->integer('advance_booking_days')->nullable();
            $table->string('business_permit')->nullable();
            $table->string('owner_id_document')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_note')->nullable();
            $table->integer('resubmission_count')->default(0);
            $table->date('permit_expiry_date')->nullable();
            
            $table->softDeletes();$table->timestamps();
        });

        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pvt_studio_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id');
            $table->timestamps();
        });
    }
}
