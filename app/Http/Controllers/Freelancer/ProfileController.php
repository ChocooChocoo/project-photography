<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Freelancer\ProfileSetupRequest;
use App\Models\Freelancer\ProfileModel;
use App\Models\Freelancer\FreelancerScheduleModel;
use App\Models\Freelancer\FreelancerCategoryModel;
use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the freelancer profile.
     */
    public function index()
    {
        $user = auth()->user();
        $profile = ProfileModel::with(['categories', 'services', 'schedule', 'location'])
            ->where('user_id', $user->id)
            ->first();

        if (!$profile) {
            return redirect()->route('freelancer.profile.setup')
                ->with('info', 'Please complete your profile setup first.');
        }

        return view('freelancer.view-profile', compact('profile'));
    }

    /**
     * Show the profile setup form.
     */
    public function setup()
    {
        $user = auth()->user();
        
        // Check if profile already exists
        $existingProfile = ProfileModel::where('user_id', $user->id)->first();
        if ($existingProfile) {
            return redirect()->route('freelancer.profile.index')
                ->with('info', 'Your profile is already setup.');
        }

        $categories = CategoriesModel::where('status', 'active')->get();
        $locations = LocationModel::where('status', 'active')->get();
        $municipalities = $locations->pluck('municipality')->unique();

        return view('freelancer.setup-profile', compact('categories', 'municipalities'));
    }

    /**
     * Store the freelancer profile.
     */
    public function store(ProfileSetupRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('profile-photos', 'public');
                $user->profile_photo = $profilePicturePath;
                $user->save();
            }

            // Update mobile number if changed
            if ($request->freelancer_mobile_number !== $user->mobile_number) {
                $user->mobile_number = $request->freelancer_mobile_number;
                $user->save();
            }

            // Get location based on municipality
            $location = LocationModel::where('municipality', $request->municipality)->first();
            
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected municipality not found.'
                ], 422);
            }
            
            $selectedBarangay = $request->barangay;

            // Handle brand logo upload
            $brandLogoPath = null;
            if ($request->hasFile('brand_logo')) {
                $brandLogoPath = $request->file('brand_logo')->store('brand-logos', 'public');
            }

            // Handle portfolio works upload
            $portfolioWorks = [];
            if ($request->hasFile('portfolios')) {
                foreach ($request->file('portfolios') as $portfolio) {
                    $portfolioPath = $portfolio->store('portfolio-works', 'public');
                    $portfolioWorks[] = $portfolioPath;
                }
            }

            // Handle valid ID upload
            $validIdPath = null;
            if ($request->hasFile('freelancer_id_document')) {
                $validIdPath = $request->file('freelancer_id_document')->store('valid-ids', 'public');
            }

            // Create freelancer profile
            $profileData = [
                'user_id' => $user->id,
                'location_id' => $location->id,
                'brand_name' => $request->brand_name,
                'tagline' => $request->professional_tagline,
                'bio' => $request->bio,
                'contract_template' => $request->contract_template,
                'years_experience' => $request->years_of_experience,
                'brand_logo' => $brandLogoPath,
                'street' => $request->street,
                'barangay' => $selectedBarangay,
                'service_area' => $request->service_area,
                'starting_price' => $request->starting_price,
                'deposit_policy' => $request->deposit_policy,
                // ==== Start: Deposit Policy Enhancement ==== //
                'deposit_type' => $request->deposit_policy === 'required' ? $request->deposit_type : null,
                'deposit_amount' => $request->deposit_policy === 'required' ? $request->deposit_amount : null,
                // ==== End: Deposit Policy Enhancement ==== //
                'portfolio_works' => !empty($portfolioWorks) ? json_encode($portfolioWorks) : null,
                'facebook_url' => $request->facebook_url,
                'instagram_url' => $request->instagram_url,
                'website_url' => $request->website_url,
                'valid_id' => $validIdPath,
            ];

            $profile = ProfileModel::create($profileData);

            // Create freelancer schedule
            FreelancerScheduleModel::create([
                'user_id' => $user->id,
                'operating_days' => json_encode($request->operating_days),
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'booking_limit' => $request->max_clients_per_day,
                'advance_booking' => $request->advance_booking_days,
            ]);

            // Attach categories
            if ($request->has('category_services')) {
                foreach ($request->category_services as $categoryId) {
                    FreelancerCategoryModel::create([
                        'user_id' => $user->id,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile setup completed successfully!',
                'redirect' => route('freelancer.profile.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving your profile. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get barangays for selected municipality.
     */
    public function getBarangays(Request $request)
    {
        $request->validate([
            'municipality' => 'required|exists:tbl_locations,municipality'
        ]);

        $location = LocationModel::active()
            ->where('municipality', $request->municipality)
            ->orderBy('id')
            ->first();

        if (! $location) {
            return response()->json(['barangays' => [], 'zip_code' => null]);
        }

        $barangays = is_array($location->barangay)
            ? $location->barangay
            : (json_decode((string) $location->barangay, true) ?: []);

        $barangays = collect($barangays)
            ->filter(fn ($barangay) => is_string($barangay) && $barangay !== '')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return response()->json([
            'barangays' => $barangays,
            'zip_code' => $location->zip_code,
        ]);
    }
}