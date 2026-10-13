<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudioRatingModel;
use App\Models\FreelancerRatingModel;
use Illuminate\Http\Request;

class ReviewModerationController extends Controller
{
    /**
     * Display a listing of all reviews (studio + freelancer), filterable by status.
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        $studioReviews = StudioRatingModel::with(['client:id,first_name,last_name', 'studio:id,studio_name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn ($review) => $this->normalize($review, 'studio'));

        $freelancerReviews = FreelancerRatingModel::with(['client:id,first_name,last_name', 'freelancer.user:id,first_name,last_name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn ($review) => $this->normalize($review, 'freelancer'));

        $reviews = $studioReviews->concat($freelancerReviews)->sortByDesc('created_at')->values();
        foreach (['service' => \App\Models\ServiceRatingModel::class, 'photographer' => \App\Models\PhotographerRatingModel::class, 'photo' => \App\Models\PhotoRatingModel::class] as $type => $model) {
            // Existing deployments may not yet have the additive feedback migration.
            if (! \Illuminate\Support\Facades\Schema::hasTable((new $model)->getTable())) continue;
            $feedback = $model::with('booking.client')->when($status, fn ($query) => $query->where('status', $status))->latest()->get()
                ->map(fn ($review) => [
                    'id' => $review->id, 'type' => $type, 'provider_name' => $review->booking?->booking_reference,
                    'client_name' => $review->booking?->client?->full_name, 'rating' => $review->rating,
                    'title' => null, 'review_text' => $review->review_text, 'status' => $review->status, 'created_at' => $review->created_at,
                ]);
            $reviews = $reviews->concat($feedback);
        }
        $reviews = $reviews->sortByDesc('created_at')->values();

        return view('admin.view-review-moderation', compact('reviews', 'status'));
    }

    /**
     * Flag a review as needing attention.
     */
    public function flag(string $type, int $id)
    {
        $review = $this->findReview($type, $id);
        $review->update(['status' => 'flagged']);
        $this->refreshAggregate($type, $review);

        return response()->json(['success' => true, 'message' => 'Review flagged.']);
    }

    /**
     * Remove a review from public view.
     */
    public function remove(string $type, int $id)
    {
        $review = $this->findReview($type, $id);
        $review->update(['status' => 'removed']);
        $this->refreshAggregate($type, $review);

        return response()->json(['success' => true, 'message' => 'Review removed.']);
    }

    /**
     * Restore a flagged/removed review back to published.
     */
    public function republish(string $type, int $id)
    {
        $review = $this->findReview($type, $id);
        $review->update(['status' => 'published']);
        $this->refreshAggregate($type, $review);

        return response()->json(['success' => true, 'message' => 'Review published.']);
    }

    private function findReview(string $type, int $id)
    {
        $model = match ($type) {
            'studio' => StudioRatingModel::class,
            'freelancer' => FreelancerRatingModel::class,
            'service' => \App\Models\ServiceRatingModel::class,
            'photographer' => \App\Models\PhotographerRatingModel::class,
            'photo' => \App\Models\PhotoRatingModel::class,
            default => abort(404),
        };
        return $model::findOrFail($id);
    }

    private function refreshAggregate(string $type, $review): void
    {
        if ($type === 'studio') {
            StudioRatingModel::updateAggregate($review->studio_id);
        } elseif ($type === 'freelancer') {
            FreelancerRatingModel::updateAggregate($review->freelancer_id);
        }
    }

    private function normalize($review, string $type): array
    {
        return [
            'id' => $review->id,
            'type' => $type,
            'provider_name' => $type === 'studio'
                ? optional($review->studio)->studio_name
                : optional(optional($review->freelancer)->user)->first_name . ' ' . optional(optional($review->freelancer)->user)->last_name,
            'client_name' => optional($review->client)->first_name . ' ' . optional($review->client)->last_name,
            'rating' => $review->rating,
            'title' => $review->title,
            'review_text' => $review->review_text,
            'status' => $review->status,
            'created_at' => $review->created_at,
        ];
    }
}
