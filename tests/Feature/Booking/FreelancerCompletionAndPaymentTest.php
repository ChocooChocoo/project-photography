<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\HardcopyOrderModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tester report 2026-11-18: a freelancer marks a booking fully paid from any
 * state with a balance, and cannot complete it without gallery photos.
 */
class FreelancerCompletionAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $freelancer;

    private UserModel $client;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-18 09:00:00');
        Storage::fake('public');
        $this->freelancer = $this->user('freelancer', 'fc-freelancer@example.com');
        $this->client = $this->user('client', 'fc-client@example.com');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_freelancer_marks_unpaid_booking_fully_paid(): void
    {
        $booking = $this->booking('in_progress', 'pending');

        $this->actingAs($this->freelancer)
            ->putJson(route('freelancer.booking.update.payment.status', $booking->id), ['payment_status' => 'paid', 'notes' => 'Cash'])
            ->assertOk()->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertEquals(0, $booking->remaining_balance);
        $this->assertEquals(5000, PaymentModel::where('booking_id', $booking->id)->sum('amount'));
    }

    public function test_freelancer_cannot_mark_a_settled_booking_paid_again(): void
    {
        $booking = $this->booking('in_progress', 'pending');
        $this->pay($booking, 5000);

        $this->actingAs($this->freelancer)
            ->putJson(route('freelancer.booking.update.payment.status', $booking->id), ['payment_status' => 'paid'])
            ->assertStatus(400)->assertJsonPath('message', 'This booking is already fully paid.');
    }

    public function test_completion_needs_gallery_photos_uploaded_while_in_progress(): void
    {
        $booking = $this->booking('in_progress', 'pending');
        $this->pay($booking, 5000);
        $this->actingAs($this->freelancer);

        $this->putJson(route('freelancer.booking.update.status', $booking->id), ['status' => 'completed'])
            ->assertStatus(400)->assertJsonPath('success', false);
        $this->assertSame('in_progress', $booking->fresh()->status);

        $photo = UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWf8AAAAASUVORK5CYII='));
        $this->postJson(route('freelancer.online-gallery.upload', $booking->id), ['images' => [$photo]])->assertOk();

        $this->putJson(route('freelancer.booking.update.status', $booking->id), ['status' => 'completed'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_hardcopy_block_reason_names_the_event_end_before_the_event(): void
    {
        $booking = $this->booking('in_progress', 'pending');
        HardcopyOrderModel::create(['booking_id' => $booking->id, 'status' => 'pending', 'amount' => 500, 'fulfillment_method' => 'pickup', 'accepted_at' => now()]);

        $this->assertStringContainsString('opens after the event ends on Nov 21, 2026 1:00 PM', $booking->getDeliveryCompletionBlockReason());
    }

    private function pay(BookingModel $booking, float $amount): void
    {
        PaymentModel::create([
            'booking_id' => $booking->id, 'payment_reference' => PaymentModel::generatePaymentReference(),
            'amount' => $amount, 'payment_method' => 'manual', 'status' => 'succeeded', 'paid_at' => now(),
        ]);
        $booking->updatePaymentStatus();
    }

    private function user(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role, 'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Completion', 'last_name' => 'User', 'email' => $email,
            'mobile_number' => '09170000021', 'password' => 'secret', 'status' => 'active',
            'email_verified' => true, 'onboarding_completed_at' => now(),
        ]);
    }

    private function booking(string $status, string $paymentStatus): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'FC-'.str()->upper(str()->random(8)), 'client_id' => $this->client->id,
            'booking_type' => 'freelancer', 'provider_id' => $this->freelancer->id, 'event_name' => 'Portraits',
            'event_date' => '2026-11-21', 'start_time' => '08:00', 'end_time' => '13:00', 'location_type' => 'on-location',
            'total_amount' => 5000, 'down_payment' => 0, 'remaining_balance' => 5000, 'payment_type' => 'full_payment',
            'status' => $status, 'payment_status' => $paymentStatus,
        ]);
    }
}
