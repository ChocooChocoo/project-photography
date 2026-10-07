<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StudioOwner\StudioPhotographerRequest;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\UserModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\ServicesModel;
use App\Mail\PhotographerRegistrationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudioPhotographersController extends Controller
{
    public function index()
    {
        $ownerId = auth()->id();
        
        // Get studios owned by the current user
        $studios = StudiosModel::where('user_id', $ownerId)->get();
        
        // Get studio photographers for the current owner with service details
        $photographers = StudioPhotographersModel::with([
                'photographer',
                'studio',
                'specializationService.category' // Load service with category
            ])
            ->where('owner_id', $ownerId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($photographer) {
                // Get service name from specialization
                if ($photographer->specializationService) {
                    $photographer->service_name = $this->getServiceName($photographer->specializationService);
                    $photographer->category_name = $photographer->specializationService->category->category_name ?? 'Not specified';
                } else {
                    $photographer->service_name = 'Not specified';
                    $photographer->category_name = 'Not specified';
                }
                return $photographer;
            });
        
        return view('owner.view-studio-photographers', compact('studios', 'photographers'));
    }

    public function create()
    {
        $ownerId = auth()->id();
        
        // Get studios owned by the current user
        $studios = StudiosModel::where('user_id', $ownerId)->get();
        
        return view('owner.create-studio-photographers', compact('studios'));
    }

    /**
     * Get services for a specific studio - returns services grouped by category
     */
    public function getStudioServices($studioId)
    {
        $ownerId = auth()->id();
        
        // Verify the studio belongs to the owner
        $studio = StudiosModel::where('id', $studioId)
            ->where('user_id', $ownerId)
            ->firstOrFail();
        
        // Get DISTINCT categories from services for this studio
        $categories = ServicesModel::where('studio_id', $studioId)
            ->join('tbl_categories', 'tbl_services.category_id', '=', 'tbl_categories.id')
            ->select(
                'tbl_categories.id',
                'tbl_categories.category_name',
                DB::raw('COUNT(tbl_services.id) as services_count')
            )
            ->groupBy('tbl_categories.id', 'tbl_categories.category_name')
            ->orderBy('tbl_categories.category_name')
            ->get();
        
        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created studio photographer - SIMPLIFIED VERSION
     */
    public function store(StudioPhotographerRequest $request)
    {
        if ($request->status === 'active'
            && $message = \App\Services\StudioStaffLimit::violation((int) $request->studio_id)) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        DB::beginTransaction();
        
        try {
            $ownerId = auth()->id();
            $uuid = Str::uuid();
            
            // Generate password: role + uuid
            $password = 'studio-photographer' . $uuid;
            $temporaryPassword = $password; // Store for email
            
            // Create photographer user
            $photographerUser = UserModel::create([
                'uuid' => $uuid,
                'role' => 'studio-photographer',
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'user_type' => 'photographer',
                'email' => $request->email,
                'mobile_number' => $request->mobile_number,
                'password' => Hash::make($password),
                'profile_photo' => $this->handleProfilePhoto($request),
                'status' => $request->status,
                'email_verified' => 1,
                'verification_token' => null,
                'token_expiry' => null,
            ]);
            
            // Get selected category ID from the dropdown
            $categoryId = $request->specialization;
            
            // Find ONE service under this category for the selected studio
            $primaryService = ServicesModel::where('studio_id', $request->studio_id)
                ->where('category_id', $categoryId)
                ->first();
            
            if (!$primaryService) {
                throw new \Exception('No services found for the selected category.');
            }
            
            // Get studio info for email
            $studio = StudiosModel::find($request->studio_id);
            
            // Create studio photographer record - store the primary service ID as specialization
            $studioPhotographer = StudioPhotographersModel::create([
                'studio_id' => $request->studio_id,
                'owner_id' => $ownerId,
                'photographer_id' => $photographerUser->id,
                'position' => $request->position,
                'specialization' => $primaryService->id, // Store primary service ID
                'years_of_experience' => $request->years_experience,
                'status' => $request->status,
            ]);
            
            // REMOVED: Pivot table creation - no longer needed
            
            // Prepare data for email
            $photographerData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'position' => $request->position,
                'years_experience' => $request->years_experience,
                'status' => $request->status,
                'profile_photo' => $this->handleProfilePhoto($request),
                'studio_name' => $studio->studio_name ?? 'N/A',
                'specialization' => $request->specialization,
            ];
            
            // Send registration email
            $this->sendRegistrationEmail($photographerData, $temporaryPassword);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Studio photographer registered successfully! Login credentials have been emailed to ' . $request->email,
                'data' => [
                    'photographer_id' => $photographerUser->id
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error for debugging
            \Log::error('Failed to create studio photographer: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create studio photographer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get studio photographers list
     */
    public function getStudioPhotographers(Request $request)
    {
        $ownerId = auth()->id();
        
        $query = StudioPhotographersModel::with([
            'photographer',
            'studio',
            'specializationService.category'
        ])
        ->where('owner_id', $ownerId);
        
        // Filter by studio
        if ($request->filled('studio_id')) {
            $query->where('studio_id', $request->studio_id);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('photographer', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $photographers = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));
        
        // Format response
        $photographers->getCollection()->transform(function($photographer) {
            if ($photographer->specializationService) {
                $photographer->service_name = $this->getServiceName($photographer->specializationService);
                $photographer->category_name = $photographer->specializationService->category->category_name ?? 'Not specified';
            }
            return $photographer;
        });
        
        return response()->json([
            'success' => true,
            'data' => $photographers
        ]);
    }

    /**
     * Get photographer details - SIMPLIFIED VERSION
     */
    public function show($id)
    {
        $ownerId = auth()->id();
        
        $photographer = StudioPhotographersModel::with([
            'photographer',
            'studio',
            'specializationService.category'
            // REMOVED: 'services.category' - pivot table no longer exists
        ])
        ->where('id', $id)
        ->where('owner_id', $ownerId)
        ->firstOrFail();
        
        // Get service name for the specialization
        $serviceName = 'Not specified';
        $categoryName = 'Not specified';
        
        if ($photographer->specializationService) {
            $serviceName = $this->getServiceName($photographer->specializationService);
            $categoryName = $photographer->specializationService->category->category_name ?? 'Not specified';
        }
        
        return response()->json([
            'success' => true,
            'data' => $photographer,
            'service_name' => $serviceName,
            'category_name' => $categoryName
        ]);
    }

    /**
     * Handle profile photo upload
     */
    private function handleProfilePhoto($request)
    {
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile-photos', $filename, 'public');
            return $filename;
        }
        
        return null;
    }

    /**
     * Extract service name from service record
     */
    private function getServiceName($service)
    {
        if (!$service) {
            return 'Not specified';
        }

        if (is_array($service->service_name)) {
            return implode(', ', $service->service_name);
        } elseif (is_string($service->service_name)) {
            try {
                $decoded = json_decode($service->service_name, true);
                if (is_array($decoded)) {
                    return implode(', ', $decoded);
                }
                return $service->service_name;
            } catch (\Exception $e) {
                return $service->service_name;
            }
        }
        
        return 'Not specified';
    }

    /**
     * Send registration email to photographer
     *
     * @param array $photographerData
     * @param string $temporaryPassword
     * @return bool
     */
    private function sendRegistrationEmail(array $photographerData, string $temporaryPassword): bool
    {
        try {
            Mail::to($photographerData['email'])->send(
                new PhotographerRegistrationMail($photographerData, $temporaryPassword)
            );
            
            \Log::info('Registration email sent to photographer: ' . $photographerData['email']);
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send registration email: ' . $e->getMessage(), [
                'photographer_email' => $photographerData['email']
            ]);
            return false;
        }
    }
}