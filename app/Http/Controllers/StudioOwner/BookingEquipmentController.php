<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\StudioOwner\BookingEquipmentModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingEquipmentController extends Controller
{
    /**
     * List equipment assigned to a booking.
     */
    public function index($bookingId)
    {
        $booking = $this->findOwnerBooking($bookingId);
        return response()->json([
            'success' => true,
            'data' => BookingEquipmentModel::where('booking_id', $booking->id)->get(),
        ]);
    }

    /**
     * Assign equipment to a booking.
     */
    public function store(Request $request, $bookingId)
    {
        $booking = $this->findOwnerBooking($bookingId);
        $validated = $request->validate([
            'assignment_id' => [
                'nullable', 'integer',
                Rule::exists('tbl_booking_assigned_photographers', 'id')
                    ->where('booking_id', $booking->id)
                    ->where('studio_id', $booking->provider_id)
                    ->whereNot('status', 'cancelled'),
            ],
            // Equipment comes from the studio catalog only (T02).
            'equipment_id' => ['required', 'integer', Rule::exists('tbl_studio_equipment', 'id')->where('studio_id', $booking->provider_id)],
            'notes' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($booking, $validated) {
            $booking = BookingModel::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (in_array($booking->status, [BookingModel::STATUS_COMPLETED, BookingModel::STATUS_CANCELLED], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment cannot be assigned to a completed or cancelled booking.',
                ], 422);
            }

            $assignments = $booking->assignedPhotographers()
                ->where('studio_id', $booking->provider_id)
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()->get();
            if ($assignments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assign at least one photographer before assigning equipment.',
                ], 422);
            }
            if (isset($validated['assignment_id']) && ! $assignments->contains('id', $validated['assignment_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Select a non-cancelled photographer assignment from this booking.',
                ], 422);
            }

            $catalog = \App\Models\StudioOwner\StudioEquipmentModel::where('studio_id', $booking->provider_id)
                ->lockForUpdate()->findOrFail($validated['equipment_id']);
            if ($catalog->status !== 'available') {
                return response()->json(['success' => false, 'message' => 'This equipment is not available.'], 422);
            }
            $conflict = BookingEquipmentModel::where('equipment_id', $catalog->id)
                ->whereHas('booking', function ($query) use ($booking) {
                    $query->whereNotIn('status', ['cancelled', 'completed'])
                        ->overlappingDates($booking->event_date, $booking->event_end_date)
                        ->where(function ($query) use ($booking) {
                            if (! $booking->start_time || ! $booking->end_time) {
                                return;
                            }
                            $query->whereNull('start_time')->orWhereNull('end_time')
                                ->orWhere(fn ($q) => $q->where('start_time', '<', $booking->end_time)->where('end_time', '>', $booking->start_time));
                        });
                })->exists();
            if ($conflict) {
                return response()->json(['success' => false, 'message' => 'This equipment is already reserved for an overlapping booking.'], 409);
            }

            $equipment = BookingEquipmentModel::create([
                'booking_id' => $booking->id,
                'equipment_id' => $catalog->id,
                'brand' => $catalog->brand,
                'assignment_id' => $validated['assignment_id'] ?? null,
                'equipment_name' => trim($catalog->name.' '.($catalog->model ?? '')),
                'equipment_type' => $catalog->type,
                'notes' => $validated['notes'] ?? null,
                'confirmed' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Equipment assigned successfully.',
                'data' => $equipment
            ], 201);
        });
    }

    /**
     * Remove equipment from a booking.
     */
    public function destroy($id)
    {
        $equipment = BookingEquipmentModel::findOrFail($id);
        $this->findOwnerBooking($equipment->booking_id);
        $equipment->delete();
        return response()->json([
            'success' => true,
            'message' => 'Equipment removed successfully.'
        ]);
    }

    /**
     * Get a studio booking that belongs to one of the owner's studios.
     */
    private function findOwnerBooking($bookingId): BookingModel
    {
        $studioIds = StudiosModel::where('user_id', Auth::id())->pluck('id')->toArray();

        if (empty($studioIds)) {
            abort(404, 'No studios found for this owner');
        }

        return BookingModel::where('id', $bookingId)
            ->whereIn('provider_id', $studioIds)
            ->where('booking_type', 'studio')
            ->firstOrFail();
    }
}
