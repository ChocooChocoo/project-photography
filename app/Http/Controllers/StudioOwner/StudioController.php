<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudioCategoryModel;
use App\Models\Admin\LocationModel;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\UserModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioPlanModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudioController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $studios = StudiosModel::with(['user', 'location', 'category', 'categories', 'schedules'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('owner.view-studio', compact('studios'));
    }

    public function create()
    {
        $categories = CategoriesModel::where('status', 'active')->get();
        $user = Auth::user();
        $municipalities = LocationModel::select('municipality')->distinct()->pluck('municipality');
        
        return view('owner.create-studio', compact('categories', 'user', 'municipalities'));
    }

    /**
     * Get barangays for a specific municipality
     */
    public function getBarangays($municipality)
    {
        $location = LocationModel::where('municipality', $municipality)->first();
        
        if (!$location) {
            return response()->json(['barangays' => [], 'zip_code' => null]);
        }
        
        // Get barangays array from the location
        $barangays = $location->barangay;
        
        // Handle JSON string or array
        if (is_string($barangays)) {
            $barangaysArray = json_decode($barangays, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not valid JSON, treat as a single item array
                $barangaysArray = [$barangays];
            }
        } else {
            $barangaysArray = $barangays;
        }
        
        // Ensure it's an array
        $barangaysArray = is_array($barangaysArray) ? $barangaysArray : [];
        
        return response()->json([
            'barangays' => $barangaysArray,
            'zip_code' => $location->zip_code
        ]);
    }

    /**
     * Store a new studio
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Debug: Log incoming request data
            \Log::info('Studio Registration Request Data:', $request->all());

            // Double-check subscription status before processing
            $user = Auth::user();
            $studioCount = StudiosModel::where('user_id', $user->id)->count();

            // Get all studio IDs owned by this user
            $userStudioIds = StudiosModel::where('user_id', $user->id)->pluck('id')->toArray();

            // If this is not the first studio, verify subscription
            if ($studioCount >= 1) {
                // Check if ANY of the user's studios have an active subscription
                $activeSubscription = null;
                if (!empty($userStudioIds)) {
                    $activeSubscription = StudioPlanModel::whereIn('studio_id', $userStudioIds)
                        ->with('plan')
                        ->where('status', 'active')
                        ->where('payment_status', 'paid')
                        ->where('end_date', '>=', now()->toDateString())
                        ->latest()           // most recent active one
                        ->first();
                }

                \Log::info('Store Method - Subscription Check', [
                    'studio_count'     => $studioCount,
                    'has_subscription' => $activeSubscription ? true : false,
                    'active_plan_id'   => $activeSubscription?->id ?? null,
                ]);

                if (!$activeSubscription) {
                    DB::rollBack();
                    return response()->json([
                        'success'     => false,
                        'message'     => 'You need an active subscription plan to register multiple studios.',
                        'alert_color' => '#DC3545',
                        'redirect'    => route('owner.subscription.index')
                    ], 403);
                }

                // Check plan limits
                if ($activeSubscription && $activeSubscription->plan) {
                    $maxStudios = $activeSubscription->plan->max_studios;

                    if ($maxStudios !== null && $studioCount >= $maxStudios) {
                        DB::rollBack();
                        return response()->json([
                            'success'     => false,
                            'message'     => "Your subscription plan only allows up to {$maxStudios} studio(s).",
                            'alert_color' => '#DC3545'
                        ], 403);
                    }
                }
            }

            // Use the rules() method instead of $rules property
            $validatedData = $request->validate(StudiosModel::rules());

            // Debug: Log validated data
            \Log::info('Validated Data:', $validatedData);

            // Handle file uploads
            $studioLogoPath = $this->uploadFile($request->file('studio_logo'), 'studio_logo');
            $businessPermitPath = $this->uploadFile($request->file('business_permit'), 'studio_documents');
            $ownerIdPath = $this->uploadFile($request->file('owner_id_document'), 'studio_documents');
            $ownerProfilePhotoPath = $this->uploadFile($request->file('owner_profile_photo'), 'profile_photos');

            // Update user's profile photo if uploaded
            if ($ownerProfilePhotoPath) {
                // Delete old profile photo if exists
                if ($user->profile_photo) {
                    $this->deleteFile($user->profile_photo);
                }

                // Update user's profile photo
                $user->update([
                    'profile_photo' => $ownerProfilePhotoPath
                ]);

                \Log::info('Owner profile photo updated', [
                    'user_id' => $user->id,
                    'path'    => $ownerProfilePhotoPath
                ]);
            }

            // Find location based on municipality and barangay
            $municipality = $validatedData['municipality'];
            $barangay = $validatedData['barangay'];

            // Debug: Log location search parameters
            \Log::info('Location Search:', [
                'municipality' => $municipality,
                'barangay'     => $barangay
            ]);

            // First, find the location by municipality
            $location = LocationModel::where('municipality', $municipality)->first();

            if (!$location) {
                // Debug: Municipality not found
                \Log::warning('Municipality not found: ' . $municipality);

                DB::rollBack();

                return response()->json([
                    'success'     => false,
                    'message'     => 'The selected municipality does not exist in our system.',
                    'errors'      => [
                        'municipality' => ['The selected municipality is not available in our system.']
                    ],
                    'alert_color' => '#DC3545'
                ], 422);
            }

            // Check if barangay exists in the location's barangay array
            $barangays = $location->barangay;

            // Handle both JSON string and array formats
            if (is_string($barangays)) {
                $barangaysArray = json_decode($barangays, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $barangaysArray = [$barangays];
                }
            } else {
                $barangaysArray = $barangays;
            }

            // Check if barangay exists in the array
            $barangayExists = false;
            if (is_array($barangaysArray)) {
                $barangayExists = in_array($barangay, $barangaysArray);

                // Debug: Barangay check
                \Log::info('Barangay Check:', [
                    'barangay_to_find'    => $barangay,
                    'available_barangays' => $barangaysArray,
                    'exists'              => $barangayExists
                ]);
            }

            if (!$barangayExists) {
                // Debug: Barangay not found
                \Log::warning('Barangay not found in location: ' . $barangay . ' for municipality: ' . $municipality);

                DB::rollBack();

                return response()->json([
                    'success'     => false,
                    'message'     => 'The selected barangay does not exist in our system for this municipality.',
                    'errors'      => [
                        'barangay' => ['The selected barangay is not available for this municipality.']
                    ],
                    'alert_color' => '#DC3545'
                ], 422);
            }

            // Create studio
            $studioData = [
                'user_id'                => $user->id,
                'category_id'            => $validatedData['service_categories'][0] ?? null,
                'location_id'            => $location->id,
                'street'                 => $validatedData['street'],
                'barangay'               => $validatedData['barangay'],
                'attendance_latitude'    => $validatedData['attendance_latitude'],
                'attendance_longitude'   => $validatedData['attendance_longitude'],
                'attendance_radius_meters' => $validatedData['attendance_radius_meters'],
                'contact_number'         => $validatedData['contact_number'],
                'studio_email'           => $validatedData['studio_email'],
                'facebook_url'           => $validatedData['facebook_url'] ?? null,
                'instagram_url'          => $validatedData['instagram_url'] ?? null,
                'website_url'            => $validatedData['website_url'] ?? null,
                'studio_name'            => $validatedData['studio_name'],
                'studio_type'            => $validatedData['studio_type'],
                'year_established'       => $validatedData['year_established'],
                'studio_description'     => $validatedData['studio_description'],
                'studio_logo'            => $studioLogoPath,
                'starting_price'         => $validatedData['starting_price'],
                'downpayment_percentage' => $validatedData['downpayment_percentage'] ?? 30.00,
                'operating_days'         => json_encode($validatedData['operating_days']),
                'start_time'             => $validatedData['start_time'],
                'end_time'               => $validatedData['end_time'],
                'max_clients_per_day'    => $validatedData['max_clients_per_day'],
                'advance_booking_days'   => $validatedData['advance_booking_days'],
                'business_permit'        => $businessPermitPath,
                'owner_id_document'      => $ownerIdPath,
                'status'                 => 'pending',
            ];

            // Debug: Studio data to be created
            \Log::info('Studio Data to Create:', $studioData);

            $studio = StudiosModel::create($studioData);

            $ownerRole = RoleModel::where('name', 'owner-super-admin')->first();
            if ($ownerRole) {
                $user->assignRole($ownerRole, $studio->id);
            }

            // Create studio schedule
            $this->createStudioSchedule($studio, $location, $validatedData);

            // Create studio category pivots (multiple categories)
            $this->createStudioCategories($studio, $user, $validatedData['service_categories']);

            DB::commit();

            // Debug: Success
            \Log::info('Studio Registration Successful:', ['studio_id' => $studio->id]);

            return response()->json([
                'success'     => true,
                'message'     => 'Studio registration submitted successfully! It will be reviewed by admin.',
                'alert_color' => '#007BFF', // Blue for success
                'redirect'    => route('owner.studio.index')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            // Debug: Validation error
            \Log::error('Studio Registration Validation Error:', $e->errors());

            return response()->json([
                'success'     => false,
                'message'     => 'Validation failed.',
                'errors'      => $e->errors(),
                'alert_color' => '#DC3545' // Red for error
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            // Debug: General error
            \Log::error('Studio Registration Error:', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success'     => false,
                'message'     => 'Failed to register studio: ' . $e->getMessage(),
                'alert_color' => '#DC3545' // Red for error
            ], 500);
        }
    }

    /**
     * Create studio schedule record.
     */
    private function createStudioSchedule($studio, $location, $validatedData)
    {
        return StudioScheduleModel::create([
            'studio_id' => $studio->id,
            'location_id' => $location->id,
            'operating_days' => json_encode($validatedData['operating_days']),
            'opening_time' => $validatedData['start_time'],
            'closing_time' => $validatedData['end_time'],
            'booking_limit' => $validatedData['max_clients_per_day'],
            'advance_booking' => $validatedData['advance_booking_days'],
        ]);
    }

    /**
     * Create studio category pivot records.
     */
    private function createStudioCategories($studio, $user, $categoryIds)
    {
        $studioCategories = [];
        
        foreach ($categoryIds as $categoryId) {
            $studioCategories[] = [
                'user_id' => $user->id,
                'studio_id' => $studio->id,
                'category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Use insert for bulk insertion
        StudioCategoryModel::insert($studioCategories);
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $studio = StudiosModel::where('id', $id)->where('user_id', $user->id)->firstOrFail();
            
            // Delete files
            $this->deleteFile($studio->studio_logo);
            $this->deleteFile($studio->business_permit);
            $this->deleteFile($studio->owner_id_document);
            
            // Delete studio
            $studio->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Studio registration cancelled successfully.',
                'alert_color' => '#007BFF' // Blue for success
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel studio: ' . $e->getMessage(),
                'alert_color' => '#DC3545' // Red for error
            ], 500);
        }
    }

    private function uploadFile($file, $folder)
    {
        if (!$file) {
            return null;
        }
        
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Create directory if it doesn't exist
        $directory = "{$folder}"; // e.g., "studio_logo" or "studio_documents"
        
        // Ensure the directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }
        
        // Store the file
        $path = $file->storeAs($directory, $fileName, 'public');
        
        return $path; // This will return: "studio_logo/filename.jpg" or "studio_documents/filename.pdf"
    }

    private function deleteFile($filePath)
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * Show the form for editing the specified studio.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $user = Auth::user();
        $studio = StudiosModel::with(['location', 'categories'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $categories = CategoriesModel::where('status', 'active')->get();
        $municipalities = LocationModel::select('municipality')->distinct()->pluck('municipality');
        
        return view('owner.edit-studio', compact('studio', 'categories', 'municipalities'));
    }

    /**
     * Update the specified studio in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $studio = StudiosModel::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Validate the request
            $rules = StudiosModel::rules();
            // Make certain fields optional for update
            $rules['studio_logo'] = 'nullable|image|mimes:jpg,jpeg,png|max:3072';
            $rules['business_permit'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:3072';
            $rules['owner_id_document'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:3072';
            
            $validatedData = $request->validate($rules);

            // Handle file uploads if new files are provided
            if ($request->hasFile('studio_logo')) {
                // Delete old logo
                $this->deleteFile($studio->studio_logo);
                $studioLogoPath = $this->uploadFile($request->file('studio_logo'), 'studio_logo');
                $studio->studio_logo = $studioLogoPath;
            }

            if ($request->hasFile('business_permit')) {
                $this->deleteFile($studio->business_permit);
                $businessPermitPath = $this->uploadFile($request->file('business_permit'), 'studio_documents');
                $studio->business_permit = $businessPermitPath;
            }

            if ($request->hasFile('owner_id_document')) {
                $this->deleteFile($studio->owner_id_document);
                $ownerIdPath = $this->uploadFile($request->file('owner_id_document'), 'studio_documents');
                $studio->owner_id_document = $ownerIdPath;
            }

            // Find location based on municipality and barangay
            $municipality = $validatedData['municipality'];
            $barangay = $validatedData['barangay'];

            $location = LocationModel::where('municipality', $municipality)->first();

            if (!$location) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'The selected municipality does not exist in our system.',
                    'errors' => ['municipality' => ['The selected municipality is not available.']],
                    'alert_color' => '#DC3545'
                ], 422);
            }

            // Verify barangay exists
            $barangays = $location->barangay;
            $barangaysArray = is_string($barangays) ? json_decode($barangays, true) ?? [$barangays] : $barangays;
            
            if (!in_array($barangay, $barangaysArray)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'The selected barangay does not exist for this municipality.',
                    'errors' => ['barangay' => ['The selected barangay is not available.']],
                    'alert_color' => '#DC3545'
                ], 422);
            }

            // Update studio fields
            $studio->fill([
                'category_id' => $validatedData['service_categories'][0] ?? null,
                'location_id' => $location->id,
                'street' => $validatedData['street'],
                'barangay' => $validatedData['barangay'],
                'attendance_latitude' => $validatedData['attendance_latitude'],
                'attendance_longitude' => $validatedData['attendance_longitude'],
                'attendance_radius_meters' => $validatedData['attendance_radius_meters'],
                'contact_number' => $validatedData['contact_number'],
                'studio_email' => $validatedData['studio_email'],
                'facebook_url' => $validatedData['facebook_url'] ?? null,
                'instagram_url' => $validatedData['instagram_url'] ?? null,
                'website_url' => $validatedData['website_url'] ?? null,
                'studio_name' => $validatedData['studio_name'],
                'studio_type' => $validatedData['studio_type'],
                'year_established' => $validatedData['year_established'],
                'studio_description' => $validatedData['studio_description'],
                'starting_price' => $validatedData['starting_price'],
                'downpayment_percentage' => $validatedData['downpayment_percentage'] ?? 30.00,
                'operating_days' => json_encode($validatedData['operating_days']),
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'max_clients_per_day' => $validatedData['max_clients_per_day'],
                'advance_booking_days' => $validatedData['advance_booking_days'],
            ]);

            $studio->save();

            // Update studio schedule
            $schedule = StudioScheduleModel::where('studio_id', $studio->id)->first();
            if ($schedule) {
                $schedule->update([
                    'location_id' => $location->id,
                    'operating_days' => json_encode($validatedData['operating_days']),
                    'opening_time' => $validatedData['start_time'],
                    'closing_time' => $validatedData['end_time'],
                    'booking_limit' => $validatedData['max_clients_per_day'],
                    'advance_booking' => $validatedData['advance_booking_days'],
                ]);
            } else {
                $this->createStudioSchedule($studio, $location, $validatedData);
            }

            // Update studio categories
            StudioCategoryModel::where('studio_id', $studio->id)->delete();
            $this->createStudioCategories($studio, $user, $validatedData['service_categories']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Studio updated successfully!',
                'alert_color' => '#007BFF',
                'redirect' => route('owner.studio.index')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'alert_color' => '#DC3545'
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Studio Update Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update studio: ' . $e->getMessage(),
                'alert_color' => '#DC3545'
            ], 500);
        }
    }
}
