<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\PhotographerRatingModel;
use App\Models\PhotoRatingModel;
use App\Models\ServiceRatingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingFeedbackController extends Controller
{
    public function show(Request $request, $booking)
    {
        $booking = BookingModel::where('client_id', $request->user()->id)->where('status', 'completed')->with(['packages', 'assignedPhotographers.photographer'])->findOrFail($booking);
        $targets = collect();
        foreach ($booking->packages->where('package_type', $booking->booking_type)->unique('package_id') as $package) {
            if ($package->package_id) {
                $targets->push(['type' => 'service', 'id' => $package->package_id, 'label' => $package->package_name,
                    'review' => ServiceRatingModel::where('booking_id', $booking->id)->where('package_id', $package->package_id)->where('package_type', $package->package_type)->first()]);
            }
        }
        if ($booking->booking_type === 'studio') {
            foreach ($booking->assignedPhotographers->where('status', '!=', 'cancelled')->unique('photographer_id') as $assignment) {
                $targets->push(['type' => 'photographer', 'id' => $assignment->photographer_id, 'label' => $assignment->photographer?->full_name ?? 'Assigned photographer',
                    'review' => PhotographerRatingModel::where('booking_id', $booking->id)->where('photographer_id', $assignment->photographer_id)->first()]);
            }
        }
        $galleryModel = $booking->booking_type === 'studio' ? StudioOnlineGalleryModel::class : FreelanceOnlineGalleryModel::class;
        foreach ($galleryModel::where('booking_id', $booking->id)->where('status', 'active')->where('gallery_status', 'published')->where('approval_status', 'approved')->get() as $gallery) {
            $targets->push(['type' => 'photo', 'id' => $gallery->id, 'label' => $gallery->gallery_name ?: 'Delivered gallery',
                'review' => PhotoRatingModel::where('booking_id', $booking->id)->where('gallery_type', $booking->booking_type)->where('gallery_id', $gallery->id)->first()]);
        }
        $reports = \App\Models\ReportModel::where('reporter_id', $request->user()->id)->where('booking_id', $booking->id)->latest()->get();

        return view('client.booking-feedback', compact('booking', 'targets', 'reports'));
    }

    public function store(Request $request, $booking, string $type)
    {
        abort_unless(in_array($type, ['service', 'photographer', 'photo'], true), 404);
        $data = $request->validate([
            'target_id' => 'required|integer|min:1',
            'rating' => ($type === 'photo' ? 'required_without:review_text|nullable' : 'required').'|integer|between:1,5',
            'review_text' => ($type === 'photo' ? 'required_without:rating|nullable' : 'nullable').'|string|max:2000',
        ]);

        return DB::transaction(function () use ($request, $booking, $type, $data) {
            $booking = BookingModel::where('client_id', $request->user()->id)->where('status', 'completed')->lockForUpdate()->findOrFail($booking);
            if ($type === 'service') {
                $package = $booking->packages()->where('package_id', $data['target_id'])->where('package_type', $booking->booking_type)->firstOrFail();
                $model = ServiceRatingModel::class;
                $target = ['package_id' => $package->package_id, 'package_type' => $package->package_type];
            } elseif ($type === 'photographer') {
                abort_unless($booking->booking_type === 'studio', 404);
                $booking->assignedPhotographers()->where('photographer_id', $data['target_id'])->where('status', '!=', 'cancelled')->firstOrFail();
                $model = PhotographerRatingModel::class;
                $target = ['photographer_id' => $data['target_id']];
            } else {
                $galleryModel = $booking->booking_type === 'studio' ? StudioOnlineGalleryModel::class : FreelanceOnlineGalleryModel::class;
                $galleryModel::where('booking_id', $booking->id)->where('status', 'active')->where('gallery_status', 'published')->where('approval_status', 'approved')->findOrFail($data['target_id']);
                $model = PhotoRatingModel::class;
                $target = ['gallery_id' => $data['target_id'], 'gallery_type' => $booking->booking_type];
            }
            abort_if($model::where('booking_id', $booking->id)->where($target)->exists(), 409, 'Feedback has already been submitted for this item.');
            $feedback = $model::create($target + ['booking_id' => $booking->id, 'client_id' => $request->user()->id,
                'rating' => $data['rating'] ?? null, 'review_text' => $data['review_text'] ?? null, 'status' => 'published']);

            return response()->json(['success' => true, 'feedback' => $feedback], 201);
        });
    }
}
