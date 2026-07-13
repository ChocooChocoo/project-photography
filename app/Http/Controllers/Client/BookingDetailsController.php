<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\Freelancer\PackagesModel as FreelancerPackagesModel;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioRatingModel;
use App\Models\FreelancerRatingModel;
use Illuminate\Support\Facades\DB;

class BookingDetailsController extends Controller
{
    /**
     * Display booking details for studio or freelancer.
     */
    public function index($type, $id)
    {
        if ($type === 'studio') {
            // Fetch studio details with ratings
            $studio = StudiosModel::whereIn('status', ['approved', 'active', 'verified'])
                ->with(['location', 'category', 'packages', 'schedules', 'user', 'services.category'])
                ->findOrFail($id);

            // Use the stored aggregate (kept in sync by StudioRatingModel::updateAggregate())
            // instead of recomputing avg()/count() on every request.
            $studio->average_rating = (float) $studio->avg_rating;
            $studio->ratings_count = $studio->total_reviews;

            // Fetch recent ratings for this studio
            $recentRatings = StudioRatingModel::where('studio_id', $id)
                ->published()
                ->with('client:id,first_name,last_name,profile_photo')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Calculate rating distribution
            $ratingDistribution = $this->getRatingDistribution(StudioRatingModel::class, 'studio_id', $id);

            // Fetch studio packages grouped by category
            $studioPackages = PackagesModel::where('studio_id', $id)
                ->where('status', 'active')
                ->with('category')
                ->get()
                ->groupBy('category_id');

            // Get category IDs that have active packages
            $categoryIdsWithPackages = $studioPackages->keys()->toArray();

            // Fetch only those categories
            $categories = CategoriesModel::whereIn('id', $categoryIdsWithPackages)
                ->where('status', 'active')
                ->orderBy('category_name', 'asc')
                ->get();

            $portfolioGalleries = \App\Models\StudioOwner\StudioOnlineGalleryModel::where('studio_id', $id)
                ->where('gallery_type', 'portfolio')
                ->where('status', 'active')
                ->latest()
                ->get();

            return view('client.booking-details', compact(
                'studio',
                'categories',
                'studioPackages',
                'type',
                'recentRatings',
                'ratingDistribution',
                'portfolioGalleries'
            ));
        }

        // For freelancer
        $freelancer = ProfileModel::with(['user', 'location', 'categories', 'services', 'schedule'])
            ->whereHas('user', function($query) {
                $query->where('status', 'active');
            })
            ->where('user_id', $id)
            ->firstOrFail();

        // Use the stored aggregate (kept in sync by FreelancerRatingModel::updateAggregate())
        // instead of recomputing avg()/count() on every request.
        $freelancer->average_rating = (float) $freelancer->avg_rating;
        $freelancer->ratings_count = $freelancer->total_reviews;

        // Fetch recent ratings for this freelancer
        $recentRatings = FreelancerRatingModel::where('freelancer_id', $id)
            ->published()
            ->with('client:id,first_name,last_name,profile_photo')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate rating distribution
        $ratingDistribution = $this->getRatingDistribution(FreelancerRatingModel::class, 'freelancer_id', $id);

        // Fetch freelancer packages grouped by category
        $freelancerPackages = FreelancerPackagesModel::where('user_id', $freelancer->user_id)
            ->where('status', 'active')
            ->with('category')
            ->get()
            ->groupBy('category_id');

        // Get category IDs that have active packages
        $categoryIdsWithPackages = $freelancerPackages->keys()->toArray();

        // Fetch only those categories
        $categories = CategoriesModel::whereIn('id', $categoryIdsWithPackages)
            ->where('status', 'active')
            ->orderBy('category_name', 'asc')
            ->get();

        $portfolioGalleries = \App\Models\Freelancer\FreelanceOnlineGalleryModel::where('freelancer_id', $freelancer->user_id)
            ->where('gallery_type', 'portfolio')
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('client.booking-details', compact(
            'freelancer',
            'categories',
            'freelancerPackages',
            'type',
            'recentRatings',
            'ratingDistribution',
            'portfolioGalleries'
        ));
        
        abort(404, 'Invalid type');
    }

    /**
     * Build rating distribution with a single grouped query.
     */
    private function getRatingDistribution(string $modelClass, string $foreignKey, int $entityId): array
    {
        $distribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        $counts = $modelClass::query()
            ->select('rating', DB::raw('COUNT(*) as total'))
            ->where($foreignKey, $entityId)
            ->groupBy('rating')
            ->pluck('total', 'rating');

        foreach ($counts as $rating => $total) {
            $rating = (int) $rating;

            if (array_key_exists($rating, $distribution)) {
                $distribution[$rating] = (int) $total;
            }
        }

        return $distribution;
    }
}
