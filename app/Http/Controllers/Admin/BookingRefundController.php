<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Services\BookingCancellationRecoveryService;
use Illuminate\Http\Request;

class BookingRefundController extends Controller
{
    public function view()
    {
        return view('admin.booking-refunds');
    }

    public function index()
    {
        $recoveries = BookingCancellationRecoveryModel::with(['booking.client', 'booking.payments'])
            ->where('status', BookingCancellationRecoveryService::STATUS_REFUND_PENDING)
            ->latest()->get();

        $recoveries->each(function (BookingCancellationRecoveryModel $recovery) {
            $booking = $recovery->booking;
            $paid = $booking ? round((float) $booking->payments->where('status', 'succeeded')->sum('amount'), 2) : 0.0;
            $byClient = $booking && $booking->cancelled_by === 'client';

            $recovery->setAttribute('cancelled_by', $booking?->cancelled_by);
            $recovery->setAttribute('paid_total', $paid);
            $recovery->setAttribute('max_refund', $byClient ? $booking->maxClientRefund() : $paid);
            $recovery->setAttribute('non_refundable_downpayment', $byClient ? $booking->nonRefundableDownpayment() : 0.0);
            $recovery->setAttribute('refund_editable', $byClient);
        });

        return response()->json(['recoveries' => $recoveries->makeHidden('photographer_reason')]);
    }

    public function complete(Request $request, $recoveryId)
    {
        $data = $request->validate([
            'provider_refund_reference' => 'nullable|string|max:255|required_without:provider_refund_references',
            'provider_refund_references' => 'nullable|array|required_without:provider_refund_reference',
            'provider_refund_references.*' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $recovery = BookingCancellationRecoveryModel::findOrFail($recoveryId);

        // The stored refund_amount is the owner's target and the default the
        // queue executes. A business-side cancellation is always refunded in
        // full; for a client cancellation the admin may adjust the target, but
        // never into the non-refundable downpayment.
        if (array_key_exists('refund_amount', $data) && $data['refund_amount'] !== null
            && $recovery->status === BookingCancellationRecoveryService::STATUS_REFUND_PENDING) {
            $booking = BookingModel::findOrFail($recovery->booking_id);

            if ($booking->cancelled_by !== 'client') {
                return response()->json([
                    'success' => false,
                    'message' => 'Business-initiated cancellations are refunded in full and cannot be changed.',
                ], 422);
            }

            $max = $booking->maxClientRefund();
            if ((float) $data['refund_amount'] > $max + 0.001) {
                return response()->json([
                    'success' => false,
                    'message' => 'The ₱'.number_format($booking->nonRefundableDownpayment(), 2)
                        .' downpayment is non-refundable. The refund can be at most ₱'.number_format($max, 2).'.',
                ], 422);
            }

            app(BookingCancellationRecoveryService::class)->setRefundTarget($recovery, (float) $data['refund_amount']);
            $recovery->refresh();
        }

        $completed = app(BookingCancellationRecoveryService::class)->completeRefund(
            $recovery,
            $data['provider_refund_references'] ?? $data['provider_refund_reference'],
            $data['notes'] ?? null
        );

        return response()->json([
            'success' => $completed,
            'message' => $completed ? 'Refund evidence recorded.' : 'Refund evidence is invalid or already used.',
            'recovery' => $recovery->fresh(),
        ], $completed ? 200 : 422);
    }
}
