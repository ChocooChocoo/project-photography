<?php

namespace Tests\Feature\Chat;

use App\Models\BookingModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatTest extends TestCase
{
    private UserModel $client;

    private UserModel $owner;

    private BookingModel $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        Schema::create('tbl_users', function (Blueprint $t) {
            $t->id();
            $t->string('uuid')->nullable();
            $t->string('user_type')->nullable();
            $t->string('role');
            $t->string('status')->default('active');
            $t->string('first_name');
            $t->string('last_name');
            $t->string('email')->unique();
            $t->string('password');
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
            $t->unsignedBigInteger('client_id');
            $t->unsignedBigInteger('provider_id');
            $t->string('booking_type');
            $t->string('booking_reference');
            $t->string('status');
            $t->date('event_date');
            $t->date('event_end_date')->nullable();
            $t->time('start_time');
            $t->time('end_time');
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
            $t->boolean('is_freelancer')->default(false);
            $t->timestamps();
        });
        Schema::create('tbl_studio_photographers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('studio_id');
            $t->unsignedBigInteger('photographer_id');
            $t->string('status');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('portal');
            $t->string('status');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_user_roles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('studio_id')->nullable();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->dateTime('expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('status');
            $t->string('permission_string');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('tbl_role_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
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
        Schema::create('tbl_notifications', function (Blueprint $t) {
            $t->id();
            $t->string('uuid');
            $t->unsignedBigInteger('user_id');
            $t->string('type');
            $t->string('title');
            $t->text('message');
            $t->json('data')->nullable();
            $t->string('icon')->nullable();
            $t->string('color')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        $this->client = $this->user('client');
        $this->owner = $this->user('owner');
        $studio = DB::table('tbl_studios')->insertGetId(['user_id' => $this->owner->id, 'studio_name' => 'Chat studio', 'status' => 'verified']);
        $this->booking = BookingModel::create(['booking_reference' => 'CHAT-BOOKING', 'client_id' => $this->client->id, 'booking_type' => 'studio', 'provider_id' => $studio, 'status' => 'completed', 'event_date' => now()->addDays(2)->toDateString(), 'start_time' => '09:00:00', 'end_time' => '17:00:00']);
        (require database_path('migrations/2026_10_13_160000_add_user_chat.php'))->up();
    }

    private function user(string $role): UserModel
    {
        return UserModel::create(['role' => $role, 'status' => 'active', 'first_name' => 'Chat', 'last_name' => $role, 'email' => str()->uuid().'@chat.test', 'password' => 'Password_123']);
    }

    private function open(): int
    {
        return $this->actingAs($this->client)->postJson('/client/messages', ['recipient_id' => $this->owner->id, 'booking_id' => $this->booking->id])->assertSuccessful()->json('conversation.id');
    }

    public function test_allowed_users_exchange_messages_and_read_only_received_messages(): void
    {
        $id = $this->open();
        $sent = $this->postJson('/client/messages/'.$id.'/send', ['body' => '<img src=x onerror=alert(1)>'])->assertCreated()->json('message.id');
        $this->actingAs($this->owner)->getJson('/owner/messages/unread')->assertOk()->assertJsonPath('count', 1);
        $this->getJson('/owner/messages/'.$id.'/messages?after=0')->assertOk()->assertJsonPath('messages.0.body', '<img src=x onerror=alert(1)>');
        $reply = $this->postJson('/owner/messages/'.$id.'/send', ['body' => 'Reply'])->assertCreated()->json('message.id');
        $this->postJson('/owner/messages/'.$id.'/read', ['through' => $sent])->assertOk();
        $this->getJson('/owner/messages/unread')->assertJsonPath('count', 0);
        $this->assertDatabaseHas('tbl_messages', ['id' => $reply, 'read_at' => null]);
        $this->actingAs($this->client)->getJson('/client/messages/'.$id.'/messages?after='.$sent)->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('read_through', $sent);
        $this->getJson('/client/messages/unread')->assertJsonPath('count', 1);
    }

    public function test_disallowed_pairs_self_and_foreign_threads_are_denied(): void
    {
        $id = $this->open();
        $stranger = $this->user('client');
        $this->actingAs($stranger)->getJson('/client/messages/'.$id.'/messages')->assertForbidden();
        $this->postJson('/client/messages/'.$id.'/send', ['body' => 'Intrusion'])->assertForbidden();
        $this->postJson('/client/messages/'.$id.'/read', ['through' => 1])->assertForbidden();
        $this->postJson('/client/messages', ['recipient_id' => $this->owner->id, 'booking_id' => $this->booking->id])->assertForbidden();
        $this->postJson('/client/messages', ['recipient_id' => $stranger->id])->assertForbidden();
        $this->getJson('/client/messages/unread')->assertJsonPath('count', 0);
    }

    public function test_conversation_uniqueness_is_canonical_and_body_limits_are_enforced(): void
    {
        $id = $this->open();
        $this->actingAs($this->owner)->postJson('/owner/messages', ['recipient_id' => $this->client->id, 'booking_id' => $this->booking->id])->assertSuccessful()->assertJsonPath('conversation.id', $id);
        $this->assertDatabaseCount('tbl_conversations', 1);
        $this->postJson('/owner/messages/'.$id.'/send', ['body' => '   '])->assertUnprocessable();
        $this->postJson('/owner/messages/'.$id.'/send', ['body' => str_repeat('a', 4001)])->assertUnprocessable();
        $this->getJson('/owner/messages/'.$id.'/messages?after=-1')->assertUnprocessable();
    }

    public function test_internal_staff_chat_requires_current_shared_studio_affiliation(): void
    {
        $photographer = $this->user('studio-photographer');
        DB::table('tbl_studio_photographers')->insert(['studio_id' => $this->booking->provider_id, 'photographer_id' => $photographer->id, 'status' => 'active']);
        $id = $this->actingAs($this->owner)->postJson('/owner/messages', ['recipient_id' => $photographer->id])->assertSuccessful()->json('conversation.id');
        $this->postJson('/owner/messages', ['recipient_id' => $photographer->id])->assertSuccessful()->assertJsonPath('conversation.id', $id);
        DB::table('tbl_studio_photographers')->update(['status' => 'inactive']);
        $this->actingAs($photographer)->getJson('/studio-photographer/messages/'.$id.'/messages')->assertForbidden();
    }

    public function test_notifications_are_bounded_and_sender_cannot_spoof_identity(): void
    {
        $id = $this->open();
        foreach (['First', 'Second'] as $body) {
            $this->postJson('/client/messages/'.$id.'/send', ['body' => $body, 'sender_id' => $this->owner->id])->assertCreated()->assertJsonPath('message.sender_id', $this->client->id);
        }
        $this->assertDatabaseCount('tbl_notifications', 1);
        $notification = DB::table('tbl_notifications')->first();
        $this->assertSame('/owner/messages/'.$id, json_decode($notification->data, true)['route']);
    }

    public function test_chat_migration_rolls_back_without_removing_bookings(): void
    {
        (require database_path('migrations/2026_10_13_160000_add_user_chat.php'))->down();
        $this->assertFalse(Schema::hasTable('tbl_messages'));
        $this->assertDatabaseCount('tbl_bookings', 1);
    }

    public function test_fallback_chat_access_ends_but_client_keeps_existing_history(): void
    {
        $freelancer = $this->user('freelancer');
        $this->booking->update(['status' => 'in_progress']);
        DB::table('tbl_booking_assigned_photographers')->insert(['booking_id' => $this->booking->id, 'studio_id' => $this->booking->provider_id, 'photographer_id' => $freelancer->id, 'assigned_by' => $this->owner->id, 'status' => 'confirmed', 'is_freelancer' => true]);
        DB::table('tbl_studio_members')->insert(['studio_id' => $this->booking->provider_id, 'freelancer_id' => $freelancer->id, 'status' => 'approved']);
        $role = DB::table('tbl_roles')->insertGetId(['name' => 'studio-freelancer', 'portal' => 'freelancer', 'status' => 'active']);
        $permission = DB::table('tbl_permissions')->insertGetId(['name' => 'fallback-view', 'status' => 'active', 'permission_string' => 'freelancer.studio-assignments.view']);
        DB::table('tbl_role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
        DB::table('tbl_user_roles')->insert(['user_id' => $freelancer->id, 'role_id' => $role, 'studio_id' => $this->booking->provider_id, 'booking_id' => $this->booking->id, 'expires_at' => now()->addDays(3)]);
        $id = $this->actingAs($this->client)->postJson('/client/messages', ['recipient_id' => $freelancer->id, 'booking_id' => $this->booking->id])->assertCreated()->json('conversation.id');
        $this->actingAs($freelancer)->postJson('/freelancer/messages/'.$id.'/send', ['body' => 'Arrival confirmed.'])->assertCreated();
        $this->travelTo(app(\App\Services\FreelancerBookingAccess::class)->eventEnd($this->booking));
        $this->getJson('/freelancer/messages/'.$id.'/messages')->assertForbidden();
        $this->postJson('/freelancer/messages/'.$id.'/send', ['body' => 'Expired'])->assertForbidden();
        $this->actingAs($this->client)->getJson('/client/messages/'.$id.'/messages')->assertOk()->assertJsonPath('can_send', false);
        $this->postJson('/client/messages/'.$id.'/send', ['body' => 'Cannot send after expiry'])->assertForbidden();
    }

    public function test_read_cursor_does_not_mark_later_arrivals_read_and_sends_are_throttled(): void
    {
        $id = $this->open();
        $first = $this->postJson('/client/messages/'.$id.'/send', ['body' => 'First'])->json('message.id');
        $second = $this->postJson('/client/messages/'.$id.'/send', ['body' => 'Later arrival'])->json('message.id');
        $this->actingAs($this->owner)->postJson('/owner/messages/'.$id.'/read', ['through' => $first])->assertOk();
        $this->assertDatabaseHas('tbl_messages', ['id' => $second, 'read_at' => null]);
        $this->getJson('/owner/messages/unread')->assertJsonPath('count', 1);
        $this->actingAs($this->client);
        for ($i = 0; $i < 28; $i++) {
            $this->postJson('/client/messages/'.$id.'/send', ['body' => 'Rate test'])->assertCreated();
        }
        $this->postJson('/client/messages/'.$id.'/send', ['body' => 'Blocked'])->assertStatus(429);
    }

    public function test_direct_freelancer_and_assigned_photographer_pairs_work_without_cross_booking_leaks(): void
    {
        $photographer = $this->user('studio-photographer');
        DB::table('tbl_studio_photographers')->insert(['studio_id' => $this->booking->provider_id, 'photographer_id' => $photographer->id, 'status' => 'active']);
        DB::table('tbl_booking_assigned_photographers')->insert(['booking_id' => $this->booking->id, 'studio_id' => $this->booking->provider_id, 'photographer_id' => $photographer->id, 'assigned_by' => $this->owner->id, 'status' => 'completed']);
        $this->actingAs($this->client)->postJson('/client/messages', ['recipient_id' => $photographer->id, 'booking_id' => $this->booking->id])->assertCreated();
        $freelancer = $this->user('freelancer');
        $direct = $this->booking->replicate();
        $direct->booking_type = 'freelancer';
        $direct->provider_id = $freelancer->id;
        $direct->save();
        $this->postJson('/client/messages', ['recipient_id' => $freelancer->id, 'booking_id' => $direct->id])->assertCreated();
        $this->postJson('/client/messages', ['recipient_id' => $photographer->id, 'booking_id' => $direct->id])->assertForbidden();
        $this->actingAs($this->owner)->getJson('/client/messages/unread')->assertForbidden();
    }

    public function test_portal_inbox_and_thread_render_with_booking_contacts(): void
    {
        $id = $this->open();
        $this->get('/client/messages?booking='.$this->booking->id)->assertOk()->assertSee('Messages')->assertSee('Chat owner');
        $this->get('/client/messages/'.$id)->assertOk()->assertSee('Message text');
        $this->actingAs($this->owner)->getJson('/owner/messages/inbox')->assertOk()->assertJsonPath('conversations.0.id', $id);
    }

    public function test_unread_badges_are_safe_before_chat_rollout(): void
    {
        (require database_path('migrations/2026_10_13_160000_add_user_chat.php'))->down();
        $this->actingAs($this->owner)->getJson('/owner/messages/unread')->assertOk()->assertJsonPath('count', 0);
    }

    public function test_hr_contact_requires_current_booking_permission_and_active_role(): void
    {
        $hr = $this->user('studio-hr');
        $role = DB::table('tbl_roles')->insertGetId(['name' => 'hr-chat', 'portal' => 'studio-hr', 'status' => 'active']);
        DB::table('tbl_user_roles')->insert(['user_id' => $hr->id, 'role_id' => $role, 'studio_id' => $this->booking->provider_id]);
        $this->actingAs($this->client)->postJson('/client/messages', ['recipient_id' => $hr->id, 'booking_id' => $this->booking->id])->assertForbidden();
        $permission = DB::table('tbl_permissions')->insertGetId(['name' => 'booking-access', 'status' => 'active', 'permission_string' => 'owner.bookings.manage']);
        DB::table('tbl_role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
        $id = $this->postJson('/client/messages', ['recipient_id' => $hr->id, 'booking_id' => $this->booking->id])->assertCreated()->json('conversation.id');
        DB::table('tbl_roles')->where('id', $role)->update(['status' => 'inactive']);
        $this->actingAs($hr)->getJson('/studio-hr/messages/'.$id.'/messages')->assertForbidden();
    }

    public function test_message_notification_escapes_sender_name_for_existing_notification_renderer(): void
    {
        $this->client->update(['first_name' => '<img src=x onerror=alert(1)>']);
        $id = $this->open();
        $this->postJson('/client/messages/'.$id.'/send', ['body' => 'Safe body'])->assertCreated();
        $notification = DB::table('tbl_notifications')->first();
        $this->assertStringNotContainsString('<img', $notification->message);
        $this->assertStringContainsString('&lt;img', $notification->message);
    }

    public function test_explicit_fresh_reset_orders_chat_children_before_booking_parents(): void
    {
        $tables = \Database\Seeders\Fresh\FreshResetSeeder::TRUNCATE_TABLES;
        $this->assertContains('tbl_messages', $tables);
        $this->assertContains('tbl_conversations', $tables);
        $this->assertLessThan(array_search('tbl_conversations', $tables), array_search('tbl_messages', $tables));
        $this->assertLessThan(array_search('tbl_bookings', $tables), array_search('tbl_conversations', $tables));
    }
}
