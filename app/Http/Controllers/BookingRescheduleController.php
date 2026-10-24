<?php

namespace App\Http\Controllers;

use App\Models\BookingModel;
use App\Models\BookingRescheduleRequestModel;
use App\Models\StudioOwner\StudiosModel;
use App\Services\BookingRescheduleService;
use Illuminate\Http\Request;

/**
 * Client and provider (studio owner or freelancer) sides of a reschedule
 * request. The rules live in BookingRescheduleService.
 */
class BookingRescheduleController extends Controller
{
    public function __construct(private BookingRescheduleService $reschedules) {}

    public function clientShow($bookingId)
    {
        return $this->panel(BookingModel::where('client_id', auth()->id())->findOrFail($bookingId), 'client');
    }

    public function clientStore(Request $request, $bookingId)
    {
        $booking = BookingModel::where('client_id', auth()->id())->findOrFail($bookingId);
        $data = $request->validate([
            'requested_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:1000',
        ]);

        return $this->respond(fn () => $this->reschedules->request($booking, $request->user(), [
            'date' => $data['requested_date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
        ], $data['reason'] ?? null), 'Reschedule request sent.');
    }

    public function clientAccept(Request $request, $rescheduleId)
    {
        $reschedule = BookingRescheduleRequestModel::whereHas('booking', fn ($query) => $query->where('client_id', auth()->id()))->findOrFail($rescheduleId);
        $data = $request->validate(['alternative' => 'required|integer|min:0|max:2']);

        return $this->respond(fn () => $this->reschedules->acceptAlternative($reschedule, (int) $data['alternative']), 'Your booking has been moved.');
    }

    public function providerShow($bookingId)
    {
        return $this->panel($this->providerBookings()->findOrFail($bookingId), 'provider');
    }

    public function approve(Request $request, $rescheduleId)
    {
        return $this->respond(fn () => $this->reschedules->approve($this->providerRequest($rescheduleId), $request->user()), 'Reschedule approved. The booking has been moved.');
    }

    public function reject(Request $request, $rescheduleId)
    {
        $data = $request->validate(['response_note' => 'nullable|string|max:1000']);

        return $this->respond(fn () => $this->reschedules->reject($this->providerRequest($rescheduleId), $request->user(), $data['response_note'] ?? null), 'Reschedule declined.');
    }

    public function offer(Request $request, $rescheduleId)
    {
        // The form has three option rows; ignore the ones left empty.
        $request->merge(['alternatives' => array_values(array_filter((array) $request->input('alternatives', []), fn ($slot) => ! empty($slot['date'])))]);
        $data = $request->validate([
            'alternatives' => 'required|array|min:1|max:3',
            'alternatives.*.date' => 'required|date|after:today',
            'alternatives.*.start_time' => 'required|date_format:H:i',
            'alternatives.*.end_time' => 'required|date_format:H:i',
            'response_note' => 'nullable|string|max:1000',
        ]);

        return $this->respond(fn () => $this->reschedules->offerAlternatives($this->providerRequest($rescheduleId), $request->user(), $data['alternatives'], $data['response_note'] ?? null), 'Other dates sent to the client.');
    }

    private function panel(BookingModel $booking, string $role)
    {
        return view('partials.booking-reschedule', [
            'booking' => $booking,
            'role' => $role,
            'requests' => BookingRescheduleRequestModel::where('booking_id', $booking->id)->latest('id')->take(5)->get(),
            'portal' => $role === 'client' ? 'client' : (auth()->user()->role === 'freelancer' ? 'freelancer' : 'owner'),
            'label' => fn (array $slot) => $this->reschedules->label($slot),
        ]);
    }

    private function providerBookings()
    {
        $user = auth()->user();

        return $user->role === 'freelancer'
            ? BookingModel::where('booking_type', 'freelancer')->where('provider_id', $user->id)
            : BookingModel::where('booking_type', 'studio')->whereIn('provider_id', StudiosModel::where('user_id', $user->id)->select('id'));
    }

    private function providerRequest($rescheduleId): BookingRescheduleRequestModel
    {
        return BookingRescheduleRequestModel::whereIn('booking_id', $this->providerBookings()->select('id'))->findOrFail($rescheduleId);
    }

    private function respond(callable $action, string $message)
    {
        try {
            $action();
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }
}
