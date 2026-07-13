<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudioRatingModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudioRatingController extends Controller
{
    use Notifiable;


    /**
     * Display the review form for a booking.
     */
    public function create($bookingId)
    {
        try {
            $clientId = Auth::id();
            
            // Check if booking can be reviewed
            if (!StudioRatingModel::canReview($bookingId, $clientId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking cannot be reviewed or has already been reviewed.'
                ], 400);
            }

            $booking = BookingModel::where('id', $bookingId)
                ->where('client_id', $clientId)
                ->where('status', 'completed')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found or not eligible for review.'
                ], 404);
            }

            // Get studio information based on booking type
            $studioInfo = null;
            if ($booking->booking_type === 'studio') {
                $studioInfo = \App\Models\StudioOwner\StudiosModel::where('id', $booking->provider_id)
                    ->select('id', 'studio_name', 'studio_logo')
                    ->first();
            } else {
                // For freelancer bookings, we might not show review option
                return response()->json([
                    'success' => false,
                    'message' => 'Reviews are only available for studio bookings at this time.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->booking_reference,
                    'studio_name' => $studioInfo->studio_name ?? 'Studio',
                    'studio_logo' => $studioInfo->studio_logo ?? null,
                    'event_date' => \Carbon\Carbon::parse($booking->event_date)->format('M d, Y'),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Review form error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading review form. Please try again.'
            ], 500);
        }
    }

    /**
     * Get preset reviews based on rating.
     */
    public function getPresetReviews(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);

        $presets = StudioRatingModel::getPresetReviews($request->rating);

        return response()->json([
            'success' => true,
            'type' => $presets['type'],
            'reviews' => $presets['reviews']
        ]);
    }

    /**
     * Store a new rating.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|exists:tbl_bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'review_text' => 'required|string|min:10|max:2000',
                'preset_used' => 'nullable|string|max:500',
                'is_recommend' => 'required|boolean',
            ]);

            $clientId = Auth::id();

            // Verify booking belongs to client and is completed
            $booking = BookingModel::where('id', $request->booking_id)
                ->where('client_id', $clientId)
                ->where('status', 'completed')
                ->firstOrFail();

            // Check if already reviewed
            $existingReview = StudioRatingModel::where('booking_id', $request->booking_id)->exists();
            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reviewed this booking.'
                ], 400);
            }

            // Determine review type based on rating
            $reviewType = StudioRatingModel::getReviewTypeFromRating($request->rating);

            // Create rating
            $rating = StudioRatingModel::create([
                'booking_id' => $request->booking_id,
                'client_id' => $clientId,
                'studio_id' => $booking->provider_id, // provider_id is the studio ID
                'rating' => $request->rating,
                'title' => $request->title,
                'review_text' => $request->review_text,
                'review_type' => $reviewType,
                'preset_used' => $request->preset_used,
                'is_recommend' => $request->is_recommend,
            ]);

            StudioRatingModel::updateAggregate($booking->provider_id);

            // Load relationships for response
            $rating->load(['client', 'studio']);

            $studioOwner = $rating->studio ? $rating->studio->user : null;
            if ($studioOwner) {
                $this->notifyReviewReceived($rating, $studioOwner);
            }

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your review!',
                'rating' => $rating
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all reviews for a studio.
     */
    public function getStudioReviews($studioId)
    {
        try {
            $reviews = StudioRatingModel::where('studio_id', $studioId)
                ->published()
                ->with(['client:id,first_name,last_name,profile_photo'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Read the stored aggregate (kept in sync by StudioRatingModel::updateAggregate())
            // instead of recomputing avg()/count() on every request.
            $studio = StudiosModel::find($studioId);
            $averageRating = $studio->avg_rating ?? 0;
            $totalReviews = $studio->total_reviews ?? 0;

            // Rating distribution (published only)
            $distribution = [
                5 => StudioRatingModel::where('studio_id', $studioId)->published()->where('rating', 5)->count(),
                4 => StudioRatingModel::where('studio_id', $studioId)->published()->where('rating', 4)->count(),
                3 => StudioRatingModel::where('studio_id', $studioId)->published()->where('rating', 3)->count(),
                2 => StudioRatingModel::where('studio_id', $studioId)->published()->where('rating', 2)->count(),
                1 => StudioRatingModel::where('studio_id', $studioId)->published()->where('rating', 1)->count(),
            ];

            return response()->json([
                'success' => true,
                'reviews' => $reviews,
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews,
                'distribution' => $distribution
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reviews: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a booking can be reviewed.
     */
    public function checkCanReview($bookingId)
    {
        try {
            $clientId = Auth::id();
            $canReview = StudioRatingModel::canReview($bookingId, $clientId);

            return response()->json([
                'success' => true,
                'can_review' => $canReview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking review status: ' . $e->getMessage()
            ], 500);
        }
    }
}