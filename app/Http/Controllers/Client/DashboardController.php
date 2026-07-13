<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudioOwner\StudiosModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\StudioRatingModel;
use App\Models\FreelancerRatingModel;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the client dashboard.
     */
    public function index(Request $request)
    {
        // Fetch approved studios (status = 'approved') with their average ratings
        $studios = StudiosModel::whereIn('status', ['approved', 'active', 'verified'])
            ->with(['location', 'category', 'packages'])
            ->addSelect(['*', DB::raw('avg_rating as average_rating'), DB::raw('total_reviews as ratings_count')])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch freelancers with profile data and their average ratings
        $freelancers = ProfileModel::with(['user', 'location', 'categories'])
            ->whereHas('user', function($query) {
                $query->where('status', 'active');
            })
            ->addSelect(['*', DB::raw('avg_rating as average_rating'), DB::raw('total_reviews as ratings_count')])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch categories for filter
        $categories = CategoriesModel::where('status', 'active')
            ->orderBy('category_name', 'asc')
            ->get();

        // Fetch locations for filter
        $locations = LocationModel::where('status', 'active')
            ->orderBy('municipality', 'asc')
            ->get();

        return view('client.dashboard', compact('studios', 'freelancers', 'categories', 'locations'));
    }

    /**
     * AJAX endpoint for filtering studios and freelancers.
     */
    public function filter(Request $request)
    {
        $query = $request->input('query', '');
        $categoryId = $request->input('category_id');
        $locationId = $request->input('location_id');
        $photographerType = $request->input('photographer_type');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $minRating = $request->input('min_rating'); // New rating filter

        $results = [];

        // Filter studios
        if (!$photographerType || $photographerType === 'studio') {
            $studioQuery = StudiosModel::whereIn('status', ['approved', 'active', 'verified'])
                ->with(['location', 'category', 'packages'])
                ->withCount(['ratings as average_rating' => function($query) {
                    $query->select(DB::raw('coalesce(avg(rating), 0)'));
                }])
                ->withCount('ratings');

            if ($categoryId) {
                $studioQuery->where('category_id', $categoryId);
            }

            if ($locationId) {
                $studioQuery->where('location_id', $locationId);
            }

            if ($minPrice) {
                $studioQuery->where('starting_price', '>=', $minPrice);
            }

            if ($maxPrice) {
                $studioQuery->where('starting_price', '<=', $maxPrice);
            }

            if ($query) {
                $studioQuery->where(function($q) use ($query) {
                    $q->where('studio_name', 'like', "%{$query}%")
                      ->orWhere('studio_description', 'like', "%{$query}%");
                });
            }

            $studios = $studioQuery->orderBy('created_at', 'desc')->get();
            
            foreach ($studios as $studio) {
                $averageRating = round($studio->average_rating, 1);
                
                // Apply rating filter if specified
                if ($minRating !== null && $averageRating < $minRating) {
                    continue;
                }
                
                $results[] = [
                    'type' => 'studio',
                    'id' => $studio->id,
                    'name' => $studio->studio_name,
                    'logo' => $studio->studio_logo ? asset('storage/' . $studio->studio_logo) : asset('assets/images/sellers/7.png'),
                    'location' => $studio->location ? $studio->location->municipality . ', Cavite' : 'Location not specified',
                    'location_id' => $studio->location_id,
                    'starting_price' => number_format($studio->starting_price, 2),
                    'description' => $studio->studio_description,
                    'type_label' => 'Studio',
                    'rating' => $averageRating,
                    'total_ratings' => $studio->ratings_count,
                    'rating_display' => $this->getRatingDisplay($averageRating, $studio->ratings_count)
                ];
            }
        }

        // Filter freelancers
        if (!$photographerType || $photographerType === 'freelancer') {
            $freelancerQuery = ProfileModel::with(['user', 'location', 'categories'])
                ->whereHas('user', function($q) {
                    $q->where('status', 'active');
                })
                ->withCount(['ratings as average_rating' => function($query) {
                    $query->select(DB::raw('coalesce(avg(rating), 0)'));
                }])
                ->withCount('ratings');

            if ($categoryId) {
                $freelancerQuery->whereHas('categories', function($q) use ($categoryId) {
                    $q->where('tbl_categories.id', $categoryId);
                });
            }

            if ($locationId) {
                $freelancerQuery->where('location_id', $locationId);
            }

            if ($minPrice) {
                $freelancerQuery->where('starting_price', '>=', $minPrice);
            }

            if ($maxPrice) {
                $freelancerQuery->where('starting_price', '<=', $maxPrice);
            }

            if ($query) {
                $freelancerQuery->where(function($q) use ($query) {
                    $q->where('brand_name', 'like', "%{$query}%")
                      ->orWhere('tagline', 'like', "%{$query}%")
                      ->orWhere('bio', 'like', "%{$query}%");
                });
            }

            $freelancers = $freelancerQuery->orderBy('created_at', 'desc')->get();
            
            foreach ($freelancers as $freelancer) {
                $averageRating = round($freelancer->average_rating, 1);
                
                // Apply rating filter if specified
                if ($minRating !== null && $averageRating < $minRating) {
                    continue;
                }
                
                $results[] = [
                    'type' => 'freelancer',
                    'id' => $freelancer->user_id,
                    'name' => $freelancer->brand_name,
                    'logo' => $freelancer->brand_logo ? asset('storage/' . $freelancer->brand_logo) : asset('assets/images/sellers/3.png'),
                    'location' => $freelancer->location ? $freelancer->location->municipality . ', Cavite' : 'Location not specified',
                    'location_id' => $freelancer->location_id,
                    'starting_price' => number_format($freelancer->starting_price, 2),
                    'description' => $freelancer->tagline,
                    'type_label' => 'Freelancer',
                    'rating' => $averageRating,
                    'total_ratings' => $freelancer->ratings_count,
                    'rating_display' => $this->getRatingDisplay($averageRating, $freelancer->ratings_count)
                ];
            }
        }

        // Sort results by rating if requested
        if ($request->input('sort_by') === 'rating') {
            usort($results, function($a, $b) {
                return $b['rating'] <=> $a['rating'];
            });
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results)
        ]);
    }

    /**
     * Helper function to generate rating display HTML.
     */
    private function getRatingDisplay($averageRating, $totalRatings)
    {
        $fullStars = floor($averageRating);
        $halfStar = ($averageRating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;
        
        $stars = '';
        
        // Full stars
        for ($i = 0; $i < $fullStars; $i++) {
            $stars .= '<i class="ti ti-star-filled fs-6"></i>';
        }
        
        // Half star
        if ($halfStar) {
            $stars .= '<i class="ti ti-star-half-filled fs-6"></i>';
        }
        
        // Empty stars
        for ($i = 0; $i < $emptyStars; $i++) {
            $stars .= '<i class="ti ti-star fs-6"></i>';
        }
        
        return [
            'stars' => $stars,
            'display' => number_format($averageRating, 1) . ' (' . $totalRatings . ' ' . ($totalRatings == 1 ? 'review' : 'reviews') . ')'
        ];
    }
}