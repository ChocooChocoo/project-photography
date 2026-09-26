<?php

namespace Tests\Feature\Client;

use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Services\BookingCancellationRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class CancellationRecoveryDeadlineTest extends TestCase
{
    private string $stubViewPath;

    /** @var string[] */
    private array $originalViewPaths;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();

        $this->installLayoutStubs();
    }

    protected function tearDown(): void
    {
        View::getFinder()->setPaths($this->originalViewPaths);
        View::flushFinderCache();
        File::deleteDirectory($this->stubViewPath);

        parent::tearDown();
    }

    public function test_newly_created_client_recovery_has_a_non_null_deadline(): void
    {
        $booking = $this->createBooking(
            BookingModel::STATUS_CONFIRMED,
            BookingModel::PAYMENT_PAID,
            now()->addDays(3)->toDateString()
        );

        PaymentModel::create([
            'booking_id' => $booking->id,
            'payment_reference' => 'PAY-DEADLINE-1',
            'amount' => 1000,
            'payment_method' => 'card',
            'status' => 'succeeded',
        ]);

        $recovery = app(BookingCancellationRecoveryService::class)
            ->clientCancelled($booking, 'The client changed plans and cancelled the event.');

        $this->assertNotNull($recovery);
        $this->assertNotNull($recovery->deadline);
        $this->assertTrue($recovery->deadline->isFuture());
    }

    public function test_client_recovery_view_renders_when_deadline_is_null(): void
    {
        $recovery = $this->legacyRecovery();

        $html = view('client.cancellation-recovery', compact('recovery'))->render();

        $this->assertStringContainsString('Not set', $html);
    }

    public function test_owner_recovery_view_renders_when_deadline_is_null(): void
    {
        $recovery = $this->legacyRecovery();

        $html = view('owner.cancellation-recovery', [
            'recovery' => $recovery,
            'canSetRefundTarget' => false,
            'paidTotal' => 0,
            'replacementMembers' => collect(),
            'replacementCandidates' => collect(),
        ])->render();

        $this->assertStringContainsString('Not set', $html);
    }

    /**
     * A production row that predates the deadline column.
     */
    private function legacyRecovery(): BookingCancellationRecoveryModel
    {
        $booking = new BookingModel(['booking_reference' => 'BK-LEGACY-DEADLINE']);

        $recovery = new BookingCancellationRecoveryModel([
            'status' => BookingCancellationRecoveryService::STATUS_REFUND_PENDING,
            'deadline' => null,
        ]);
        $recovery->setRelation('booking', $booking);

        return $recovery;
    }

    private function createBooking(string $status, string $paymentStatus, string $eventDate): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => 1,
            'booking_type' => 'studio',
            'provider_id' => 1,
            'event_name' => 'Deadline Event',
            'event_date' => $eventDate,
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'location_type' => 'in-studio',
            'total_amount' => 1000,
            'down_payment' => 0,
            'remaining_balance' => 0,
            'payment_type' => 'full_payment',
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function installLayoutStubs(): void
    {
        $this->stubViewPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'capsapp-recovery-views-'.uniqid();

        File::ensureDirectoryExists($this->stubViewPath.'/layouts/client');
        File::ensureDirectoryExists($this->stubViewPath.'/layouts/owner');

        $stub = "<!DOCTYPE html><html><body>@yield('content')@yield('scripts')</body></html>";
        File::put($this->stubViewPath.'/layouts/client/app.blade.php', $stub);
        File::put($this->stubViewPath.'/layouts/owner/app.blade.php', $stub);

        $this->originalViewPaths = View::getFinder()->getPaths();
        View::prependLocation($this->stubViewPath);
        View::flushFinderCache();
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
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location_type');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
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
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique();
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedBigInteger('original_assignment_id')->nullable();
            $table->unsignedBigInteger('replacement_assignment_id')->nullable();
            $table->string('status');
            $table->timestamp('deadline')->nullable();
            $table->timestamp('replacement_proposed_at')->nullable();
            $table->timestamp('replacement_confirmed_at')->nullable();
            $table->timestamp('client_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('outcome_reason')->nullable();
            $table->text('photographer_reason')->nullable();
            $table->timestamps();
        });
    }
}
