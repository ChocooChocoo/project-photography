<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;

class BookingPreparationController extends Controller
{
    public function show($booking)
    {
        $booking = BookingModel::where('client_id', auth()->id())->findOrFail($booking);
        abort_unless($booking->preparationVisible(), 403, 'Preparation is available after payment.');
        $booking->load(['events', 'equipment', 'packages', 'assignedPhotographers.photographer']);
        if ($booking->requiresContract()) {
            $booking->load('contract');
        }
        return view('client.partials.booking-preparation', compact('booking'));
    }
}
