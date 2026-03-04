<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudioOwner\UserModel;
use App\Models\StudioOwner\StudiosModel;

class RbacModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_rbac';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'studio_id',
        'role',
        'role_type',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
        'module_permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_create' => 'boolean',
        'can_read' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
        'module_permissions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user associated with this RBAC.
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio associated with this RBAC.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Check if user has specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissionMap = [
            'create' => $this->can_create,
            'read' => $this->can_read,
            'update' => $this->can_update,
            'delete' => $this->can_delete,
        ];

        return $permissionMap[$permission] ?? false;
    }

    /**
     * Check if user has module-specific permission.
     *
     * @param string $module
     * @param string $permission
     * @return bool
     */
    public function hasModulePermission(string $module, string $permission): bool
    {
        $modulePermissions = $this->module_permissions ?? [];
        
        if (!isset($modulePermissions[$module])) {
            return false;
        }

        $permissionMap = [
            'create' => 'can_create',
            'read' => 'can_read',
            'update' => 'can_update',
            'delete' => 'can_delete',
        ];

        $permissionKey = $permissionMap[$permission] ?? null;
        
        if (!$permissionKey) {
            return false;
        }

        return $modulePermissions[$module][$permissionKey] ?? false;
    }

    /**
     * Get role display name.
     *
     * @return string
     */
    public function getRoleDisplayAttribute(): string
    {
        $roles = [
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
        ];

        return $roles[$this->role] ?? ucfirst(str_replace('-', ' ', $this->role));
    }

    /**
     * Get role type display.
     *
     * @return string
     */
    public function getRoleTypeDisplayAttribute(): string
    {
        if ($this->role === 'studio-photographer') {
            return 'Photographer';
        }
        
        return $this->role_type ?? 'Not specified';
    }

    /**
     * Scope to filter by studio.
     */
    public function scopeByStudio($query, $studioId)
    {
        return $query->where('studio_id', $studioId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get permissions as array for API responses.
     *
     * @return array
     */
    public function getPermissionsArrayAttribute(): array
    {
        return [
            'create' => $this->can_create,
            'read' => $this->can_read,
            'update' => $this->can_update,
            'delete' => $this->can_delete,
            'module_permissions' => $this->module_permissions ?? [],
        ];
    }
}