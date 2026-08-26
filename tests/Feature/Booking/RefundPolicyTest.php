<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Services\BookingCancellationRecoveryService;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class RefundPolicyTest extends TestCase
{
    public function test_downpayment_customer_cancellation_has_no_refund_recovery_policy(): void
    {
        $booking = new BookingModel(['booking_type' => 'studio', 'cancelled_by' => 'client', 'payment_type' => 'downpayment']);
        $this->assertSame(100.0, $this->refundPercentage($booking));
        $this->assertFalse($booking->payment_type === 'full_payment');
    }

    public function test_full_payment_customer_cancellation_targets_seventy_percent_by_default(): void
    {
        $booking = new BookingModel(['booking_type' => 'studio', 'cancelled_by' => 'client', 'payment_type' => 'full_payment']);
        $booking->setRelation('studio', new StudiosModel(['downpayment_percentage' => 30]));
        $this->assertSame(70.0, $this->refundPercentage($booking));
    }

    public function test_studio_cancellation_targets_full_provider_refund(): void
    {
        $booking = new BookingModel(['booking_type' => 'studio', 'cancelled_by' => 'studio', 'payment_type' => 'full_payment']);
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
