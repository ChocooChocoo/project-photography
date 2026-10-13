<?php

namespace App\Http\Controllers;

use App\Models\BookingModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Rules\DriveLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GalleryDeliveryController extends Controller
{
    public function saveDrive(Request $request, $booking)
    {
        $booking = BookingModel::findOrFail($booking);
        $user = $request->user();
        $owner = $booking->booking_type === 'studio' && $booking->studio?->user_id === $user->id;
        $freelancer = $booking->booking_type === 'freelancer' && (int) $booking->provider_id === $user->id && $user->role === 'freelancer';
        $photographer = $booking->booking_type === 'studio' && $booking->assignedPhotographers()
            ->where('photographer_id', $user->id)->whereIn('status', ['on_site', 'in_progress', 'completed'])->exists();
        abort_unless($owner || $freelancer || $photographer, 404);
        $data = $request->validate(['drive_link' => ['required', 'string', 'max:2048', 'url', new DriveLink]]);

        return DB::transaction(function () use ($booking, $user, $owner, $freelancer, $data) {
            $booking = BookingModel::lockForUpdate()->findOrFail($booking->id);
            abort_unless(in_array($booking->status, ['in_progress', 'completed'], true), 422, 'Delivery requires an in-progress booking.');
            $model = $booking->booking_type === 'studio' ? StudioOnlineGalleryModel::class : FreelanceOnlineGalleryModel::class;
            $gallery = $model::where('booking_id', $booking->id)->lockForUpdate()->first();
            abort_if($gallery?->gallery_status === 'published', 422, 'Request a revision before changing published delivery.');
            $values = [
                'drive_link' => $data['drive_link'], 'gallery_status' => $freelancer ? 'published' : 'draft',
                'approval_status' => ($owner || $freelancer) ? 'approved' : 'pending',
                'approved_by' => ($owner || $freelancer) ? $user->id : null,
                'approved_at' => ($owner || $freelancer) ? now() : null,
                'published_at' => $freelancer ? now() : null,
                'submitted_by' => $user->id, 'submitted_at' => now(),
                'rejected_by' => null, 'rejected_at' => null, 'rejection_reason' => null,
            ];
            if ($gallery) {
                $gallery->update($values);
            } else {
                $gallery = $model::create($values + [
                    'booking_id' => $booking->id, 'client_id' => $booking->client_id,
                    $owner || $booking->booking_type === 'studio' ? 'studio_id' : 'freelancer_id' => $booking->provider_id,
                    'gallery_reference' => $model::generateGalleryReference(), 'gallery_name' => $booking->event_name.' Gallery',
                    'images' => [], 'total_photos' => 0, 'status' => 'active',
                ]);
            }

            return response()->json(['success' => true, 'gallery' => $gallery]);
        });
    }
}
