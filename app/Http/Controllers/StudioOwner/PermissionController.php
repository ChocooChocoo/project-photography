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
        $query = PermissionModel::query()
            ->whereIn('portal', ['owner', 'studio-hr', 'studio-finance', 'studio-photographer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('resource', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('permission_string', 'like', "%{$search}%")
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
                'resource' => $permission->resource,
                'action' => $permission->action,
                'permission_string' => $permission->permission_string,
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
            ->whereIn('portal', ['owner', 'studio-hr', 'studio-finance', 'studio-photographer'])
            ->orderBy('permission_string')
            ->get(['id', 'name', 'resource', 'action', 'permission_string', 'description']);

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
            'resource' => 'required|string|max:100',
            'action' => 'required|string|max:50',
            'permission_string' => 'required|string|max:150|unique:tbl_permissions,permission_string',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ], [
            'resource.required' => 'The resource field is required.',
            'action.required' => 'The action field is required.',
            'permission_string.required' => 'The permission string is required.',
            'permission_string.unique' => 'The generated permission string already exists.',
            'status.required' => 'Please select a status.',
        ]);

        DB::beginTransaction();

        try {
            $permissionData = $this->preparePermissionData($request);

            $permission = PermissionModel::create([
                'name' => $permissionData['name'],
                'resource' => $permissionData['resource'],
                'action' => $permissionData['action'],
                'permission_string' => $permissionData['permission_string'],
                'description' => $request->description,
                'status' => $request->status,
                'portal' => $permissionData['portal'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Permission created successfully.',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to create permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified permission.
     */
    public function show($id)
    {
        $permission = PermissionModel::with(['roles' => function ($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'resource' => $permission->resource,
                'action' => $permission->action,
                'permission_string' => $permission->permission_string,
                'description' => $permission->description,
                'status' => $permission->status,
                'roles' => $permission->roles->map(function ($role) {
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
            'resource' => 'required|string|max:100',
            'action' => 'required|string|max:50',
            'permission_string' => 'required|string|max:150|unique:tbl_permissions,permission_string,' . $id,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ], [
            'resource.required' => 'The resource field is required.',
            'action.required' => 'The action field is required.',
            'permission_string.required' => 'The permission string is required.',
            'permission_string.unique' => 'The generated permission string already exists.',
            'status.required' => 'Please select a status.',
        ]);

        DB::beginTransaction();

        try {
            $permissionData = $this->preparePermissionData($request);
            $permission = PermissionModel::findOrFail($id);
            $permission->update([
                'name' => $permissionData['name'],
                'resource' => $permissionData['resource'],
                'action' => $permissionData['action'],
                'permission_string' => $permissionData['permission_string'],
                'description' => $request->description,
                'status' => $request->status,
                'portal' => $permissionData['portal'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Permission updated successfully.',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
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
                    'status' => 'error',
                    'message' => 'Cannot delete permission that is assigned to roles.'
                ], 422);
            }
            
            $permission->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Permission deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete permission: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
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
                'status' => 'success',
                'message' => 'Permission status updated successfully.',
                'data' => ['status' => $newStatus]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to toggle permission status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to update permission status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare normalized permission data for storage.
     *
     * @param Request $request
     * @return array<string, string>
     */
    private function preparePermissionData(Request $request): array
    {
        $normalizedPermissionString = $this->normalizePermissionString($request->input('permission_string'));
        $segments = array_values(array_filter(explode('.', str_replace(':', '.', $normalizedPermissionString))));
        $action = end($segments) ?: $this->normalizePermissionSegment($request->input('action'));
        $resourceSegments = array_slice($segments, 0, -1);
        $resource = !empty($resourceSegments)
            ? implode('_', $resourceSegments)
            : $this->normalizePermissionSegment($request->input('resource'));

        return [
            'name' => $this->buildPermissionName($segments, $resource, $action),
            'resource' => $resource,
            'action' => $action,
            'permission_string' => $normalizedPermissionString,
            'portal' => $this->inferPortalFromPermissionString($normalizedPermissionString),
        ];
    }

    /**
     * Normalize a permission resource or action segment.
     *
     * @param string|null $value
     * @return string
     */
    private function normalizePermissionSegment(?string $value): string
    {
        $normalizedValue = strtolower(trim((string) $value));
        $normalizedValue = preg_replace('/[^a-z0-9]+/', '_', $normalizedValue) ?? '';

        return trim($normalizedValue, '_');
    }

    /**
     * Normalize a permission string while preserving dot notation.
     */
    private function normalizePermissionString(?string $value): string
    {
        $normalizedValue = strtolower(trim((string) $value));
        $normalizedValue = str_replace(':', '.', $normalizedValue);
        $normalizedValue = preg_replace('/[^a-z0-9.]+/', '_', $normalizedValue) ?? '';
        $normalizedValue = preg_replace('/_+/', '_', $normalizedValue) ?? '';
        $normalizedValue = preg_replace('/\.+/', '.', $normalizedValue) ?? '';

        return trim($normalizedValue, '._');
    }

    /**
     * Build a stable permission name from parsed segments.
     *
     * @param array<int, string> $segments
     */
    private function buildPermissionName(array $segments, string $resource, string $action): string
    {
        if (!empty($segments)) {
            return implode('_', $segments);
        }

        return trim($action . '_' . $resource, '_');
    }

    /**
     * Infer the RBAC portal from a permission string.
     */
    private function inferPortalFromPermissionString(string $permissionString): string
    {
        $portal = explode('.', $permissionString)[0] ?? '';

        if (in_array($portal, ['owner', 'studio-hr', 'studio-finance', 'studio-photographer'], true)) {
            return $portal;
        }

        if (str_contains($permissionString, 'payroll')) {
            return 'studio-finance';
        }

        if (str_contains($permissionString, 'employee') || str_contains($permissionString, 'schedule')) {
            return 'studio-hr';
        }

        return auth()->user()?->role ?? 'owner';
    }
}
