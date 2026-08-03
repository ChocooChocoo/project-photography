<?php

namespace Tests\Feature;

use App\Mail\SubscriptionLifecycleMail;
use App\Models\NotificationModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class SubscriptionNotificationTest extends TestCase
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

    public function test_lifecycle_command_sends_each_milestone_once_per_subscription(): void
    {
        Mail::fake();
        [$owner, $subscription] = $this->createOwnerSubscription([
            'trial_ends_at' => '2026-08-10 10:30:00',
            'end_date' => '2026-08-10',
            'next_billing_date' => '2026-08-10',
        ]);

        $this->artisan('subscriptions:notify-lifecycle')
            ->expectsOutput('Sent 1 lifecycle notification(s).')
            ->assertSuccessful();
        $this->artisan('subscriptions:notify-lifecycle')
            ->expectsOutput('Sent 0 lifecycle notification(s).')
            ->assertSuccessful();

        $notification = NotificationModel::sole();
        $this->assertSame('subscription_ending', $notification->type);
        $this->assertSame($subscription->id, $notification->data['subscription_id']);
        $this->assertSame('ending_7', $notification->data['event']);
        Mail::assertSent(SubscriptionLifecycleMail::class, 1);
        Mail::assertSent(SubscriptionLifecycleMail::class, fn ($mail) => $mail->hasTo($owner->email));
    }

    public function test_email_failure_does_not_remove_the_in_app_notice_or_fail_the_command(): void
    {
        [, $subscription] = $this->createOwnerSubscription([
            'status' => 'grace',
            'trial_ends_at' => null,
            'end_date' => '2026-07-27',
            'next_billing_date' => '2026-07-27',
            'grace_ends_at' => '2026-08-06 00:00:00',
        ]);
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP offline'));

        $this->artisan('subscriptions:notify-lifecycle')
            ->expectsOutput('Sent 1 lifecycle notification(s).')
            ->assertSuccessful();

        $notification = NotificationModel::sole();
        $this->assertSame($subscription->id, $notification->data['subscription_id']);
        $this->assertSame('grace_3', $notification->data['event']);
    }

    public function test_hourly_lifecycle_transitions_send_grace_and_expiry_events(): void
    {
        Mail::fake();
        [, $subscription] = $this->createOwnerSubscription([
            'trial_ends_at' => '2026-08-03 09:00:00',
            'end_date' => '2026-08-03',
            'next_billing_date' => '2026-08-03',
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();
        $this->assertSame('grace_entered', NotificationModel::sole()->data['event']);

        Carbon::setTestNow('2026-08-10 09:00:00');
        $this->artisan('subscriptions:expire')->assertSuccessful();

        $events = NotificationModel::orderBy('id')->get()->pluck('data')->pluck('event')->all();
        $this->assertSame(['grace_entered', 'expired'], $events);
        $this->assertSame('expired', $subscription->fresh()->status);
        Mail::assertSent(SubscriptionLifecycleMail::class, 2);
    }

    private function createOwnerSubscription(array $overrides): array
    {
        $owner = UserModel::create([
            'role' => 'owner',
            'user_type' => 'photographer',
            'first_name' => 'Billing',
            'last_name' => 'Owner',
            'email' => 'billing-owner@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Billing Studio',
            'status' => 'verified',
        ]);
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio',
            'plan_type' => 'premium',
            'billing_cycle' => 'monthly',
            'plan_code' => 'STU_PREMIUM_MON',
            'name' => 'Premium',
            'price' => 1999,
            'commission_rate' => 5,
            'trial_days' => 0,
            'status' => 'active',
        ]);
        $subscription = StudioPlanModel::create(array_merge([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-NOTIFICATION',
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'next_billing_date' => '2026-09-01',
            'amount_paid' => 0,
            'payment_status' => 'paid',
            'status' => 'active',
        ], $overrides));

        return [$owner, $subscription];
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
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->string('plan_type');
            $table->string('billing_cycle');
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_studio_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('plan_id');
            $table->string('subscription_reference')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_status');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
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
