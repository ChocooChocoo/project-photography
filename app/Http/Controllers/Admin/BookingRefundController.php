<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\PaymentModel;
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
        // queue executes. An admin may override it, but never above the total paid.
        if (array_key_exists('refund_amount', $data) && $data['refund_amount'] !== null
            && $recovery->status === BookingCancellationRecoveryService::STATUS_REFUND_PENDING) {
            $paidTotal = (float) PaymentModel::where('booking_id', $recovery->booking_id)
                ->where('status', 'succeeded')->sum('amount');

            if ((float) $data['refund_amount'] > $paidTotal) {
                return response()->json([
                    'success' => false,
                    'message' => 'The refund amount cannot exceed the total amount paid for this booking.',
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
