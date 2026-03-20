<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\RoleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        return view('owner.view-roles');
    }

    /**
     * Get roles data for DataTable.
     */
    public function getRoles(Request $request)
    {
        $query = RoleModel::query();

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

        $roles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        // Transform data for response
        $roles->getCollection()->transform(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'status' => $role->status,
                'permissions_count' => $role->permissions()->count(),
                'users_count' => $role->users()->count(),
                'created_at' => $role->created_at ? $role->created_at->format('M d, Y h:i A') : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:tbl_roles,name',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        DB::beginTransaction();

        try {
            $role = RoleModel::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'data' => $role
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show($id)
    {
        $role = RoleModel::with(['permissions' => function($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'status' => $role->status,
                'permissions' => $role->permissions->map(function($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                    ];
                }),
                'created_at' => $role->created_at ? $role->created_at->format('M d, Y h:i A') : 'N/A',
                'updated_at' => $role->updated_at ? $role->updated_at->format('M d, Y h:i A') : 'N/A',
            ]
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:tbl_roles,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        DB::beginTransaction();

        try {
            $role = RoleModel::findOrFail($id);
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $role
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update role permissions.
     */
    public function updatePermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:tbl_permissions,id',
        ]);

        DB::beginTransaction();

        try {
            $role = RoleModel::findOrFail($id);
            $role->permissions()->sync($request->permissions);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role permissions updated successfully.',
                'data' => [
                    'permissions_count' => $role->permissions()->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update role permissions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $role = RoleModel::findOrFail($id);
            
            // Check if role has users assigned
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that has users assigned to it.'
                ], 422);
            }
            
            $role->permissions()->detach();
            $role->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle role status.
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();

        try {
            $role = RoleModel::findOrFail($id);
            $newStatus = $role->status === 'active' ? 'inactive' : 'active';
            $role->update(['status' => $newStatus]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role status updated successfully.',
                'data' => ['status' => $newStatus]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to toggle role status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role status: ' . $e->getMessage()
            ], 500);
        }
    }
}