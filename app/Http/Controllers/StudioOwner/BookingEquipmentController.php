<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\StudioOwner\BookingEquipmentModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingEquipmentController extends Controller
{
    /**
     * List equipment assigned to a booking.
     */
    public function index($bookingId)
    {
        try {
            $booking = $this->findOwnerBooking($bookingId);

            return response()->json([
                'success' => true,
                'data' => BookingEquipmentModel::where('booking_id', $booking->id)->get(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching equipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign equipment to a booking.
     */
    public function store(Request $request, $bookingId)
    {
        try {
            $booking = $this->findOwnerBooking($bookingId);

            $validated = $request->validate([
                'assignment_id' => 'nullable|integer',
                'equipment_name' => 'required|string',
                'equipment_type' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            $equipment = BookingEquipmentModel::create([
                'booking_id' => $booking->id,
                'assignment_id' => $validated['assignment_id'] ?? null,
                'equipment_name' => $validated['equipment_name'],
                'equipment_type' => $validated['equipment_type'],
                'notes' => $validated['notes'] ?? null,
                'confirmed' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Equipment assigned successfully.',
                'data' => $equipment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning equipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove equipment from a booking.
     */
    public function destroy($id)
    {
        try {
            $equipment = BookingEquipmentModel::findOrFail($id);
            $this->findOwnerBooking($equipment->booking_id);

            $equipment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Equipment removed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing equipment: ' . $e->getMessage()
            ], 500);
        }
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
