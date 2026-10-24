<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use App\Services\FreelancerBookingAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FreelancerAccessTest extends TestCase
{
    protected UserModel $freelancer;

    protected BookingModel $booking;

    protected BookingAssignedPhotographerModel $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        Schema::create('tbl_users', function (Blueprint $t) {
            $t->string('uuid')->nullable();
            $t->string('user_type')->nullable();
            $t->id();
            $t->string('email')->unique();
            $t->string('password');
            $t->string('role');
            $t->string('status')->default('active');
            $t->string('first_name');
            $t->string('last_name');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('studio_name');
            $t->string('status');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_reference');
            $t->unsignedBigInteger('client_id');
            $t->string('booking_type');
            $t->unsignedBigInteger('provider_id');
            $t->date('event_date');
            $t->date('event_end_date')->nullable();
            $t->time('start_time');
            $t->time('end_time');
            $t->string('status');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_booking_assigned_photographers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('studio_id');
            $t->unsignedBigInteger('photographer_id');
            $t->unsignedBigInteger('assigned_by');
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('tbl_studio_members', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('studio_id');
            $t->unsignedBigInteger('freelancer_id');
            $t->string('status');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('portal');
            $t->text('description')->nullable();
            $t->string('status');
            $t->boolean('is_system')->default(false);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('portal');
            $t->string('resource');
            $t->string('action');
            $t->string('permission_string');
            $t->text('description')->nullable();
            $t->string('status');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_role_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });
        Schema::create('tbl_user_roles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('studio_id')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'role_id', 'studio_id'], 'tbl_user_roles_user_id_role_id_studio_id_unique');
        });
        Schema::create('tbl_notifications', function (Blueprint $t) {
            $t->string('uuid');
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('type');
            $t->string('title');
            $t->text('message');
            $t->json('data')->nullable();
            $t->string('icon')->nullable();
            $t->string('color')->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::table('tbl_bookings', fn (Blueprint $t) => $t->string('preferred_camera_brand')->nullable());
        Schema::create('tbl_booking_equipment', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('assignment_id')->nullable();
            $t->unsignedBigInteger('equipment_id')->nullable();
            $t->string('equipment_name');
            $t->string('equipment_type');
            $t->string('brand')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('confirmed')->default(false);
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamps();
        });
        $owner = $this->user('owner');
        $this->freelancer = $this->user('freelancer');
        $studio = StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'Access studio', 'status' => 'verified']);
        $this->booking = BookingModel::create(['booking_reference' => 'ACCESS-1', 'client_id' => $owner->id, 'booking_type' => 'studio', 'provider_id' => $studio->id, 'event_date' => now()->addDays(2)->toDateString(), 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'status' => 'confirmed']);
        $this->assignment = BookingAssignedPhotographerModel::create(['booking_id' => $this->booking->id, 'studio_id' => $studio->id, 'photographer_id' => $this->freelancer->id, 'assigned_by' => $owner->id, 'status' => 'assigned']);
        DB::table('tbl_studio_members')->insert(['studio_id' => $studio->id, 'freelancer_id' => $this->freelancer->id, 'status' => 'approved']);
    }

    protected function user(string $role): UserModel
    {
        return UserModel::create(['email' => $role.'@access.test', 'password' => 'Password_123', 'role' => $role, 'first_name' => 'Access', 'last_name' => $role, 'status' => 'active']);
    }

    protected function migrateAccess(): void
    {
        (require database_path('migrations/2026_10_13_150000_add_freelancer_booking_access.php'))->up();
        $this->assignment->update(['is_freelancer' => true]);
    }

    public function test_temporary_permission_is_booking_scoped_and_expires_with_a_warm_cache(): void
    {
        $this->migrateAccess();
        $service = app(FreelancerBookingAccess::class);
        $service->grant($this->assignment);
        $permission = 'freelancer.studio-assignments.view';
        $this->assertTrue($this->freelancer->hasPermission($permission, $this->assignment->studio_id, $this->booking->id));
        $this->assertFalse($this->freelancer->hasPermission($permission, $this->assignment->studio_id));
        $this->assertFalse($this->freelancer->hasPermission($permission, $this->assignment->studio_id, $this->booking->id + 1));
        $this->travelTo($service->eventEnd($this->booking));
        $this->assertFalse($this->freelancer->hasPermission($permission, $this->assignment->studio_id, $this->booking->id));
        $this->assertDatabaseCount('tbl_user_roles', 1);
        $this->artisan('bookings:revoke-freelancer-access')->assertSuccessful();
        $this->assertDatabaseCount('tbl_user_roles', 0);
        $this->assertDatabaseCount('tbl_notifications', 3);
        $this->artisan('bookings:revoke-freelancer-access')->assertSuccessful();
        $this->assertDatabaseCount('tbl_notifications', 3);
    }

    public function test_revocation_preserves_another_booking_and_permanent_grants(): void
    {
        $this->migrateAccess();
        $service = app(FreelancerBookingAccess::class);
        $service->grant($this->assignment);
        $other = $this->booking->replicate();
        $other->booking_reference = 'ACCESS-2';
        $other->save();
        $assignment = $this->assignment->replicate();
        $assignment->booking_id = $other->id;
        $assignment->unsetRelations();
        $assignment->save();
        $service->grant($assignment);
        $role = DB::table('tbl_user_roles')->first()->role_id;
        DB::table('tbl_user_roles')->insert(['user_id' => $this->freelancer->id, 'role_id' => $role, 'studio_id' => $this->assignment->studio_id]);
        $this->booking->update(['status' => 'cancelled']);
        $this->assertDatabaseCount('tbl_user_roles', 2);
        $this->assertTrue($this->freelancer->hasPermission('freelancer.studio-assignments.view', $assignment->studio_id, $other->id));
        $assignment->delete();
        $this->assertDatabaseCount('tbl_user_roles', 1);
        $this->assertDatabaseHas('tbl_user_roles', ['booking_id' => null, 'expires_at' => null]);
    }

    public function test_assignment_rejection_revokes_access_immediately(): void
    {
        $this->migrateAccess();
        app(FreelancerBookingAccess::class)->grant($this->assignment);
        $this->assignment->update(['status' => 'cancelled']);
        $this->assertDatabaseCount('tbl_user_roles', 0);
        $this->assertDatabaseCount('tbl_studio_members', 1);
    }

    public function test_multiday_event_expiry_uses_last_date_and_manila_timezone(): void
    {
        $this->migrateAccess();
        $this->booking->update(['event_end_date' => '2026-12-25', 'end_time' => '20:00:00']);
        $this->assertSame('2026-12-25T20:00:00+08:00', app(FreelancerBookingAccess::class)->eventEnd($this->booking)->toIso8601String());
    }

    protected function prepareOwnerAssignment(): void
    {
        $this->migrateAccess();
        $this->assignment->delete();
        Schema::table('tbl_bookings', function (Blueprint $t) {
            $t->unsignedBigInteger('category_id')->nullable();
            $t->string('event_name')->nullable();
            $t->string('location_type')->nullable();
            $t->decimal('total_amount')->default(0);
            $t->decimal('down_payment')->default(0);
        });
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $t) {
            $t->text('assignment_notes')->nullable();
            $t->timestamp('assigned_at')->nullable();
            $t->timestamp('response_deadline')->nullable();
        });
        Schema::create('tbl_booking_packages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('package_id');
            $t->string('package_type');
            $t->string('package_name');
            $t->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('studio_id');
            $t->string('package_name');
            $t->integer('photographer_count');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_studio_photographers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('studio_id');
            $t->unsignedBigInteger('photographer_id');
            $t->string('status');
            $t->string('position')->nullable();
            $t->integer('years_of_experience')->nullable();
            $t->string('specialization')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_leave_requests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('status');
            $t->date('start_date');
            $t->date('end_date');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $t) {
            $t->id();
            $t->string('category_name');
            $t->softDeletes();
        });
        $package = DB::table('tbl_packages')->insertGetId(['studio_id' => $this->booking->provider_id, 'package_name' => 'Fallback package', 'photographer_count' => 1]);
        DB::table('tbl_booking_packages')->insert(['booking_id' => $this->booking->id, 'package_id' => $package, 'package_type' => 'studio', 'package_name' => 'Fallback package']);
        \Illuminate\Support\Facades\Route::post('/_fallback/{bookingId}', [\App\Http\Controllers\StudioOwner\BookingController::class, 'assignPhotographers']);
        \Illuminate\Support\Facades\Route::get('/_fallback/{bookingId}', [\App\Http\Controllers\StudioOwner\BookingController::class, 'getAvailablePhotographers']);
        $this->actingAs(UserModel::where('role', 'owner')->first());
    }

    public function test_owner_assigns_approved_fallback_and_grants_temporary_access(): void
    {
        $this->prepareOwnerAssignment();
        $this->getJson('/_fallback/'.$this->booking->id)->assertOk()->assertJsonPath('freelancers.0.id', $this->freelancer->id);
        $response = $this->postJson('/_fallback/'.$this->booking->id, ['photographer_ids' => [$this->freelancer->id]]);
        $this->assertSame(200, $response->status(), $response->getContent());
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('tbl_booking_assigned_photographers', ['photographer_id' => $this->freelancer->id, 'is_freelancer' => true]);
        $this->assertDatabaseHas('tbl_user_roles', ['user_id' => $this->freelancer->id, 'booking_id' => $this->booking->id]);
    }

    public function test_owner_cannot_bypass_available_staff_or_membership(): void
    {
        $this->prepareOwnerAssignment();
        $staff = $this->user('studio-photographer');
        DB::table('tbl_studio_photographers')->insert(['studio_id' => $this->booking->provider_id, 'photographer_id' => $staff->id, 'status' => 'active']);
        $this->postJson('/_fallback/'.$this->booking->id, ['photographer_ids' => [$this->freelancer->id]])->assertStatus(409)->assertJsonPath('success', false);
        DB::table('tbl_studio_photographers')->delete();
        DB::table('tbl_studio_members')->update(['status' => 'pending']);
        $this->postJson('/_fallback/'.$this->booking->id, ['photographer_ids' => [$this->freelancer->id]])->assertStatus(409)->assertJsonPath('success', false);
        $this->assertDatabaseCount('tbl_booking_assigned_photographers', 0);
    }

    public function test_fallback_conflicts_with_freelancers_direct_bookings(): void
    {
        $this->prepareOwnerAssignment();
        $direct = $this->booking->replicate();
        $direct->booking_type = 'freelancer';
        $direct->provider_id = $this->freelancer->id;
        $direct->booking_reference = 'DIRECT';
        $direct->save();
        $this->postJson('/_fallback/'.$this->booking->id, ['photographer_ids' => [$this->freelancer->id]])->assertStatus(409)->assertJsonPath('success', false);
        $this->assertDatabaseCount('tbl_user_roles', 0);
    }

    public function test_access_migration_rollback_preserves_permanent_rows(): void
    {
        $this->migrateAccess();
        DB::table('tbl_user_roles')->insert(['user_id' => $this->freelancer->id, 'role_id' => 1, 'studio_id' => $this->assignment->studio_id]);
        (require database_path('migrations/2026_10_13_150000_add_freelancer_booking_access.php'))->down();
        $this->assertFalse(Schema::hasColumn('tbl_user_roles', 'expires_at'));
        $this->assertDatabaseCount('tbl_user_roles', 1);
        $this->assertDatabaseCount('tbl_bookings', 1);
    }

    public function test_freelancer_routes_deny_wrong_booking_and_expired_access(): void
    {
        $this->migrateAccess();
        app(FreelancerBookingAccess::class)->grant($this->assignment);
        $this->actingAs($this->freelancer)->getJson('/freelancer/studio-assignments/'.$this->assignment->id)->assertOk()->assertJsonPath('assignment.booking_id', $this->booking->id);
        $other = $this->assignment->replicate();
        $other->photographer_id = UserModel::where('role', 'owner')->value('id');
        $other->save();
        $this->getJson('/freelancer/studio-assignments/'.$other->id)->assertForbidden();
        $this->travelTo(app(FreelancerBookingAccess::class)->eventEnd($this->booking));
        $this->getJson('/freelancer/studio-assignments/'.$this->assignment->id)->assertForbidden();
        $this->postJson('/freelancer/studio-assignments/'.$this->assignment->id.'/status', ['status' => 'confirmed'])->assertForbidden();
        $this->postJson('/freelancer/studio-assignments/'.$this->assignment->id.'/images', ['images' => []])->assertForbidden();
    }

    public function test_fallback_freelancer_sees_and_confirms_only_this_bookings_equipment(): void
    {
        $this->migrateAccess();
        app(FreelancerBookingAccess::class)->grant($this->assignment);
        $this->booking->update(['preferred_camera_brand' => 'Canon']);
        $item = \App\Models\StudioOwner\BookingEquipmentModel::create(['booking_id' => $this->booking->id, 'equipment_name' => 'Camera MARK VI', 'equipment_type' => 'DSLR', 'brand' => 'NIKON']);
        $otherItem = \App\Models\StudioOwner\BookingEquipmentModel::create(['booking_id' => $this->booking->id + 100, 'equipment_name' => 'Flash', 'equipment_type' => 'lighting']);

        $this->actingAs($this->freelancer)->getJson('/freelancer/studio-assignments/'.$this->assignment->id)
            ->assertOk()
            ->assertJsonPath('equipment.0.equipment_name', 'Camera MARK VI')
            ->assertJsonPath('equipment.0.brand_mismatch', true);
        $this->get('/freelancer/studio-assignments')->assertOk()
            ->assertSee('Camera MARK VI')
            ->assertSee("Brand differs from the client's preference (Canon)", false)
            ->assertSee('Confirm equipment');

        $confirm = fn ($equipmentId) => '/freelancer/studio-assignments/'.$this->assignment->id.'/equipment/'.$equipmentId.'/confirm';
        $this->postJson($confirm($otherItem->id))->assertNotFound();
        $this->assertFalse($otherItem->fresh()->confirmed);
        $this->postJson($confirm($item->id))->assertOk()->assertJsonPath('success', true);
        $this->assertTrue($item->fresh()->confirmed);

        $this->travelTo(app(FreelancerBookingAccess::class)->eventEnd($this->booking));
        $item->update(['confirmed' => false]);
        $this->postJson($confirm($item->id))->assertForbidden();
    }

    public function test_freelancer_page_shows_active_work_and_expired_empty_state(): void
    {
        $this->migrateAccess();
        app(FreelancerBookingAccess::class)->grant($this->assignment);
        $this->actingAs($this->freelancer)->get('/freelancer/studio-assignments')->assertOk()->assertSee('ACCESS-1')->assertSee('Access ends');
        $this->travelTo(app(FreelancerBookingAccess::class)->eventEnd($this->booking));
        $this->get('/freelancer/studio-assignments')->assertOk()->assertDontSee('ACCESS-1')->assertSee('No active studio assignments');
    }

    public function test_freelancer_upload_validates_files_and_submits_draft_for_owner_review(): void
    {
        $this->prepareOwnerAssignment();
        Schema::table('tbl_packages', fn (Blueprint $t) => $t->boolean('online_gallery')->default(true));
        Schema::create('tbl_studio_online_gallery', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('booking_id');
            $t->unsignedBigInteger('studio_id');
            $t->unsignedBigInteger('client_id');
            $t->string('gallery_reference');
            $t->string('gallery_name');
            $t->text('description')->nullable();
            $t->json('images');
            $t->integer('total_photos');
            $t->string('status');
            $t->string('gallery_status');
            $t->string('approval_status')->nullable();
            $t->text('rejection_reason')->nullable();
            foreach (['submitted', 'approved', 'rejected'] as $prefix) {
                $t->unsignedBigInteger($prefix.'_by')->nullable();
                $t->timestamp($prefix.'_at')->nullable();
            }
            $t->timestamps();
        });
        $assignment = BookingAssignedPhotographerModel::create(['booking_id' => $this->booking->id, 'studio_id' => $this->booking->provider_id, 'photographer_id' => $this->freelancer->id, 'assigned_by' => 1, 'status' => 'in_progress', 'is_freelancer' => true]);
        app(FreelancerBookingAccess::class)->grant($assignment);
        $this->actingAs($this->freelancer)->postJson('/freelancer/studio-assignments/'.$assignment->id.'/images', ['images' => []])->assertUnprocessable()->assertJsonValidationErrors('images');
        \Illuminate\Support\Facades\Storage::fake('public');
        $image = \Illuminate\Http\UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWf8AAAAASUVORK5CYII='));
        $response = $this->post('/freelancer/studio-assignments/'.$assignment->id.'/images', ['images' => [$image]], ['Accept' => 'application/json']);
        $this->assertSame(200, $response->status(), $response->getContent());
        $this->assertDatabaseHas('tbl_studio_online_gallery', ['booking_id' => $this->booking->id, 'approval_status' => 'pending', 'submitted_by' => $this->freelancer->id, 'total_photos' => 1]);
    }

    public function test_inactive_user_or_removed_membership_cannot_use_a_live_grant(): void
    {
        $this->migrateAccess();
        $access = app(FreelancerBookingAccess::class);
        $access->grant($this->assignment);
        $this->assertTrue($access->allows($this->freelancer, $this->assignment));
        $this->freelancer->update(['status' => 'inactive']);
        $this->assertFalse($access->allows($this->freelancer, $this->assignment));
        $this->freelancer->update(['status' => 'active']);
        DB::table('tbl_studio_members')->update(['status' => 'cancelled']);
        $this->assertFalse($access->allows($this->freelancer, $this->assignment));
    }

    public function test_owner_cannot_select_a_client_as_a_photographer(): void
    {
        $this->prepareOwnerAssignment();
        $client = $this->user('client');
        $this->postJson('/_fallback/'.$this->booking->id, ['photographer_ids' => [$client->id]])->assertUnprocessable();
        $this->assertDatabaseCount('tbl_booking_assigned_photographers', 0);
    }

    public function test_fresh_seed_chain_restores_fallback_role_and_role_seed_is_idempotent(): void
    {
        $this->migrateAccess();
        (new \Database\Seeders\StudioFreelancerRoleSeeder)->run();
        $this->assertDatabaseCount('tbl_roles', 1);
        $this->assertDatabaseCount('tbl_permissions', 3);
        $this->assertDatabaseCount('tbl_role_permissions', 3);
        $this->assertStringContainsString('StudioFreelancerRoleSeeder::class', file_get_contents(database_path('seeders/Fresh/FreshSeedSeeder.php')));
    }

    public function test_migration_preserves_permanent_role_uniqueness(): void
    {
        $this->migrateAccess();
        $row = ['user_id' => $this->freelancer->id, 'role_id' => 1, 'studio_id' => $this->assignment->studio_id];
        DB::table('tbl_user_roles')->insert($row);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('tbl_user_roles')->insert($row);
    }
}
