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
