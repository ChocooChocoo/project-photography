<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
     * Display the studio user roles assignment page.
     */
    public function userRoles(Request $request)
    {
        $studios = StudiosModel::where('user_id', auth()->id())->get();
        $studioId = $request->filled('studio_id')
            ? (int) $request->input('studio_id')
            : ($studios->first()->id ?? null);

        $users = collect();

        if ($studioId !== null) {
            $users = UserModel::whereIn('id', $this->getStudioUserIds($studioId))
                ->with(['roles' => function ($query) use ($studioId) {
                    $query->wherePivot('studio_id', $studioId);
                }])
                ->orderBy('first_name')
                ->get();
        }

        $roles = RoleModel::whereIn('portal', ['owner', 'studio-hr', 'studio-finance', 'studio-photographer'])
            ->orderBy('name')
            ->get();

        return view('owner.user-roles', compact('users', 'roles', 'studios', 'studioId'));
    }

    /**
     * Sync the studio roles assigned to the selected users.
     */
    public function updateUserRoles(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|integer',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|integer',
        ]);

        $studioIds = StudiosModel::where('user_id', auth()->id())->pluck('id');
        $studioId = $request->filled('studio_id')
            ? (int) $request->input('studio_id')
            : ($studioIds->first() ?? null);

        if ($studioId === null || !$studioIds->contains($studioId)) {
            return redirect()->back()->withErrors(['studio_id' => 'The selected studio is invalid.']);
        }

        $roleIds = RoleModel::whereIn('id', $request->input('role_ids', []))
            ->whereIn('portal', ['owner', 'studio-hr', 'studio-finance', 'studio-photographer'])
            ->pluck('id');

        if ($roleIds->count() !== count($request->input('role_ids', []))) {
            return redirect()->back()->withErrors(['role_ids' => 'One or more selected roles are not available for this studio.']);
        }

        $roleNames = RoleModel::whereIn('id', $roleIds)->pluck('name')->all();

        $userIds = collect($request->input('user_ids'))
            ->map(fn ($id) => (int) $id)
            ->intersect($this->getStudioUserIds($studioId))
            ->values();

        if ($userIds->isEmpty()) {
            return redirect()->back()->withErrors(['user_ids' => 'No valid studio users were selected.']);
        }

        foreach (UserModel::whereIn('id', $userIds)->get() as $user) {
            $user->syncRoles($roleNames, $studioId);
        }

        $this->clearPermissionCache();

        return redirect()->back()->with('success', 'User roles updated successfully.');
    }

    /**
     * Collect the ids of users that belong to the given studio
     * (employees, studio photographers and approved studio members).
     */
    private function getStudioUserIds(int $studioId)
    {
        $employeeIds = UserModel::whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->whereExists(function ($query) use ($studioId) {
                $query->select(DB::raw(1))
                    ->from('tbl_user_roles')
                    ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                    ->where('tbl_user_roles.studio_id', $studioId);
            })
            ->pluck('id');

        $photographerIds = StudioPhotographersModel::where('studio_id', $studioId)
            ->pluck('photographer_id');

        $memberIds = StudioMemberModel::where('studio_id', $studioId)
            ->approved()
            ->pluck('freelancer_id');

        return $employeeIds
            ->merge($photographerIds)
            ->merge($memberIds)
            ->unique()
            ->values();
    }

    /**
     * Get roles data for DataTable.
     */
    public function getRoles(Request $request)
    {
        $query = RoleModel::query()
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
                'technical_name' => $role->name,
                'description' => $role->description,
                'status' => $role->status,
                'is_system' => $role->is_system,
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
            'name' => ['required', 'string', 'max:100', Rule::unique('tbl_roles', 'name')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'is_system' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $role = RoleModel::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'is_system' => $request->boolean('is_system'),
                'portal' => $this->inferPortalFromRoleName($request->name),
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
        $role = RoleModel::with(['permissions' => function ($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'technical_name' => $role->name,
                'description' => $role->description,
                'status' => $role->status,
                'is_system' => $role->is_system,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'permission_string' => $permission->permission_string,
                        'display_label' => $permission->display_label,
                        'resource_display' => $permission->resource_display,
                        'action_display' => $permission->action_display,
                        'portal_display' => $permission->portal_display,
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
            'name' => ['required', 'string', 'max:100', Rule::unique('tbl_roles', 'name')->whereNull('deleted_at')->ignore($id)],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'is_system' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $role = RoleModel::findOrFail($id);
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'is_system' => $request->boolean('is_system'),
                'portal' => $this->inferPortalFromRoleName($request->name),
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
        // "present" lets an empty list through, so removing every permission
        // saves. "required" would reject the empty list and the removal would
        // never reach the pivot table.
        $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'exists:tbl_permissions,id',
        ]);

        DB::beginTransaction();

        try {
            $role = RoleModel::findOrFail($id);
            $role->permissions()->sync($request->input('permissions', []));

            DB::commit();

            $this->clearPermissionCache();

            $role->load(['permissions' => function ($query) {
                $query->orderBy('name');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Role permissions updated successfully.',
                'data' => [
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'permission_string' => $permission->permission_string,
                            'display_label' => $permission->display_label,
                            'resource_display' => $permission->resource_display,
                            'action_display' => $permission->action_display,
                            'portal_display' => $permission->portal_display,
                            'description' => $permission->description,
                        ];
                    })->values(),
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

            if ($role->isSystemProtected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System-protected roles cannot be deleted.'
                ], 422);
            }
            
            // Check if role has users assigned
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that has users assigned to it.'
                ], 422);
            }
            
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

    /**
     * Reset the shared permission cache.
     *
     * UserModel keeps the cache in a protected static property and exposes no
     * public reset method, so reach it through reflection. Every save clears
     * the whole cache because one role change can affect many users.
     */
    private function clearPermissionCache(): void
    {
        $property = new \ReflectionProperty(UserModel::class, 'permissionCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    /**
     * Infer the RBAC portal from a role name.
     */
    private function inferPortalFromRoleName(string $roleName): string
    {
        $normalizedRoleName = strtolower(trim($roleName));

        if (str_starts_with($normalizedRoleName, 'owner')) {
            return 'owner';
        }

        if (str_starts_with($normalizedRoleName, 'studio-hr')) {
            return 'studio-hr';
        }

        if (str_starts_with($normalizedRoleName, 'studio-finance')) {
            return 'studio-finance';
        }

        if (str_starts_with($normalizedRoleName, 'studio-photographer')) {
            return 'studio-photographer';
        }

        return auth()->user()?->role ?? 'owner';
    }
}
