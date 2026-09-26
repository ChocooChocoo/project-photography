<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Traits\Notifiable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_recent_payload_carries_the_stored_route(): void
    {
        $user = $this->createUser('client');
        $this->createNotification($user, [
            'title' => 'Routed Notification',
            'data' => ['booking_id' => 9, 'route' => '/view/my-bookings'],
        ]);

        $this->actingAs($user)
            ->getJson('/notifications/recent')
            ->assertOk()
            ->assertJsonPath('notifications.0.route', '/view/my-bookings')
            ->assertJsonPath('notifications.0.data.route', '/view/my-bookings');
    }

    public function test_missing_route_is_exposed_as_null(): void
    {
        $user = $this->createUser('client');
        $this->createNotification($user, [
            'title' => 'Plain Notification',
            'data' => ['booking_id' => 9],
        ]);

        $this->actingAs($user)
            ->getJson('/notifications/recent')
            ->assertOk()
            ->assertJsonPath('notifications.0.route', null);
    }

    public function test_missing_route_falls_back_to_index_route_by_type(): void
    {
        $client = $this->createUser('client');
        $owner = $this->createUser('owner');

        $creator = $this->notificationCreator();

        $budget = $creator->createNotification(
            $client->id,
            'budget_exceeded',
            'Budget Limit Reached',
            'Your budget reached its maximum.',
            ['budget_id' => 3],
            'wallet',
            'warning'
        );
        $this->assertSame(route('client.budget.index', [], false), $budget->data['route']);

        $revision = $creator->createNotification(
            $owner->id,
            'revision_requested',
            'Revision Requested',
            'The client requested a revision.',
            ['booking_id' => 12],
            'refresh',
            'warning'
        );
        $this->assertSame(route('owner.booking.index', [], false), $revision->data['route']);

        $review = $creator->createNotification(
            $owner->id,
            'review_received',
            'New Review Received',
            'You received a new 5-star review.',
            ['rating_id' => 4],
            'star',
            'info'
        );
        $this->assertSame(route('owner.profile', [], false), $review->data['route']);
    }

    public function test_explicit_route_is_not_overwritten_by_the_fallback(): void
    {
        $user = $this->createUser('owner');

        $notification = $this->notificationCreator()->createNotification(
            $user->id,
            'revision_requested',
            'Revision Requested',
            'The client requested a revision.',
            ['booking_id' => 12, 'route' => '/owner/bookings/12/details'],
            'refresh',
            'warning'
        );

        $this->assertSame('/owner/bookings/12/details', $notification->data['route']);
    }

    public function test_unknown_type_does_not_produce_a_target(): void
    {
        $user = $this->createUser('client');

        $notification = $this->notificationCreator()->createNotification(
            $user->id,
            'unmapped_event_type',
            'Something Happened',
            'No route is known for this type.',
            ['reference' => 'abc'],
            'bell',
            'secondary'
        );

        $this->assertArrayNotHasKey('route', $notification->data);

        $this->actingAs($user)
            ->getJson('/notifications/recent')
            ->assertOk()
            ->assertJsonPath('notifications.0.route', null);
    }

    public function test_list_rows_render_a_target(): void
    {
        $user = $this->createUser('client');
        $this->createNotification($user, [
            'title' => 'List Row',
            'data' => ['gallery_id' => 1, 'route' => '/view/online-gallery'],
        ]);

        $this->actingAs($user)
            ->getJson('/notifications/recent')
            ->assertOk()
            ->assertJsonPath('notifications.0.route', '/view/online-gallery');

        $this->actingAs($user)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.data.0.route', '/view/online-gallery');
    }

    private function notificationCreator(): object
    {
        return new class {
            use Notifiable;
        };
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Route',
            'last_name' => 'User',
            'email' => $role.'-'.uniqid().'@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createNotification(UserModel $user, array $overrides = []): NotificationModel
    {
        return NotificationModel::create(array_merge([
            'user_id' => $user->id,
            'type' => 'reminder',
            'title' => 'Test Notification',
            'message' => 'This is a test message.',
            'data' => null,
            'icon' => 'bell',
            'color' => 'warning',
        ], $overrides));
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
    }
}
