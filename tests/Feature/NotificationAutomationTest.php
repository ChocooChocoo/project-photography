<?php

namespace Tests\Feature;

use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\NotificationModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationAutomationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prune_command_deletes_only_old_read_notifications(): void
    {
        $user = $this->createUser('client', 'prune@example.com');

        $oldRead = $this->createNotification($user, [
            'read_at' => now()->subDays(31),
            'created_at' => now()->subDays(31),
        ]);
        $recentRead = $this->createNotification($user, ['read_at' => now()->subDays(5)]);
        $oldUnread = $this->createNotification($user, [
            'read_at' => null,
            'created_at' => now()->subDays(31),
        ]);
        $newest = $this->createNotification($user);

        $this->artisan('notifications:prune')
            ->expectsOutput('Pruned 1 read notification(s) older than 30 day(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('tbl_notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('tbl_notifications', ['id' => $recentRead->id]);
        $this->assertDatabaseHas('tbl_notifications', ['id' => $oldUnread->id]);
        $this->assertDatabaseHas('tbl_notifications', ['id' => $newest->id]);
    }

    public function test_prune_command_respects_custom_retention_days(): void
    {
        $user = $this->createUser('client', 'prune-custom@example.com');

        $oldRead = $this->createNotification($user, [
            'read_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);
        $recentRead = $this->createNotification($user, ['read_at' => now()->subDays(3)]);

        $this->artisan('notifications:prune', ['--days' => 7])->assertSuccessful();

        $this->assertDatabaseMissing('tbl_notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('tbl_notifications', ['id' => $recentRead->id]);
    }

    public function test_send_reminders_command_reminds_only_confirmed_bookings_soon(): void
    {
        $client = $this->createUser('client', 'remind-client@example.com');
        $owner = $this->createUser('owner', 'remind-owner@example.com');
        $freelancer = $this->createUser('freelancer', 'remind-freelancer@example.com');

        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Reminder Studio',
            'status' => 'verified',
        ]);

        ProfileModel::create([
            'user_id' => $freelancer->id,
            'brand_name' => 'Reminder Lens',
        ]);

        $tomorrowStudio = $this->createBooking($client, [
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'status' => BookingModel::STATUS_CONFIRMED,
            'event_date' => now()->addDay()->toDateString(),
        ]);
        $threeDaysFreelancer = $this->createBooking($client, [
            'booking_type' => 'freelancer',
            'provider_id' => $freelancer->id,
            'status' => BookingModel::STATUS_CONFIRMED,
            'event_date' => now()->addDays(3)->toDateString(),
        ]);
        $farFuture = $this->createBooking($client, [
            'status' => BookingModel::STATUS_CONFIRMED,
            'event_date' => now()->addDays(10)->toDateString(),
        ]);
        $pendingSoon = $this->createBooking($client, [
            'status' => BookingModel::STATUS_PENDING,
            'event_date' => now()->addDay()->toDateString(),
        ]);

        $this->artisan('bookings:send-reminders')
            ->expectsOutput('Sent 4 booking reminder(s).')
            ->assertSuccessful();

        $reminders = NotificationModel::where('type', 'reminder')->get();
        $this->assertCount(4, $reminders);

        $this->assertEqualsCanonicalizing(
            [$client->id, $client->id, $owner->id, $freelancer->id],
            $reminders->pluck('user_id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$tomorrowStudio->id, $threeDaysFreelancer->id],
            $reminders->pluck('data.booking_id')->unique()->values()->all()
        );
    }

    public function test_send_reminders_command_is_idempotent_per_day(): void
    {
        $client = $this->createUser('client', 'idem-client@example.com');

        $this->createBooking($client, [
            'status' => BookingModel::STATUS_CONFIRMED,
            'event_date' => now()->addDay()->toDateString(),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();
        $this->artisan('bookings:send-reminders')->expectsOutput('Sent 0 booking reminder(s).')->assertSuccessful();

        $this->assertCount(1, NotificationModel::where('type', 'reminder')->get());
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Automation',
            'last_name' => 'User',
            'email' => $email,
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
            'icon' => 'bell',
            'color' => 'warning',
        ], $overrides));

        if ($createdAt !== null) {
            $notification->created_at = $createdAt;
            $notification->save();
        }

        return $notification;
    }

    private function createBooking(UserModel $client, array $overrides = []): BookingModel
    {
        return BookingModel::create(array_merge([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => 1,
            'event_name' => 'Reminder Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_CONFIRMED,
            'payment_status' => BookingModel::PAYMENT_PAID,
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
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_freelancers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('brand_name')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name');
            $table->date('event_date');
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
