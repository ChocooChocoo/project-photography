<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Services\BookingCancellationRecoveryService;
use Tests\TestCase;

class RefundPolicyTest extends TestCase
{
    public function test_client_downpayment_cancellation_is_not_refundable_for_studio_bookings(): void
    {
        $booking = new BookingModel(['booking_type' => 'studio', 'cancelled_by' => 'client', 'payment_type' => 'downpayment']);

        // The down payment is not refundable, so no automatic cash refund target is set.
        $this->assertSame(0.0, $this->refundPercentage($booking));
    }

    public function test_client_downpayment_cancellation_is_not_refundable_for_freelancer_bookings(): void
    {
        $booking = new BookingModel(['booking_type' => 'freelancer', 'cancelled_by' => 'client', 'payment_type' => 'downpayment']);

        // The rule covers every booking type, including freelancer bookings.
        $this->assertSame(0.0, $this->refundPercentage($booking));
    }

    public function test_client_full_payment_cancellation_leaves_the_amount_to_owner_discretion(): void
    {
        $booking = new BookingModel(['booking_type' => 'studio', 'cancelled_by' => 'client', 'payment_type' => 'full_payment']);
        $booking->setRelation('studio', new StudiosModel(['downpayment_percentage' => 30]));

        // No automatic split is applied; the owner sets the refund target instead.
        $this->assertSame(0.0, $this->refundPercentage($booking));
    }

    public function test_studio_cancellation_targets_full_provider_refund(): void
    {
        $booking = new BookingModel(['booking_type' => 'studio', 'cancelled_by' => 'studio', 'payment_type' => 'full_payment']);
        $this->assertSame(100.0, $this->refundPercentage($booking));
    }

    public function test_freelancer_cancellation_by_business_targets_full_refund(): void
    {
        $booking = new BookingModel(['booking_type' => 'freelancer', 'cancelled_by' => 'studio', 'payment_type' => 'downpayment']);
        $this->assertSame(100.0, $this->refundPercentage($booking));
    }

    public function test_refund_audit_columns_support_idempotent_completion(): void
    {
        $recovery = new \App\Models\BookingCancellationRecoveryModel;
        $payment = new \App\Models\PaymentModel;
        $this->assertArrayHasKey('refund_percentage', $recovery->getCasts());
        $this->assertArrayHasKey('refund_amount', $recovery->getCasts());
        $this->assertArrayHasKey('refunded_amount', $payment->getCasts());
        $this->assertSame(BookingCancellationRecoveryService::STATUS_REFUNDED, 'refunded');
    }

    private function refundPercentage(BookingModel $booking): float
    {
        $method = new \ReflectionMethod(BookingCancellationRecoveryService::class, 'refundPercentage');
        $method->setAccessible(true);

        return (float) $method->invoke(app(BookingCancellationRecoveryService::class), $booking);
    }
}
