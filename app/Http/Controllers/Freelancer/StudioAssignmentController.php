<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StudioPhotographer\AssignedBookingController;
use App\Http\Controllers\StudioPhotographer\OnlineGalleryController;
use App\Http\Requests\StudioPhotographer\UpdateAssignmentStatusRequest;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Services\FreelancerBookingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudioAssignmentController extends Controller
{
    public function index(Request $request, FreelancerBookingAccess $access)
    {
        $assignments = collect();
        if (Schema::hasColumn('tbl_user_roles', 'booking_id')) {
            $assignments = BookingAssignedPhotographerModel::where('photographer_id', $request->user()->id)
                ->where('is_freelancer', true)->whereNotIn('status', ['cancelled', 'completed'])
                ->with('booking', 'studio')->latest()->get()
                ->filter(fn ($assignment) => $access->allows($request->user(), $assignment));
        }

        return view('freelancer.view-studio-assignments', compact('assignments'));
    }

    public function show(Request $request, int $id)
    {
        $assignment = BookingAssignedPhotographerModel::with('booking', 'studio')->findOrFail($id);
        abort_unless(app(FreelancerBookingAccess::class)->allows($request->user(), $assignment), 403);

        return response()->json(['success' => true, 'assignment' => $assignment]);
    }

    public function status(UpdateAssignmentStatusRequest $request, int $id)
    {
        return $this->withAccess($request, $id, 'update-status', fn () => app(AssignedBookingController::class)->updateAssignmentStatus($request, $id));
    }

    public function images(Request $request, int $id)
    {
        $request->validate([
            'images' => 'required|array|min:1|max:50',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'gallery_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        return $this->withAccess($request, $id, 'upload', function ($assignment) use ($request) {
            $controller = app(OnlineGalleryController::class);
            $response = $controller->uploadImages($request, $assignment->booking_id);
            $data = $response->getData(true);
            if ($response->isSuccessful() && ($data['success'] ?? false)) {
                $review = $controller->submitForApproval($data['gallery']['id']);
                abort_unless($review->isSuccessful(), 500, 'Gallery could not be submitted for owner review.');

                return $review;
            }

            return $response;
        });
    }

    private function withAccess(Request $request, int $id, string $action, callable $callback)
    {
        return DB::transaction(function () use ($request, $id, $action, $callback) {
            $candidate = BookingAssignedPhotographerModel::findOrFail($id);
            \App\Models\BookingModel::lockForUpdate()->findOrFail($candidate->booking_id);
            $assignment = BookingAssignedPhotographerModel::lockForUpdate()->findOrFail($id);
            abort_unless(app(FreelancerBookingAccess::class)->allows($request->user(), $assignment, $action), 403);

            return $callback($assignment);
        });
    }
}
