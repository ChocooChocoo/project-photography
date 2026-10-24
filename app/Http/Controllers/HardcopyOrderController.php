<?php

namespace App\Http\Controllers;

use App\Models\BookingModel;
use App\Models\HardcopyOrderModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Services\BookingEventRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HardcopyOrderController extends Controller
{
    private function ownerBooking($id): BookingModel
    {
        return BookingModel::where('booking_type', 'studio')
            ->whereIn('provider_id', \App\Models\StudioOwner\StudiosModel::where('user_id', auth()->id())->select('id'))->findOrFail($id);
    }

    private function clientBooking($id): BookingModel
    {
        return BookingModel::where('client_id', auth()->id())->findOrFail($id);
    }

    private function editable(BookingModel $booking): void
    {
        abort_unless(in_array($booking->status, ['confirmed', 'in_progress'], true), 422, 'Hardcopy changes require an open, confirmed booking.');
    }

    public function ownerShow($bookingId)
    {
        $booking = $this->ownerBooking($bookingId);
        $orders = HardcopyOrderModel::with('items')->where('booking_id', $booking->id)->get();

        return view('partials.hardcopy-orders', ['booking' => $booking, 'orders' => $orders, 'owner' => true]);
    }

    public function clientShow($bookingId)
    {
        $booking = $this->clientBooking($bookingId);
        $orders = HardcopyOrderModel::with('items')->where('booking_id', $booking->id)->get();

        return view('partials.hardcopy-orders', ['booking' => $booking, 'orders' => $orders, 'owner' => false]);
    }

    public function store(Request $request, $bookingId)
    {
        $booking = $this->ownerBooking($bookingId);
        $data = $request->validate([
            'fulfillment_method' => 'required|in:pickup,courier',
            'items' => 'required|array|min:1|max:20', 'items.*.name' => 'required|string|max:255',
            'items.*.size' => 'nullable|string|max:100', 'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.unit_price' => 'required|numeric|min:0|max:99999.99|decimal:0,2',
        ]);

        return DB::transaction(function () use ($booking, $data) {
            $booking = BookingModel::lockForUpdate()->findOrFail($booking->id);
            $this->editable($booking);
            abort_if(HardcopyOrderModel::where('booking_id', $booking->id)->whereNotIn('status', ['cancelled', 'delivered'])->exists(), 409, 'An active hardcopy order already exists.');
            $cents = collect($data['items'])->sum(fn ($item) => (int) round($item['unit_price'] * 100) * $item['quantity']);
            abort_if($cents > 100000000, 422, 'Hardcopy total cannot exceed PHP 1,000,000.');
            // The studio only suggests a method; the client's accepted choice is final.
            $order = HardcopyOrderModel::create(['booking_id' => $booking->id, 'fulfillment_method' => $data['fulfillment_method'], 'suggested_fulfillment_method' => $data['fulfillment_method'], 'amount' => $cents / 100, 'status' => 'pending']);
            $order->items()->createMany($data['items']);
            app(BookingEventRecorder::class)->record($booking->id, 'hardcopy_quoted', 'Hardcopy order quoted', 'hardcopy:'.$order->id.':quoted');

            return response()->json(['success' => true, 'order' => $order->load('items')], 201);
        });
    }

    public function accept(Request $request, $bookingId, $order)
    {
        $booking = $this->clientBooking($bookingId);
        $data = $request->validate([
            'fulfillment_method' => 'required|in:pickup,courier',
            'delivery_address' => 'required_if:fulfillment_method,courier|nullable|string|max:2000',
            'contact_number' => 'required_if:fulfillment_method,courier|nullable|string|max:40',
        ]);

        return DB::transaction(function () use ($booking, $order, $data) {
            $booking = BookingModel::lockForUpdate()->findOrFail($booking->id);
            $this->editable($booking);
            $order = HardcopyOrderModel::where('booking_id', $booking->id)->lockForUpdate()->findOrFail($order);
            if ($order->accepted_at) {
                return response()->json(['success' => true, 'order' => $order]);
            }
            abort_unless($order->status === 'pending', 422, 'This quote is no longer available.');
            $pending = $booking->payments()->where('status', 'pending')->lockForUpdate()->get();
            abort_if($pending->contains(fn ($payment) => ! empty($payment->stripe_session_id)), 409, 'Finish the existing checkout before accepting this order.');
            $order->update($data + ['accepted_at' => now()]);
            $booking->total_amount = (int) round($booking->total_amount * 100) / 100 + (float) $order->amount;
            $booking->save();
            $remaining = round($booking->total_amount - $booking->total_paid, 2);
            if ($remaining > 0) {
                $payment = $pending->first();
                if ($payment) {
                    $payment->update(['amount' => $remaining]);
                    foreach ($pending->slice(1) as $duplicate) {
                        $duplicate->update(['status' => 'failed']);
                    }
                } else {
                    PaymentModel::create(['booking_id' => $booking->id, 'payment_reference' => PaymentModel::generatePaymentReference(), 'amount' => $remaining, 'payment_method' => 'pending', 'status' => 'pending']);
                }
            }
            $booking->updatePaymentStatus();
            app(BookingEventRecorder::class)->record($booking->id, 'hardcopy_accepted', 'Hardcopy order accepted', 'hardcopy:'.$order->id.':accepted');

            return response()->json(['success' => true, 'order' => $order, 'remaining_balance' => $remaining]);
        });
    }

    public function status(Request $request, $bookingId, $order)
    {
        $booking = $this->ownerBooking($bookingId);
        $data = $request->validate(['status' => 'required|in:preparing,ready,shipped,delivered,cancelled', 'courier_name' => 'required_if:status,shipped|nullable|string|max:255', 'tracking_number' => 'required_if:status,shipped|nullable|string|max:255']);

        return DB::transaction(function () use ($booking, $order, $data) {
            $booking = BookingModel::lockForUpdate()->findOrFail($booking->id);
            $this->editable($booking);
            $order = HardcopyOrderModel::where('booking_id', $booking->id)->lockForUpdate()->findOrFail($order);
            $next = $data['status'];
            if ($next === $order->status) {
                return response()->json(['success' => true, 'order' => $order]);
            }
            if ($next === 'cancelled') {
                abort_if($order->accepted_at, 422, 'Accepted orders require the booking cancellation/refund workflow.');
                abort_unless($order->status === 'pending', 422, 'Only pending quotes can be cancelled here.');
            } else {
                abort_unless($order->accepted_at && $booking->isFullyPaid(), 422, 'Client acceptance and full payment are required.');
                abort_unless($booking->eventHasEnded(), 422, 'Printed photo delivery can start after the event ends on '.$booking->eventEndsAt()->format('M d, Y g:i A').'.');
                $transitions = ['pending' => 'preparing', 'preparing' => 'ready', 'ready' => $order->fulfillment_method === 'pickup' ? 'delivered' : 'shipped', 'shipped' => 'delivered'];
                abort_unless(($transitions[$order->status] ?? null) === $next, 422, 'Invalid hardcopy status transition.');
            }
            $order->update($data);
            app(BookingEventRecorder::class)->record($booking->id, 'hardcopy_'.$next, 'Hardcopy order '.$next, 'hardcopy:'.$order->id.':'.$next);
            if (in_array($next, ['ready', 'shipped', 'delivered'], true)) {
                NotificationModel::create(['user_id' => $booking->client_id, 'type' => 'hardcopy_'.$next, 'title' => 'Hardcopy order '.ucfirst($next), 'message' => 'Your hardcopy order for '.$booking->booking_reference.' is '.$next.'.', 'data' => ['booking_id' => $booking->id, 'order_id' => $order->id, 'route' => route('client.my-bookings.index', [], false)]]);
            }

            return response()->json(['success' => true, 'order' => $order]);
        });
    }
}
