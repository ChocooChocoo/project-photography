<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\ServiceRequest;
use App\Models\StudioOwner\ServicesModel;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index()
    {
        return view('owner.view-services');
    }

    /**
     * Show the form for creating a new service.
     */
    public function create()
    {
        // Get verified studios owned by the current user
        $verifiedStudios = StudiosModel::where('user_id', auth()->id())
            ->where('status', 'verified')
            ->get(['id', 'studio_name']);
        
        // Get active categories
        $categories = CategoriesModel::where('status', 'active')
            ->get(['id', 'category_name']);

        return view('owner.create-services', compact('verifiedStudios', 'categories'));
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(ServiceRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Store service names as JSON
            $service = ServicesModel::create([
                'studio_id' => $validated['studio_id'],
                'category_id' => $validated['category_id'],
                'service_name' => json_encode($validated['service_name']), // Store as JSON
                'starting_from' => $validated['starting_from'] ?? null,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Service created successfully!',
                'data' => $service
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified service.
     */
    public function show($id)
    {
        try {
            $service = ServicesModel::with(['studio', 'category'])
                ->whereHas('studio', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->findOrFail($id);
            
            // Decode JSON service names
            $service->service_names_array = $service->service_name ? json_decode($service->service_name, true) : [];
            
            return response()->json([
                'success' => true,
                'data' => $service
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.'
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit($id)
    {
        try {
            $service = ServicesModel::with(['studio', 'category'])
                ->whereHas('studio', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->findOrFail($id);
            
            // Get service names as array
            $service->service_names_array = $service->service_name ? json_decode($service->service_name, true) : [];
            
            // Get verified studios owned by the current user
            $verifiedStudios = StudiosModel::where('user_id', auth()->id())
                ->where('status', 'verified')
                ->get(['id', 'studio_name']);
            
            // Get active categories
            $categories = CategoriesModel::where('status', 'active')
                ->get(['id', 'category_name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'studios' => $verifiedStudios,
                    'categories' => $categories
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.'
            ], 404);
        }
    }

    /**
     * Update the specified service in storage.
     */
    public function update(ServiceRequest $request, $id)
    {
        try {
            $service = ServicesModel::whereHas('studio', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->findOrFail($id);
            
            $validated = $request->validated();
            
            $service->update([
                'studio_id' => $validated['studio_id'],
                'category_id' => $validated['category_id'],
                'service_name' => json_encode($validated['service_name']), // Update as JSON
                'starting_from' => $validated['starting_from'] ?? null,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully!',
                'data' => $service
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy($id)
    {
        try {
            $service = ServicesModel::whereHas('studio', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->findOrFail($id);
            
            $service->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get services data for DataTable.
     */
    public function getServices(Request $request)
    {
        try {
            $services = ServicesModel::with(['studio', 'category'])
                ->whereHas('studio', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->select('tbl_services.*')
                ->orderBy('tbl_services.created_at', 'desc')
                ->get();
            
            // Decode JSON service names for each service
            $services->transform(function ($service) {
                $service->service_names_array = $service->service_name ? json_decode($service->service_name, true) : [];
                return $service;
            });
            
            return response()->json([
                'success' => true,
                'data' => $services
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}