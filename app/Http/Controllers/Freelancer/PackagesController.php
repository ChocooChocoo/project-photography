<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Freelancer\PackageStoreRequest;
use App\Models\Freelancer\PackagesModel;
use App\Models\Admin\CategoriesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PackagesController extends Controller
{
    /**
     * Display a listing of packages.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get active categories for filter dropdown
        $categories = CategoriesModel::where('status', 'active')
            ->select('id', 'category_name')
            ->get();

        // Get packages for the current freelancer
        $packages = PackagesModel::with('category')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('freelancer.view-packages', compact('packages', 'categories'));
    }

    /**
     * Show the form for creating a new package.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Get active categories for dropdown
        $categories = CategoriesModel::where('status', 'active')->get();
        
        return view('freelancer.create-packages', compact('categories'));
    }

    /**
     * Store a newly created package in storage.
     *
     * @param  PackageStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PackageStoreRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Add user_id to the validated data
            $validated['user_id'] = Auth::id();
            
            // Ensure online_gallery is properly handled as boolean
            if ($request->has('online_gallery')) {
                $validated['online_gallery'] = filter_var($request->online_gallery, FILTER_VALIDATE_BOOLEAN);
            } else {
                $validated['online_gallery'] = false;
            }

            // Handle duration based on time customization
            $validated['allow_time_customization'] = filter_var($request->allow_time_customization, FILTER_VALIDATE_BOOLEAN);
            
            if ($validated['allow_time_customization']) {
                unset($validated['duration']);
            } else {
                if (!$request->has('duration') || empty($request->duration)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duration is required when time customization is not allowed.'
                    ], 422);
                }
                $validated['duration'] = $request->duration;
            }

            // ==== NEW: Handle multiple locations fields ====
            // Ensure allow_multiple_locations is properly set as boolean
            if ($request->has('allow_multiple_locations')) {
                $validated['allow_multiple_locations'] = filter_var($request->allow_multiple_locations, FILTER_VALIDATE_BOOLEAN);
            } else {
                $validated['allow_multiple_locations'] = false;
            }
            
            // Handle max_locations based on allow_multiple_locations
            if ($validated['allow_multiple_locations']) {
                // Validate max_locations is provided and within range
                if (!$request->has('max_locations') || empty($request->max_locations)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maximum locations is required when multiple locations is enabled.'
                    ], 422);
                }
                
                // Ensure max_locations is within 1-10
                $maxLocations = (int)$request->max_locations;
                if ($maxLocations < 1 || $maxLocations > 10) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maximum locations must be between 1 and 10.'
                    ], 422);
                }
                
                $validated['max_locations'] = $maxLocations;
            } else {
                // If multiple locations not allowed, set max_locations to null
                $validated['max_locations'] = null;
            }
            // ==== END: New multiple locations handling ====
            
            // Log final data before insert (remove in production)
            \Log::info('Package creation - Final data:', $validated);
            
            // Create package
            $package = PackagesModel::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Package created successfully!',
                'data' => $package
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create package. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display packages list.
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
        // Get active categories for filter dropdown
        $categories = CategoriesModel::where('status', 'active')
            ->select('id', 'category_name')
            ->get();

        // Get ACTIVE packages for the current freelancer with category
        $packages = PackagesModel::with('category')
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('freelancer.packages-list', compact('packages', 'categories'));
    }

    /**
     * Get packages data for DataTable.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPackages(Request $request)
    {
        $query = PackagesModel::with('category')
            ->where('user_id', Auth::id())
            ->select('tbl_freelancer_packages.*');

        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('package_name', 'like', "%{$search}%")
                  ->orWhere('package_description', 'like', "%{$search}%")
                  ->orWhere('coverage_scope', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Total records
        $totalRecords = $query->count();

        // Ordering
        if ($request->has('order')) {
            $columns = ['category_id', 'package_name', 'package_price', 'duration', 'maximum_edited_photos', 'status'];
            $orderColumn = $columns[$request->order[0]['column']] ?? 'id';
            $orderDirection = $request->order[0]['dir'] ?? 'desc';
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $packages = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data = $packages->map(function ($package) {
            return [
                'category' => $package->category->category_name ?? 'N/A',
                'package_name' => $package->package_name,
                'price' => 'PHP ' . number_format($package->package_price, 2),
                'duration' => $package->duration . ' hours',
                'max_photos' => $package->maximum_edited_photos,
                'locations' => $package->allow_multiple_locations 
                    ? '<span class="badge badge-soft-success"><i class="ti ti-map-pin-check me-1"></i> Up to ' . $package->max_locations . ' locations</span>'
                    : '<span class="badge badge-soft-secondary"><i class="ti ti-map-pin me-1"></i> Single location</span>',
                'status' => $package->status === 'active' 
                    ? '<span class="badge badge-soft-success fs-8 px-1 w-100">ACTIVE</span>'
                    : '<span class="badge badge-soft-danger fs-8 px-1 w-100">INACTIVE</span>',
                'actions' => '
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-edit" data-id="' . $package->id . '">
                            <i class="ti ti-edit fs-lg"></i>
                        </button>
                        <button class="btn btn-sm btn-delete" data-id="' . $package->id . '">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                '
            ];
        });

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    /**
     * Get active categories for dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = CategoriesModel::where('status', 'active')
            ->select('id', 'category_name')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Remove the specified package from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $package = PackagesModel::where('user_id', Auth::id())
                ->where('id', $id)
                ->firstOrFail();
            
            $package->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found or you do not have permission to delete it.'
            ], 404);
        }
    }

    /**
     * Display the specified package.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $package = PackagesModel::with('category')
                ->where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$package) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not found or you do not have permission to view it.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $package
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading package details.'
            ], 500);
        }
    }
}