<?php

namespace Tests\Feature;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    private UserModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');
        $this->owner = $this->createOwner('owner-super-admin');
        Auth::setUser($this->owner);

        Route::post('/_test/subscription/manage/{studioId?}', fn () => response()->json(['ok' => true]))
            ->middleware('subscription.access:manage');
        Route::post('/_test/subscription/booking/{id}', fn () => response()->json(['ok' => true]))
            ->middleware('subscription.access:fulfill-paid-booking');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_marketplace_scope_returns_only_studios_with_current_access(): void
    {
        $active = $this->createStudio('Active Studio');
        $grace = $this->createStudio('Grace Studio');
        $expired = $this->createStudio('Expired Studio');
        $this->createStudio('Never Subscribed');
        $this->createSubscription($active, ['end_date' => '2026-08-03']);
        $this->createSubscription($grace, [
            'status' => 'grace',
            'end_date' => '2026-08-01',
            'grace_ends_at' => '2026-08-05 00:00:00',
        ]);
        $this->createSubscription($expired, [
            'status' => 'expired',
            'end_date' => '2026-07-01',
            'grace_ends_at' => '2026-07-09 00:00:00',
        ]);

        $ids = StudiosModel::subscriptionAccessible()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$active->id, $grace->id], $ids);
    }

    public function test_manage_access_is_per_studio_and_owner_super_admin_is_not_exempt(): void
    {
        $active = $this->createStudio('Active Studio');
        $expired = $this->createStudio('Expired Studio');
        $this->createSubscription($active);
        $this->createSubscription($expired, [
            'status' => 'expired',
            'end_date' => '2026-07-01',
            'grace_ends_at' => '2026-07-09 00:00:00',
        ]);

        $this->postJson("/_test/subscription/manage/{$active->id}")->assertOk();
        $this->postJson("/_test/subscription/manage/{$expired->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_manage_access_fails_closed_when_studio_cannot_be_resolved(): void
    {
        $this->createStudio('First Studio');
        $this->createStudio('Second Studio');

        $this->postJson('/_test/subscription/manage')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_expired_studio_can_fulfill_a_paid_booking_but_not_an_unpaid_booking(): void
    {
        $studio = $this->createStudio('Expired Studio');
        $paid = $this->createBooking($studio, 'paid');
        $unpaid = $this->createBooking($studio, 'unpaid');
        PaymentModel::create([
            'booking_id' => $paid->id,
            'payment_reference' => 'PAY-SUCCEEDED',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        $this->postJson("/_test/subscription/booking/{$paid->id}")->assertOk();
        $this->postJson("/_test/subscription/booking/{$unpaid->id}")->assertForbidden();
    }

    private function createOwner(string $role): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Access',
            'last_name' => 'Owner',
            'email' => 'access-owner@example.com',
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(string $name): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => $name,
            'status' => 'verified',
        ]);
    }

    private function createSubscription(StudiosModel $studio, array $overrides = []): StudioPlanModel
    {
        $plan = SubscriptionPlanModel::firstOrCreate(['plan_code' => 'STU_BASIC_MON'], [
            'user_type' => 'studio',
            'plan_type' => 'basic',
            'billing_cycle' => 'monthly',
            'name' => 'Basic',
            'price' => 590,
            'commission_rate' => 5,
            'trial_days' => 0,
            'status' => 'active',
        ]);

        return StudioPlanModel::create(array_merge([
            'studio_id' => $studio->id,
            'plan_id' => $plan->id,
            'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'next_billing_date' => '2026-09-01',
            'amount_paid' => 590,
            'payment_status' => 'paid',
            'status' => 'active',
        ], $overrides));
    }

    private function createBooking(StudiosModel $studio, string $paymentStatus): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $this->owner->id,
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'event_name' => 'Test Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => 'confirmed',
            'payment_status' => $paymentStatus,
        ]);
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
            
            $table->softDeletes();$table->timestamps();
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
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
}
