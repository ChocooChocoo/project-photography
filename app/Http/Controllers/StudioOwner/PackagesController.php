<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StudioOwner\PackageStoreRequest;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\Admin\CategoriesModel;
use Illuminate\Support\Facades\Auth;

class PackagesController extends Controller
{
    /**
     * Display a listing of packages.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get packages for the current user
        $packages = PackagesModel::with(['studio', 'category'])
            ->whereHas('studio', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('owner.view-packages', compact('packages'));
    }

    /**
     * Show the form for creating a new package.
     */
    public function create()
    {
        $userId = Auth::id();
        
        // Get verified studios for the current user
        $studios = StudiosModel::where('user_id', $userId)
            ->where('status', 'verified')
            ->get();

        // Get all active categories
        $categories = CategoriesModel::where('status', 'active')->get();

        return view('owner.create-packages', compact('studios', 'categories'));
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(PackageStoreRequest $request)
    {
        try {
            // Check if studio belongs to current user
            $studio = StudiosModel::where('id', $request->studio_id)
                ->where('user_id', Auth::id())
                ->where('status', 'verified')
                ->firstOrFail();

            // Get validated data from the request
            $validatedData = $request->validated();

            // ==== Start: Handle package_inclusions conversion ====
            // Convert JSON string to array if needed
            if (isset($validatedData['package_inclusions']) && is_string($validatedData['package_inclusions'])) {
                $decoded = json_decode($validatedData['package_inclusions'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $validatedData['package_inclusions'] = $decoded;
                }
            }
            // ==== End: Handle package_inclusions conversion ====

            // ==== Start: Ensure package_location is stored as JSON ====
            // The package_location is already an array from validation
            // Cast will handle JSON conversion automatically
            // ==== End: Ensure package_location is stored as JSON ====

            // Create package with all validated data
            $package = PackagesModel::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Package created successfully!',
                'data' => $package
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create package. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified package.
     */
    public function list()
    {
        $userId = Auth::id();
        
        // Get packages for the current user
        $packages = PackagesModel::with(['studio', 'category'])
            ->whereHas('studio', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all active categories for the filter dropdown
        $categories = \App\Models\Admin\CategoriesModel::where('status', 'active')->get();

        return view('owner.packages-list', compact('packages', 'categories'));
    }

    /**
     * Display the specified package.
     */
    public function show($id)
    {
        try {
            $userId = Auth::id();
            
            // Get package with relationships, ensure it belongs to current user
            $package = PackagesModel::with(['studio', 'category'])
                ->whereHas('studio', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $package
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found or you do not have permission to view it.'
            ], 404);
        }
    }
}