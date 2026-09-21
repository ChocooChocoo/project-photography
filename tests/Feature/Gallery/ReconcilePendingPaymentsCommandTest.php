<?php

namespace Tests\Feature\Gallery;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReconcilePendingPaymentsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
    }

    public function test_settles_old_pending_booking_that_has_a_successful_payment(): void
    {
        $booking = $this->createBooking('pending', 'in_progress', 1000);
        $this->createPayment($booking, 1000, 'succeeded');
        $this->agePendingBooking($booking);

        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_PAID, $booking->payment_status);
    }

    public function test_marks_old_pending_booking_partially_paid_for_partial_payment(): void
    {
        $booking = $this->createBooking('pending', 'in_progress', 1000);
        $this->createPayment($booking, 400, 'succeeded');
        $this->agePendingBooking($booking);

        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_PARTIALLY_PAID, $booking->payment_status);
    }

    public function test_leaves_recent_pending_booking_untouched(): void
    {
        $booking = $this->createBooking('pending', 'in_progress', 1000);
        $this->createPayment($booking, 1000, 'succeeded');
        // updated_at stays "now", inside the two-minute grace window.

        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_PENDING, $booking->payment_status);
    }

    public function test_leaves_old_pending_booking_without_successful_payment(): void
    {
        $booking = $this->createBooking('pending', 'confirmed', 1000);
        $this->createPayment($booking, 1000, 'failed');
        $this->agePendingBooking($booking);

        $before = DB::table('tbl_bookings')->where('id', $booking->id)->value('updated_at');

        // Repeated runs must not rewrite a booking that has nothing to reconcile.
        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);
        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_PENDING, $booking->payment_status);
        $this->assertSame(
            (string) $before,
            (string) DB::table('tbl_bookings')->where('id', $booking->id)->value('updated_at')
        );
    }

    public function test_booking_with_no_payments_is_untouched_across_repeated_runs(): void
    {
        $booking = $this->createBooking('pending', 'confirmed', 1000);
        // No payment rows at all.
        $this->agePendingBooking($booking);

        $before = DB::table('tbl_bookings')->where('id', $booking->id)->value('updated_at');

        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);
        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_PENDING, $booking->payment_status);
        $this->assertSame(
            (string) $before,
            (string) DB::table('tbl_bookings')->where('id', $booking->id)->value('updated_at')
        );
    }

    public function test_skips_cancelled_bookings(): void
    {
        $booking = $this->createBooking('pending', 'cancelled', 1000);
        $this->createPayment($booking, 1000, 'succeeded');
        $this->agePendingBooking($booking);

        $this->artisan('bookings:reconcile-payments')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(BookingModel::PAYMENT_PENDING, $booking->payment_status);
    }

    public function test_command_is_scheduled_every_five_minutes_in_production(): void
    {
        app(ConsoleKernel::class)->bootstrap();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($scheduled) => str_contains($scheduled->command, 'bookings:reconcile-payments'));

        $this->assertNotNull($event, 'bookings:reconcile-payments must be scheduled');
        $this->assertSame('*/5 * * * *', $event->expression);
        $this->assertSame(['production'], $event->environments);
        $this->assertTrue($event->withoutOverlapping);
    }

    private function createBooking(string $paymentStatus, string $status, float $total): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => 1,
            'booking_type' => 'studio',
            'provider_id' => 1,
            'event_name' => 'Reconcile Event',
            'event_date' => '2026-08-10',
            'total_amount' => $total,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function createPayment(BookingModel $booking, float $amount, string $status): PaymentModel
    {
        return PaymentModel::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => $status,
            'paid_at' => $status === 'succeeded' ? now() : null,
        ]);
    }

    private function agePendingBooking(BookingModel $booking): void
    {
        DB::table('tbl_bookings')
            ->where('id', $booking->id)
            ->update(['updated_at' => now()->subMinutes(5)]);
    }

    private function createSchema(): void
    {
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
            $table->unsignedBigInteger('booking_id');
            $table->string('payment_reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
}
