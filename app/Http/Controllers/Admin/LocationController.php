<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Admin\LocationModel;
use App\Http\Requests\Admin\LocationStoreRequest;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index()
    {
        $locations = LocationModel::latest()->get();
        return view('admin.view-locations', compact('locations'));
    }

    /**
     * Show the form for creating a new location.
     */
    public function create()
    {
        return view('admin.create-locations');
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(LocationStoreRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Create location
            $location = LocationModel::create([
                'province' => $validated['province'],
                'municipality' => $validated['municipality'],
                'barangay' => json_encode($validated['barangay']),
                'zip_code' => $validated['zip_code'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location created successfully!',
                'data' => $location,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create location. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified location.
     */
    public function show($id): JsonResponse
    {
        try {
            $location = LocationModel::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $location->id,
                    'province' => $location->province,
                    'municipality' => $location->municipality,
                    'barangays' => $location->barangays,
                    'zip_code' => $location->zip_code,
                    'status' => $location->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found.',
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit($id)
    {
        $location = LocationModel::findOrFail($id);
        return view('admin.edit-locations', compact('location'));
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'municipality' => ['required', 'string', 'max:255', Rule::unique('tbl_locations', 'municipality')->whereNull('deleted_at')->ignore($id)],
                'barangay' => 'required|array|min:1',
                'barangay.*' => 'required|string|max:255',
                'zip_code' => ['required', 'string', 'max:10', Rule::unique('tbl_locations', 'zip_code')->whereNull('deleted_at')->ignore($id)],
                'status' => 'required|in:active,inactive',
            ]);

            $location = LocationModel::findOrFail($id);
            
            // Filter out empty barangay values
            $barangays = array_filter($request->input('barangay'), function ($value) {
                return !empty(trim($value));
            });

            $location->update([
                'municipality' => $request->input('municipality'),
                'barangay' => json_encode(array_values($barangays)),
                'zip_code' => $request->input('zip_code'),
                'status' => $request->input('status'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully!',
                'data' => $location,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $location = LocationModel::findOrFail($id);
            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all locations for DataTable.
     */
    public function getLocations(): JsonResponse
    {
        $locations = LocationModel::select(['id', 'province', 'municipality', 'barangay', 'zip_code', 'status', 'created_at'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $locations,
        ]);
    }
}