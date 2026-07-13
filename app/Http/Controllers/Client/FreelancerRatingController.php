<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FreelancerRatingModel;
use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\UserModel;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FreelancerRatingController extends Controller
{
    use Notifiable;

    /**
     * Display the review form for a freelancer booking.
     */
    public function create($bookingId)
    {
        try {
            Log::info('Loading freelancer review form for booking: ' . $bookingId);
            $clientId = Auth::id();
            
            // Check if booking can be reviewed
            if (!FreelancerRatingModel::canReview($bookingId, $clientId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking cannot be reviewed or has already been reviewed.'
                ], 400);
            }

            $booking = BookingModel::where('id', $bookingId)
                ->where('client_id', $clientId)
                ->where('status', 'completed')
                ->where('booking_type', 'freelancer')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer booking not found or not eligible for review.'
                ], 404);
            }

            // Get freelancer information
            $freelancer = ProfileModel::where('user_id', $booking->provider_id)
                ->select('user_id', 'brand_name', 'brand_logo')
                ->first();

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->booking_reference,
                    'freelancer_name' => $freelancer->brand_name ?? 'Freelancer',
                    'freelancer_logo' => $freelancer->brand_logo ?? null,
                    'event_date' => \Carbon\Carbon::parse($booking->event_date)->format('M d, Y'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Freelancer review form error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading review form. Please try again.'
            ], 500);
        }
    }

    /**
     * Get preset reviews based on rating for freelancers.
     */
    public function getPresetReviews(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);

        $presets = FreelancerRatingModel::getPresetReviews($request->rating);

        return response()->json([
            'success' => true,
            'type' => $presets['type'],
            'reviews' => $presets['reviews']
        ]);
    }

    /**
     * Store a new freelancer rating.
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

            // Verify booking belongs to client and is completed freelancer booking
            $booking = BookingModel::where('id', $request->booking_id)
                ->where('client_id', $clientId)
                ->where('status', 'completed')
                ->where('booking_type', 'freelancer')
                ->firstOrFail();

            // Check if already reviewed
            $existingReview = FreelancerRatingModel::where('booking_id', $request->booking_id)->exists();
            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reviewed this freelancer.'
                ], 400);
            }

            // Determine review type based on rating
            $reviewType = FreelancerRatingModel::getReviewTypeFromRating($request->rating);

            // Create rating
            $rating = FreelancerRatingModel::create([
                'booking_id' => $request->booking_id,
                'client_id' => $clientId,
                'freelancer_id' => $booking->provider_id,
                'rating' => $request->rating,
                'title' => $request->title,
                'review_text' => $request->review_text,
                'review_type' => $reviewType,
                'preset_used' => $request->preset_used,
                'is_recommend' => $request->is_recommend,
            ]);

            FreelancerRatingModel::updateAggregate($booking->provider_id);

            $freelancer = UserModel::find($booking->provider_id);
            if ($freelancer) {
                $this->notifyReviewReceived($rating, $freelancer);
            }

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your review!',
                'rating' => $rating
            ]);

        } catch (\Exception $e) {
            Log::error('Error submitting freelancer review: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error submitting review. Please try again.'
            ], 500);
        }
    }

    /**
     * Get all reviews for a freelancer.
     */
    public function getFreelancerReviews($freelancerId)
    {
        try {
            $reviews = FreelancerRatingModel::where('freelancer_id', $freelancerId)
                ->published()
                ->with(['client:id,first_name,last_name,profile_photo'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Read the stored aggregate (kept in sync by FreelancerRatingModel::updateAggregate())
            // instead of recomputing avg()/count() on every request.
            $freelancerProfile = ProfileModel::where('user_id', $freelancerId)->first();
            $averageRating = $freelancerProfile->avg_rating ?? 0;
            $totalReviews = $freelancerProfile->total_reviews ?? 0;

            // Rating distribution (published only)
            $distribution = [
                5 => FreelancerRatingModel::where('freelancer_id', $freelancerId)->published()->where('rating', 5)->count(),
                4 => FreelancerRatingModel::where('freelancer_id', $freelancerId)->published()->where('rating', 4)->count(),
                3 => FreelancerRatingModel::where('freelancer_id', $freelancerId)->published()->where('rating', 3)->count(),
                2 => FreelancerRatingModel::where('freelancer_id', $freelancerId)->published()->where('rating', 2)->count(),
                1 => FreelancerRatingModel::where('freelancer_id', $freelancerId)->published()->where('rating', 1)->count(),
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
     * Check if a freelancer booking can be reviewed.
     */
    public function checkCanReview($bookingId)
    {
        try {
            $clientId = Auth::id();
            $canReview = FreelancerRatingModel::canReview($bookingId, $clientId);

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