<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the permissions for this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(PermissionModel::class, 'tbl_role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * Get the users assigned to this role.
     */
    public function users()
    {
        return $this->belongsToMany(\App\Models\UserModel::class, 'tbl_user_roles', 'role_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Scope to filter active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if role is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Get role display name for UI.
     */
    public function getDisplayNameAttribute(): string
    {
        $displayNames = [
            'studio-hr-manager' => 'Human Resource Manager',
            'studio-hr-staff' => 'Human Resource Staff',
            'studio-finance-manager' => 'Finance Manager',
            'studio-finance-staff' => 'Finance Staff',
        ];

        return $displayNames[$this->name] ?? ucwords(str_replace('-', ' ', $this->name));
    }
}