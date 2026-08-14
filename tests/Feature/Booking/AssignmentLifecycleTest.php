<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Client\MyBookingsController;
use App\Http\Controllers\StudioOwner\BookingController as OwnerBookingController;
use App\Http\Controllers\StudioPhotographer\AssignedBookingController;
use App\Models\BookingModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssignmentLifecycleTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'assignment-owner@example.com');
        Auth::setUser($this->owner);

        Route::post('/_test/photographer/assignment/{id}/status', [AssignedBookingController::class, 'updateAssignmentStatus']);
        Route::post('/_test/client/assignment/{id}/confirm', [MyBookingsController::class, 'confirmPhotographerOnSite']);
        Route::post('/_test/owner/booking/{id}/status', [OwnerBookingController::class, 'updateStatus']);
        Route::post('/_test/owner/assignment/{id}/status', [OwnerBookingController::class, 'updateAssignmentStatus']);
    }

    public function test_assignment_full_lifecycle(): void
    {
        $client = $this->createUser('client', 'lifecycle-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'lifecycle-photographer@example.com');
        $studio = $this->createStudio();
        $this->grantAssignmentPermission($photographer, $studio);
        $booking = $this->createBooking($studio, $client, 'confirmed', 'on-location');
        $assignment = $this->createAssignment($booking, $studio, $photographer, 'assigned');

        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-LIFECYCLE',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        $this->actingAs($photographer)
            ->postJson("/_test/photographer/assignment/{$assignment->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $assignment->refresh();
        $this->assertSame('confirmed', $assignment->status);
        $this->assertNotNull($assignment->confirmed_at);
        $booking->refresh();
        $this->assertSame('in_progress', $booking->status);

        $this->actingAs($photographer)
            ->postJson("/_test/photographer/assignment/{$assignment->id}/status", ['status' => 'on_site'])
            ->assertOk();

        $assignment->refresh();
        $this->assertSame('on_site', $assignment->status);
        $this->assertNotNull($assignment->on_site_at);

        $this->actingAs($client)
            ->postJson("/_test/client/assignment/{$assignment->id}/confirm", ['confirmation_notes' => 'Photographer arrived on time.'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $assignment->refresh();
        $this->assertNotNull($assignment->client_confirmed_at);
        $this->assertSame('Photographer arrived on time.', $assignment->client_confirmation_notes);

        $this->actingAs($photographer)
            ->postJson("/_test/photographer/assignment/{$assignment->id}/status", ['status' => 'in_progress'])
            ->assertOk();

        $assignment->refresh();
        $this->assertSame('in_progress', $assignment->status);
        $this->assertNotNull($assignment->started_at);

        $this->actingAs($photographer)
            ->postJson("/_test/photographer/assignment/{$assignment->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $assignment->refresh();
        $this->assertSame('completed', $assignment->status);
        $this->assertNotNull($assignment->completed_at);
    }

    public function test_deadline_command_warns_but_does_not_cancel(): void
    {
        $client = $this->createUser('client', 'deadline-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'deadline-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, 'confirmed');
        $past = $this->createAssignment($booking, $studio, $photographer, 'assigned', now()->subDay());
        $future = $this->createAssignment($booking, $studio, $photographer, 'assigned', now()->addDay());

        $this->artisan('assignments:check-deadlines')->assertSuccessful();

        $this->assertSame('assigned', $past->fresh()->status);
        $this->assertSame('assigned', $future->fresh()->status);

        $this->assertSame(
            1,
            NotificationModel::where('user_id', $photographer->id)
                ->where('type', 'assignment_deadline_warning')
                ->count()
        );
    }

    public function test_owner_cancelling_booking_cancels_open_assignments(): void
    {
        $client = $this->createUser('client', 'cancel-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'cancel-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, 'confirmed');
        $open = $this->createAssignment($booking, $studio, $photographer, 'confirmed');
        $completed = $this->createAssignment($booking, $studio, $photographer, 'completed');

        Auth::setUser($this->owner);
        $this->postJson("/_test/owner/booking/{$booking->id}/status", [
            'status' => 'cancelled',
            'cancellation_reason' => 'Studio closed due to unforeseen circumstances and must reschedule all bookings.',
        ])->assertOk()->assertJsonPath('success', true);

        $open->refresh();
        $this->assertSame('cancelled', $open->status);
        $this->assertNotNull($open->cancelled_at);
        $this->assertStringContainsString('Studio closed', $open->cancellation_reason);

        $completed->refresh();
        $this->assertSame('completed', $completed->status);

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('studio', $booking->cancelled_by);
    }

    public function test_owner_can_cancel_or_complete_assignment(): void
    {
        $client = $this->createUser('client', 'owner-ops-client@example.com');
        $photographer = $this->createUser('studio-photographer', 'owner-ops-photographer@example.com');
        $studio = $this->createStudio();
        $booking = $this->createBooking($studio, $client, 'in_progress');
        $cancellable = $this->createAssignment($booking, $studio, $photographer, 'assigned');
        $completable = $this->createAssignment($booking, $studio, $photographer, 'in_progress', null, [
            'on_site_at' => now()->subHour(),
            'client_confirmed_at' => now()->subHour(),
            'started_at' => now()->subHour(),
        ]);
        $blocked = $this->createAssignment($booking, $studio, $photographer, 'assigned');

        Auth::setUser($this->owner);

        $this->postJson("/_test/owner/assignment/{$cancellable->id}/status", [
            'status' => 'cancelled',
            'cancellation_reason' => 'Photographer became unavailable.',
        ])->assertOk()->assertJsonPath('success', true);

        $cancellable->refresh();
        $this->assertSame('cancelled', $cancellable->status);
        $this->assertNotNull($cancellable->cancelled_at);
        $this->assertSame('Photographer became unavailable.', $cancellable->cancellation_reason);

        $this->postJson("/_test/owner/assignment/{$completable->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $completable->refresh();
        $this->assertSame('completed', $completable->status);
        $this->assertNotNull($completable->completed_at);

        $this->postJson("/_test/owner/assignment/{$blocked->id}/status", ['status' => 'completed'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertSame('assigned', $blocked->fresh()->status);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Assignment',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000005',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Assignment Studio',
            'status' => 'verified',
        ]);
    }

    private function createBooking(StudiosModel $studio, UserModel $client, string $status, string $locationType = 'in-studio'): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => '2026-08-10',
            'location_type' => $locationType,
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => $status,
            'payment_status' => 'pending',
        ]);
    }

    private function createAssignment(BookingModel $booking, StudiosModel $studio, UserModel $photographer, string $status, $deadline = null, array $overrides = []): BookingAssignedPhotographerModel
    {
        return BookingAssignedPhotographerModel::create(array_merge([
            'booking_id' => $booking->id,
            'studio_id' => $studio->id,
            'photographer_id' => $photographer->id,
            'assigned_by' => $this->owner->id,
            'status' => $status,
            'assigned_at' => now(),
            'response_deadline' => $deadline ?? now()->addDay(),
        ], $overrides));
    }

    private function grantAssignmentPermission(UserModel $photographer, StudiosModel $studio): void
    {
        $permission = PermissionModel::create([
            'name' => 'studio-photographer.assignment.update_status',
            'permission_string' => 'assignment:update_status',
            'portal' => 'studio-photographer',
            'status' => 'active',
        ]);

        $role = RoleModel::create([
            'name' => 'studio-photographer',
            'portal' => 'studio-photographer',
            'status' => 'active',
        ]);

        $role->permissions()->attach($permission->id);
        $photographer->roles()->attach($role->id, ['studio_id' => $studio->id]);
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
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name');
            $table->date('event_date');
            $table->string('location_type')->default('in-studio');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->text('cancellation_reason')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('studio_id');
            $table->foreignId('photographer_id');
            $table->foreignId('assigned_by');
            $table->string('status')->default('assigned');
            $table->text('assignment_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('response_deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('on_site_at')->nullable();
            $table->timestamp('client_confirmed_at')->nullable();
            $table->text('client_confirmation_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('icon')->default('bell');
            $table->string('color')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_type');
            $table->string('package_name');
            $table->decimal('package_price', 10, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->string('package_name');
            $table->decimal('package_price', 10, 2);
            $table->boolean('online_gallery')->default(false);
            $table->integer('photographer_count')->default(0);
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('studio_id');
            $table->json('images')->nullable();
            $table->integer('total_photos')->default(0);
            $table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal')->nullable();
            $table->string('status');
            $table->boolean('is_system')->default(false);
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal')->nullable();
            $table->string('permission_string')->nullable();
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });
        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->timestamps();
        });
    }
}
