<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HasFactory;

    /**
     * Friendly role labels keyed by stored role names.
     *
     * @var array<string, string>
     */
    protected const DISPLAY_NAMES = [
        'admin' => 'Administrator',
        'owner' => 'Studio Owner',
        'owner-super-admin' => 'Studio Owner',
        'freelancer' => 'Freelancer',
        'client' => 'Client',
        'studio-hr' => 'Human Resources',
        'studio-hr-manager' => 'HR Manager',
        'studio-hr-staff' => 'HR Staff',
        'studio-finance' => 'Finance',
        'studio-finance-manager' => 'Finance Manager',
        'studio-finance-staff' => 'Finance Staff',
        'studio-photographer' => 'Photographer',
    ];

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
        'portal',
        'description',
        'status',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'is_system' => 'boolean',
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
     * Check if role is system-protected.
     */
    public function isSystemProtected(): bool
    {
        return $this->is_system === true;
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        $permissionIdentifiers = PermissionModel::buildPermissionIdentifiers($permissionName);

        return $this->permissions()
            ->where('tbl_permissions.status', 'active')
            ->where(function ($query) use ($permissionIdentifiers) {
                $query->whereIn('name', $permissionIdentifiers)
                    ->orWhereIn('permission_string', $permissionIdentifiers);
            })
            ->exists();
    }

    /**
     * Get role display name for UI.
     */
    public function getDisplayNameAttribute(): string
    {
        return static::getFriendlyRoleName($this->name);
    }

    /**
     * Resolve a user-friendly label for a stored role name.
     */
    public static function getFriendlyRoleName(?string $roleName): string
    {
        $normalizedRoleName = strtolower(trim((string) $roleName));

        if ($normalizedRoleName === '') {
            return 'Unknown Role';
        }

        return static::DISPLAY_NAMES[$normalizedRoleName] ?? static::humanizeRoleName($normalizedRoleName);
    }

    /**
     * Convert a technical role key into a readable label.
     */
    private static function humanizeRoleName(string $roleName): string
    {
        $label = str_replace(['-', '_'], ' ', $roleName);

        return ucwords($label);
    }
}
