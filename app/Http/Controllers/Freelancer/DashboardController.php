<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $bookings = BookingModel::where('booking_type', 'freelancer')
            ->where('provider_id', $request->user()->id);
        $payments = PaymentModel::where('status', 'succeeded')
            ->whereHas('booking', fn ($query) => $query->where('booking_type', 'freelancer')
                ->where('provider_id', $request->user()->id))
            ->get(['amount', 'refunded_amount']);

        $summary = [
            'payments_received' => (float) $payments->sum(fn ($payment) => max(0, (float) $payment->amount - (float) $payment->refunded_amount)),
            'bookings' => (clone $bookings)->count(),
            'clients' => (clone $bookings)->distinct()->count('client_id'),
            'refunds_pending' => (clone $bookings)->where('payment_status', BookingModel::PAYMENT_REFUND_PENDING)->count(),
        ];

        return view('freelancer.dashboard', compact('summary'));
    }
}
