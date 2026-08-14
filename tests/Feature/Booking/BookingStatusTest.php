<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingStatusTest extends TestCase
{
    private const EXPIRED_REASON = 'Booking expired — not confirmed within the required timeframe.';

    private const STATUSES = [
        BookingModel::STATUS_PENDING,
        BookingModel::STATUS_CONFIRMED,
        BookingModel::STATUS_IN_PROGRESS,
        BookingModel::STATUS_COMPLETED,
        BookingModel::STATUS_CANCELLED,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_can_transition_to_matrix(): void
    {
        $client = $this->createUser('client', 'matrix-client@example.com');

        $bookings = [
            BookingModel::STATUS_PENDING => $this->createBooking($client, ['status' => BookingModel::STATUS_PENDING]),
            BookingModel::STATUS_CONFIRMED => $this->createBooking($client, ['status' => BookingModel::STATUS_CONFIRMED]),
            BookingModel::STATUS_IN_PROGRESS => $this->createBooking($client, [
                'status' => BookingModel::STATUS_IN_PROGRESS,
                'payment_status' => BookingModel::PAYMENT_PAID,
            ]),
            BookingModel::STATUS_COMPLETED => $this->createBooking($client, ['status' => BookingModel::STATUS_COMPLETED]),
            BookingModel::STATUS_CANCELLED => $this->createBooking($client, ['status' => BookingModel::STATUS_CANCELLED]),
        ];

        PaymentModel::create([
            'booking_id' => $bookings[BookingModel::STATUS_IN_PROGRESS]->id,
            'payment_reference' => 'PAY-MATRIX-PAID',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        $matrix = [
            BookingModel::STATUS_PENDING => [
                BookingModel::STATUS_CONFIRMED => true,
                BookingModel::STATUS_CANCELLED => true,
                BookingModel::STATUS_PENDING => false,
                BookingModel::STATUS_IN_PROGRESS => false,
                BookingModel::STATUS_COMPLETED => false,
            ],
            BookingModel::STATUS_CONFIRMED => [
                BookingModel::STATUS_IN_PROGRESS => true,
                BookingModel::STATUS_CANCELLED => true,
                BookingModel::STATUS_PENDING => false,
                BookingModel::STATUS_CONFIRMED => false,
                BookingModel::STATUS_COMPLETED => false,
            ],
            BookingModel::STATUS_IN_PROGRESS => [
                BookingModel::STATUS_COMPLETED => true,
                BookingModel::STATUS_CANCELLED => true,
                BookingModel::STATUS_PENDING => false,
                BookingModel::STATUS_CONFIRMED => false,
                BookingModel::STATUS_IN_PROGRESS => false,
            ],
            BookingModel::STATUS_COMPLETED => [
                BookingModel::STATUS_COMPLETED => false,
                BookingModel::STATUS_CANCELLED => false,
                BookingModel::STATUS_PENDING => false,
                BookingModel::STATUS_CONFIRMED => false,
                BookingModel::STATUS_IN_PROGRESS => false,
            ],
            BookingModel::STATUS_CANCELLED => [
                BookingModel::STATUS_CANCELLED => false,
                BookingModel::STATUS_CONFIRMED => false,
                BookingModel::STATUS_PENDING => false,
                BookingModel::STATUS_IN_PROGRESS => false,
                BookingModel::STATUS_COMPLETED => false,
            ],
        ];

        foreach ($matrix as $from => $expected) {
            foreach (self::STATUSES as $to) {
                $this->assertSame(
                    $expected[$to],
                    $bookings[$from]->canTransitionTo($to),
                    "{$from} -> {$to}"
                );
            }
        }

        // Completion guard: an in-progress booking that is not fully paid cannot complete.
        $unpaid = $this->createBooking($client, ['status' => BookingModel::STATUS_IN_PROGRESS]);
        $this->assertFalse($unpaid->canTransitionTo(BookingModel::STATUS_COMPLETED));
        $this->assertTrue($unpaid->canTransitionTo(BookingModel::STATUS_CANCELLED));
    }

    public function test_expire_pending_bookings_command(): void
    {
        Carbon::setTestNow('2026-08-03 10:30:00');

        $client = $this->createUser('client', 'expire-client@example.com');
        $owner = $this->createUser('owner', 'expire-owner@example.com');
        $freelancer = $this->createUser('freelancer', 'expire-freelancer@example.com');

        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Expire Studio',
            'status' => 'verified',
        ]);

        ProfileModel::create([
            'user_id' => $freelancer->id,
            'brand_name' => 'Expire Lens',
        ]);

        $studioBooking = $this->createBooking($client, [
            'booking_type' => 'studio',
            'provider_id' => $studio->id,
            'status' => BookingModel::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        $freelancerBooking = $this->createBooking($client, [
            'booking_type' => 'freelancer',
            'provider_id' => $freelancer->id,
            'status' => BookingModel::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        $futureBooking = $this->createBooking($client, [
            'status' => BookingModel::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $confirmedBooking = $this->createBooking($client, [
            'status' => BookingModel::STATUS_CONFIRMED,
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('bookings:expire-pending')->assertSuccessful();

        foreach ([$studioBooking, $freelancerBooking] as $booking) {
            $booking->refresh();
            $this->assertSame(BookingModel::STATUS_CANCELLED, $booking->status);
            $this->assertSame('system', $booking->cancelled_by);
            $this->assertSame(self::EXPIRED_REASON, $booking->cancellation_reason);
        }

        $futureBooking->refresh();
        $this->assertSame(BookingModel::STATUS_PENDING, $futureBooking->status);
        $this->assertNull($futureBooking->cancelled_by);

        $confirmedBooking->refresh();
        $this->assertSame(BookingModel::STATUS_CONFIRMED, $confirmedBooking->status);
        $this->assertNull($confirmedBooking->cancelled_by);

        $expiredNotifications = NotificationModel::where('type', 'booking_expired')->get();
        $this->assertCount(4, $expiredNotifications);
        $this->assertEqualsCanonicalizing(
            [$client->id, $client->id, $owner->id, $freelancer->id],
            $expiredNotifications->pluck('user_id')->all()
        );

        $clientNotifications = $expiredNotifications->where('user_id', $client->id);
        $this->assertCount(2, $clientNotifications);
        $this->assertEqualsCanonicalizing(
            [$studioBooking->id, $freelancerBooking->id],
            $clientNotifications->pluck('data.booking_id')->all()
        );
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Booking',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000004',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createBooking(UserModel $client, array $overrides = []): BookingModel
    {
        return BookingModel::create(array_merge([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $client->id,
            'booking_type' => 'studio',
            'provider_id' => 1,
            'event_name' => 'Test Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => BookingModel::STATUS_PENDING,
            'payment_status' => BookingModel::PAYMENT_PENDING,
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
            $table->softDeletes();
            $table->timestamps();
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
    }
}
