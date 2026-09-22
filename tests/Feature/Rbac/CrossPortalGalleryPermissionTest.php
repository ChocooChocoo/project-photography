<?php

namespace Tests\Feature\Rbac;

use App\Http\Controllers\StudioHR\OnlineGalleryController;
use App\Models\BookingModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression coverage for the cross-portal permission bug: an owner grants an
 * HR role a permission filed under the owner portal, but the HR account never
 * received it because permission resolution filtered by the permission's own
 * portal column.
 */
class CrossPortalGalleryPermissionTest extends TestCase
{
    private const GALLERY_MIDDLEWARE = 'permission:studio-hr.online-gallery.view,studio-hr.online-gallery.manage,owner.online-gallery.manage';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->clearPermissionCache();

        Route::middleware(['studio.hr', self::GALLERY_MIDDLEWARE])
            ->get('/_test/rbac/studio-hr/online-gallery', [OnlineGalleryController::class, 'index'])
            ->name('_test.studio-hr.online-gallery.index');
    }

    public function test_owner_portal_permission_granted_to_hr_role_resolves(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('owner.online-gallery.manage', 'owner', 'online-gallery', 'manage');
        $hrRole->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana@example.com');
        $hr->roles()->attach($hrRole->id, ['studio_id' => $studio->id]);

        // Cross-portal grant must resolve even though the permission's portal is
        // "owner" and the user's portal is "studio-hr".
        $this->assertTrue($hr->refresh()->hasPermission('owner.online-gallery.manage'));
        $this->assertTrue($hr->refresh()->hasPermission('owner.online-gallery.manage', $studio->id));
    }

    public function test_cross_portal_grant_is_enforced_on_gallery_route(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('owner.online-gallery.manage', 'owner', 'online-gallery', 'manage');
        $hrRole->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana@example.com');
        $hr->roles()->attach($hrRole->id, ['studio_id' => $studio->id]);

        $this->actingAs($hr)
            ->get('/_test/rbac/studio-hr/online-gallery')
            ->assertOk()
            ->assertSee('Online Gallery');
    }

    public function test_hr_without_gallery_grant_is_denied(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');

        $hr = $this->createUser('studio-hr', 'Hana', 'hana@example.com');
        $hr->roles()->attach($hrRole->id, ['studio_id' => $studio->id]);

        $this->actingAs($hr)
            ->get('/_test/rbac/studio-hr/online-gallery')
            ->assertRedirect(route('studio-hr.dashboard'));
    }

    public function test_hr_can_open_gallery_after_owner_grants_studio_hr_permission(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('studio-hr.online-gallery.view', 'studio-hr', 'online-gallery', 'view');
        $hrRole->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana@example.com');
        $hr->roles()->attach($hrRole->id, ['studio_id' => $studio->id]);

        $this->createGalleryBooking($studio, 'Gala Night', 'GAL-BOOK-1');

        $this->actingAs($hr)
            ->get('/_test/rbac/studio-hr/online-gallery')
            ->assertOk()
            ->assertSee('Online Gallery')
            ->assertSee('Gala Night');
    }

    public function test_studio_hr_online_gallery_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('studio-hr.online-gallery.index'));
        $this->assertTrue(Route::has('studio-hr.online-gallery.upload'));
        $this->assertTrue(Route::has('studio-hr.online-gallery.publish'));
    }

    public function test_sidebar_shows_gallery_link_only_with_permission(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $hrRole = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('owner.online-gallery.manage', 'owner', 'online-gallery', 'manage');
        $hrRole->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana@example.com');
        $hr->roles()->attach($hrRole->id, ['studio_id' => $studio->id]);

        $this->actingAs($hr);
        $html = view('layouts.studio-hr.sidebar')->render();
        $this->assertStringContainsString(route('studio-hr.online-gallery.index'), $html);

        $emptyRole = $this->createRole('studio-hr-staff', 'studio-hr');
        $hrWithout = $this->createUser('studio-hr', 'Rina', 'rina@example.com');
        $hrWithout->roles()->attach($emptyRole->id, ['studio_id' => $studio->id]);

        $this->actingAs($hrWithout);
        $htmlWithout = view('layouts.studio-hr.sidebar')->render();
        $this->assertStringNotContainsString(route('studio-hr.online-gallery.index'), $htmlWithout);
    }

    public function test_canonical_string_and_normalizer_own_the_format(): void
    {
        $this->assertSame(
            'studio-hr.online-gallery.manage',
            PermissionModel::canonicalString('studio-hr', 'online_gallery', 'manage')
        );
        $this->assertSame('portal.online-gallery.view', PermissionModel::normalizeIdentifier('online_gallery.view'));
        $this->assertSame(
            'studio-hr.online-gallery.manage',
            PermissionModel::normalizeIdentifier('Studio-HR.Online_Gallery.Manage')
        );
        $this->assertSame(
            'owner.online-gallery.manage',
            PermissionModel::normalizeIdentifier('owner:online-gallery:manage')
        );
    }

    public function test_missing_portal_prefix_matches_the_stored_photographer_grant(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $role = $this->createRole('studio-photographer', 'studio-photographer');
        $permission = $this->createPermission(
            'studio-photographer.online-gallery.view',
            'studio-photographer',
            'online-gallery',
            'view'
        );
        $role->permissions()->attach($permission->id);

        $photographer = $this->createUser('studio-photographer', 'Pia', 'pia@example.com');
        $photographer->roles()->attach($role->id, ['studio_id' => $studio->id]);

        $photographer->refresh();
        $this->assertTrue($photographer->hasPermission('online_gallery.view'));
        $this->assertTrue($photographer->hasPermission('studio-photographer.online-gallery.view'));
    }

    public function test_underscore_and_hyphen_are_the_same_character(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('studio-hr.online_gallery.manage', 'studio-hr', 'online_gallery', 'manage');
        $role->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana-underscore@example.com');
        $hr->roles()->attach($role->id, ['studio_id' => $studio->id]);

        $hr->refresh();
        $this->assertTrue($hr->hasPermission('studio-hr.online-gallery.manage'));
        $this->assertTrue($hr->hasPermission('online-gallery.manage'));
        // Two real portals must stay apart.
        $this->assertFalse($hr->hasPermission('studio-finance.online-gallery.manage'));
    }

    public function test_middleware_accepts_a_legacy_underscore_permission(): void
    {
        Route::middleware(['studio.hr', 'permission:studio-hr.online_gallery.manage'])
            ->get('/_test/rbac/studio-hr/online-gallery-legacy', [OnlineGalleryController::class, 'index'])
            ->name('_test.studio-hr.online-gallery.legacy');

        $studio = $this->createStudio('Cross Portal Studio');
        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('studio-hr.online-gallery.manage', 'studio-hr', 'online-gallery', 'manage');
        $role->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana-legacy@example.com');
        $hr->roles()->attach($role->id, ['studio_id' => $studio->id]);

        $this->actingAs($hr)
            ->get('/_test/rbac/studio-hr/online-gallery-legacy')
            ->assertOk();
    }

    public function test_legacy_name_only_grant_does_not_match_a_canonical_permission(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $role = $this->createRole('studio-hr-manager', 'studio-hr');

        // The old seeds stored an action-first "name" and left the permission
        // string empty. The name is not the permission identity, so it must not
        // grant the canonical gallery permission in any portal.
        $permission = $this->createPermissionRow('manage_online_gallery', '', 'studio-hr', 'online-gallery', 'manage');
        $role->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana-name-only@example.com');
        $hr->roles()->attach($role->id, ['studio_id' => $studio->id]);

        $hr->refresh();
        $this->assertFalse($hr->hasPermission('owner.online-gallery.manage'));
        $this->assertFalse($hr->hasPermission('studio-hr.online-gallery.manage'));
    }

    public function test_legacy_action_first_permission_string_still_matches(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $role = $this->createRole('studio-hr-manager', 'studio-hr');

        // A legacy permission string has no portal and stores the action before
        // the resource. This tolerance must stay.
        $permission = $this->createPermissionRow('manage_employees_legacy', 'manage_employees', 'owner', 'employees', 'manage');
        $role->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana-legacy-string@example.com');
        $hr->roles()->attach($role->id, ['studio_id' => $studio->id]);

        $hr->refresh();
        $this->assertTrue($hr->hasPermission('owner.employees.manage'));
    }

    public function test_real_portal_grant_stays_inside_its_own_portal(): void
    {
        $studio = $this->createStudio('Cross Portal Studio');
        $role = $this->createRole('studio-hr-manager', 'studio-hr');
        $permission = $this->createPermission('studio-hr.online-gallery.manage', 'studio-hr', 'online-gallery', 'manage');
        $role->permissions()->attach($permission->id);

        $hr = $this->createUser('studio-hr', 'Hana', 'hana-real-portal@example.com');
        $hr->roles()->attach($role->id, ['studio_id' => $studio->id]);

        $hr->refresh();
        $this->assertTrue($hr->hasPermission('studio-hr.online-gallery.manage'));
        $this->assertFalse($hr->hasPermission('owner.online-gallery.manage'));
    }

    public function test_repair_migration_rewrites_and_merges_collisions(): void
    {
        $role = $this->createRole('studio-hr-manager', 'studio-hr');

        $legacy = $this->createPermissionRow(
            'studio_hr_online_gallery_view_legacy',
            'studio-hr.online_gallery.view',
            'studio-hr',
            'online_gallery',
            'view'
        );
        $canonical = $this->createPermissionRow(
            'studio_hr_online_gallery_view_canonical',
            'studio-hr.online-gallery.view',
            'studio-hr',
            'online-gallery',
            'view'
        );
        $role->permissions()->attach($legacy->id);
        $role->permissions()->attach($canonical->id);

        $migration = require database_path('migrations/2026_09_22_090200_normalize_permission_strings.php');
        $migration->up();

        $strings = DB::table('tbl_permissions')->whereNull('deleted_at')->pluck('permission_string')->all();
        $this->assertSame(count($strings), count(array_unique($strings)));
        $this->assertSame(['studio-hr.online-gallery.view'], array_values($strings));
        $this->assertTrue($role->refresh()->hasPermission('studio-hr.online-gallery.view'));

        // A second run finds nothing left to change.
        $migration->up();
        $this->assertSame(1, DB::table('tbl_permissions')->whereNull('deleted_at')->count());
    }

    private function createUser(string $role, string $firstName, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'Photographer',
            'first_name' => $firstName,
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '0917'.substr(md5($email), 0, 7),
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    /**
     * UserModel caches permissions in a static array keyed by user id. Because
     * each test resets the schema, ids restart at 1 and the cache would leak a
     * grant from one test into the next.
     */
    private function clearPermissionCache(): void
    {
        $property = new \ReflectionProperty(UserModel::class, 'permissionCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    private function createRole(string $name, string $portal): RoleModel
    {
        return RoleModel::create([
            'name' => $name,
            'portal' => $portal,
            'status' => 'active',
        ]);
    }

    private function createPermission(string $string, string $portal, string $resource, string $action): PermissionModel
    {
        return PermissionModel::create([
            'name' => str_replace(['.', '-'], '_', $string),
            'portal' => $portal,
            'resource' => $resource,
            'action' => $action,
            'permission_string' => $string,
            'status' => 'active',
        ]);
    }

    private function createPermissionRow(string $name, string $string, string $portal, string $resource, string $action): PermissionModel
    {
        return PermissionModel::create([
            'name' => $name,
            'portal' => $portal,
            'resource' => $resource,
            'action' => $action,
            'permission_string' => $string,
            'status' => 'active',
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

    private function createGalleryBooking(StudiosModel $studio, string $eventName, string $reference): BookingModel
    {
        $client = $this->createUser('client', 'Cleo', strtolower($reference).'@example.com');

        $package = DB::table('tbl_packages')->insertGetId([
            'studio_id' => $studio->id,
            'category_id' => null,
            'package_name' => 'Gallery Package',
            'package_price' => 5000,
            'online_gallery' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $booking = BookingModel::create([
            'booking_reference' => $reference,
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'category_id' => null,
            'event_name' => $eventName,
            'event_date' => '2026-08-10',
            'total_amount' => 5000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => 'in_progress',
            'payment_status' => 'pending',
        ]);

        DB::table('tbl_booking_packages')->insert([
            'booking_id' => $booking->id,
            'package_id' => $package,
            'package_type' => 'studio',
            'package_name' => 'Gallery Package',
            'package_price' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $booking;
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

        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->string('resource')->nullable();
            $table->string('action')->nullable();
            $table->string('permission_string')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('studio_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'role_id', 'studio_id']);
        });

        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id')->nullable();
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->default(0);
            $table->boolean('online_gallery')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->foreignId('category_id')->nullable();
            $table->string('event_name');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location_type')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_type')->nullable();
            $table->string('package_name')->nullable();
            $table->decimal('package_price', 10, 2)->nullable();
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('gallery_type')->nullable();
            $table->string('gallery_reference')->nullable();
            $table->string('gallery_name')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->integer('total_photos')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('gallery_status')->nullable();
            $table->timestamps();
        });
    }
}
