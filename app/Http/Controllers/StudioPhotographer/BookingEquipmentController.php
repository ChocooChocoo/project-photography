<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\BookingEquipmentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingEquipmentController extends Controller
{
    /**
     * Confirm that the photographer has the equipment for their assignment.
     */
    public function confirm(Request $request, $id)
    {
        try {
            $equipment = BookingEquipmentModel::findOrFail($id);

            $isAssigned = BookingAssignedPhotographerModel::where('booking_id', $equipment->booking_id)
                ->where('photographer_id', Auth::id())
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this booking.'
                ], 403);
            }

            $equipment->update([
                'confirmed' => true,
                'confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Equipment confirmed successfully.',
                'data' => $equipment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error confirming equipment: ' . $e->getMessage()
            ], 500);
        }
    }
}
