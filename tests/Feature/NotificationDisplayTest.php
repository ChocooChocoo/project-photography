<?php

namespace Tests\Feature;

use App\Models\NotificationModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_unread_count_endpoint_returns_only_unread(): void
    {
        $user = $this->createUser('client');
        $this->createNotification($user, ['read_at' => now()]);
        $this->createNotification($user);
        $this->createNotification($user);

        $this->actingAs($user)
            ->getJson('/notifications/unread-count')
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);
    }

    public function test_recent_endpoint_returns_latest_five_with_unread_count(): void
    {
        $user = $this->createUser('client');

        foreach (range(1, 6) as $i) {
            $this->createNotification($user, [
                'title' => "Notification {$i}",
                'created_at' => now()->addMinutes($i),
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson('/notifications/recent')
            ->assertOk()
            ->assertJson(['success' => true, 'unread_count' => 6]);

        $titles = collect($response->json('notifications'))->pluck('title')->all();
        $this->assertCount(5, $titles);
        $this->assertSame('Notification 6', $titles[0]);
        $this->assertSame('Notification 2', $titles[4]);
    }

    public function test_mark_as_read_updates_read_at_and_returns_updated_count(): void
    {
        $user = $this->createUser('client');
        $unread = $this->createNotification($user);
        $other = $this->createNotification($user);

        $this->actingAs($user)
            ->postJson("/notifications/{$unread->id}/read")
            ->assertOk()
            ->assertJson(['success' => true, 'unread_count' => 1]);

        $this->assertNotNull($unread->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_mark_all_as_read_clears_unread(): void
    {
        $user = $this->createUser('client');
        $this->createNotification($user);
        $this->createNotification($user);

        $this->actingAs($user)
            ->postJson('/notifications/read-all')
            ->assertOk()
            ->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertSame(0, NotificationModel::where('user_id', $user->id)->unread()->count());
    }

    public function test_destroy_deletes_notification(): void
    {
        $user = $this->createUser('client');
        $notification = $this->createNotification($user);

        $this->actingAs($user)
            ->deleteJson("/notifications/{$notification->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tbl_notifications', ['id' => $notification->id]);
    }

    public function test_index_renders_blade_page_with_pagination(): void
    {
        $user = $this->createUser('client');

        foreach (range(1, 12) as $i) {
            $this->createNotification($user, ['title' => "Paged Notification {$i}"]);
        }

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Paged Notification')
            ->assertSee('Showing')
            ->assertSee('Mark all read')
            ->assertSee('rel="next"', false);
    }

    public function test_index_still_serves_json_when_requested(): void
    {
        $user = $this->createUser('client');
        $this->createNotification($user, ['title' => 'JSON Notification']);

        $this->actingAs($user)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.data.0.title', 'JSON Notification');
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = $this->createUser('owner');
        $client = $this->createUser('client');
        $notification = $this->createNotification($owner);

        $this->actingAs($client)
            ->postJson("/notifications/{$notification->id}/read")
            ->assertStatus(404);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_guest_cannot_access_notification_endpoints(): void
    {
        $this->getJson('/notifications/unread-count')->assertStatus(401);
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    private function createUser(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Notice',
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
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $notification = NotificationModel::create(array_merge([
            'user_id' => $user->id,
            'type' => 'reminder',
            'title' => 'Test Notification',
            'message' => 'This is a test message.',
            'data' => null,
            'icon' => 'bell',
            'color' => 'warning',
        ], $overrides));

        if ($createdAt !== null) {
            $notification->created_at = $createdAt;
            $notification->save();
        }

        return $notification;
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
