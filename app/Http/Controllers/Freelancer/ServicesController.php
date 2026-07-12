<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Freelancer\ServiceRequest;
use App\Models\Admin\CategoriesModel;
use App\Models\Freelancer\ServiceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get freelancer's services with category info
        $services = ServiceModel::with('category')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($service) {
                return (object) [
                    'id' => $service->id,
                    'category_id' => $service->category_id,
                    'category_name' => $service->category->category_name ?? 'N/A',
                    'services_name' => $service->services_name,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                ];
            });

        // Get ALL active categories for the filter dropdown
        $categories = CategoriesModel::where('status', 'active')
            ->orderBy('category_name')
            ->get();

        return view('freelancer.view-services', compact('services', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get active categories for dropdown
        $categories = CategoriesModel::where('status', 'active')
            ->orderBy('category_name')
            ->get();

        return view('freelancer.create-services', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceRequest $request)
    {
        try {
            DB::beginTransaction();

            // Check if freelancer already has services for this category
            $existingService = ServiceModel::where('user_id', auth()->id())
                ->where('category_id', $request->category_id)
                ->first();

            if ($existingService) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have services in this category. Please edit your existing services instead.'
                ], 422);
            }

            // Store in tbl_freelancer_services (now includes category_id)
            $service = ServiceModel::create([
                'user_id' => auth()->id(),
                'category_id' => $request->category_id,
                'services_name' => $request->service_name,
                'starting_from' => $request->starting_from,
            ]);

            // No longer need to store in pivot table since category_id is in main table
            // But if you still want to keep the pivot table for other purposes:
            DB::table('pvt_freelancer_categories')->updateOrInsert(
                [
                    'user_id' => auth()->id(),
                    'category_id' => $request->category_id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Services created successfully!',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create services. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $service = ServiceModel::with(['user', 'categories'])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $service = ServiceModel::with('categories')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $categories = CategoriesModel::where('status', 'active')
            ->orderBy('category_name')
            ->get();

        return view('freelancer.edit-services', compact('service', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceRequest $request, $id)
    {
        try {
            $service = ServiceModel::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            DB::beginTransaction();

            // Check if category is being changed
            $isCategoryChanged = $service->category_id != $request->category_id;

            if ($isCategoryChanged) {
                // Check if freelancer already has the new category
                $newCategoryExists = ServiceModel::where('user_id', auth()->id())
                    ->where('category_id', $request->category_id)
                    ->where('id', '!=', $id) // Exclude current service
                    ->exists();

                if ($newCategoryExists) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'You already have services in the selected category. Please choose a different category.'
                    ], 422);
                }

                // Update pivot table if needed
                DB::table('pvt_freelancer_categories')
                    ->where('user_id', auth()->id())
                    ->where('category_id', $service->category_id)
                    ->update([
                        'category_id' => $request->category_id,
                        'updated_at' => now(),
                    ]);
            }

            // Update services (now includes category_id)
            $service->update([
                'category_id' => $request->category_id,
                'services_name' => $request->service_name,
                'starting_from' => $request->starting_from,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Services updated successfully!',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update services. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $service = ServiceModel::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            DB::beginTransaction();

            $categoryId = $service->category_id;
            
            // Delete service
            $service->delete();

            // Check if any services left with this category for this user
            $remainingServicesWithCategory = ServiceModel::where('user_id', auth()->id())
                ->where('category_id', $categoryId)
                ->exists();
            
            // If no services left with this category, remove from pivot table
            if (!$remainingServicesWithCategory && $categoryId) {
                DB::table('pvt_freelancer_categories')
                    ->where('user_id', auth()->id())
                    ->where('category_id', $categoryId)
                    ->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
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
        $services = ServiceModel::with(['categories'])
            ->where('user_id', auth()->id())
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('category_id', $request->category);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'category' => $service->categories->first()->category_name ?? 'N/A',
                    'category_id' => $service->categories->first()->id ?? null,
                    'services' => implode(', ', array_slice($service->services_name, 0, 3)) . 
                                 (count($service->services_name) > 3 ? '...' : ''),
                    'services_full' => $service->services_name,
                    'created_at' => $service->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'data' => $services
        ]);
    }

    /**
     * Get active categories for dropdown.
     */
    public function getCategories()
    {
        $categories = CategoriesModel::where('status', 'active')
            ->orderBy('category_name')
            ->get(['id', 'category_name']);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}