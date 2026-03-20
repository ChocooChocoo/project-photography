<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\PermissionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        return view('owner.view-permissions');
    }

    /**
     * Get permissions data for DataTable.
     */
    public function getPermissions(Request $request)
    {
        $query = PermissionModel::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        // Transform data for response
        $permissions->getCollection()->transform(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'status' => $permission->status,
                'roles_count' => $permission->roles()->count(),
                'created_at' => $permission->created_at ? $permission->created_at->format('M d, Y h:i A') : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Get all permissions (for role assignment).
     */
    public function getAllPermissions()
    {
        $permissions = PermissionModel::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:tbl_permissions,name',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        DB::beginTransaction();

        try {
            $permission = PermissionModel::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully.',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified permission.
     */
    public function show($id)
    {
        $permission = PermissionModel::with(['roles' => function($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'status' => $permission->status,
                'roles' => $permission->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                    ];
                }),
                'created_at' => $permission->created_at ? $permission->created_at->format('M d, Y h:i A') : 'N/A',
                'updated_at' => $permission->updated_at ? $permission->updated_at->format('M d, Y h:i A') : 'N/A',
            ]
        ]);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:tbl_permissions,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        DB::beginTransaction();

        try {
            $permission = PermissionModel::findOrFail($id);
            $permission->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully.',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $permission = PermissionModel::findOrFail($id);
            
            // Check if permission is assigned to any role
            if ($permission->roles()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission that is assigned to roles.'
                ], 422);
            }
            
            $permission->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle permission status.
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();

        try {
            $permission = PermissionModel::findOrFail($id);
            $newStatus = $permission->status === 'active' ? 'inactive' : 'active';
            $permission->update(['status' => $newStatus]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission status updated successfully.',
                'data' => ['status' => $newStatus]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to toggle permission status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission status: ' . $e->getMessage()
            ], 500);
        }
    }
}