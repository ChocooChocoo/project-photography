<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\Admin\LocationModel;

class UserModel extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static array $permissionCache = [];

    protected $table = 'tbl_users';
    
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'uuid',
        'role',
        'user_type',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'mobile_number',
        'password',
        'profile_photo',
        'cover_photo',
        'location_id',
        'status',
        'email_verified',
        'verification_token',
        'token_expiry'
    ];

    protected $hidden = [
        'password',
        'verification_token',
        'remember_token'
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'token_expiry' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Boot method to generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            
            // Automatically set user_type based on role
            if (empty($model->user_type)) {
                $model->user_type = self::getUserTypeFromRole($model->role);
            }
        });
    }

    /**
     * Get the user's location
     */
    public function location()
    {
        return $this->belongsTo(LocationModel::class, 'location_id');
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        
        if (!empty($this->middle_name)) {
            $name .= ' ' . $this->middle_name;
        }
        
        $name .= ' ' . $this->last_name;
        
        return trim($name);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is owner
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is freelancer
     */
    public function isFreelancer(): bool
    {
        return $this->role === 'freelancer';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Check if email is verified
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified === true;
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is photographer type
     */
    public function isPhotographer(): bool
    {
        return $this->user_type === 'photographer';
    }

    /**
     * Check if user is customer type
     */
    public function isCustomer(): bool
    {
        return $this->user_type === 'customer';
    }

    /**
     * Get user type from role
     */
    public static function getUserTypeFromRole($role): string
    {
        $photographerRoles = ['owner', 'freelancer'];
        $customerRoles = ['client'];
        
        if (in_array($role, $photographerRoles)) {
            return 'photographer';
        } elseif (in_array($role, $customerRoles)) {
            return 'customer';
        }
        
        // Default for admin or any other role
        return 'customer';
    }

    /**
     * Get display name for user type
     */
    public function getUserTypeDisplay(): string
    {
        return ucfirst($this->user_type);
    }

    /**
     * Get location details
     */
    public function getLocationDetailsAttribute(): ?array
    {
        if (!$this->location) {
            return null;
        }
        
        return [
            'province' => $this->location->province,
            'municipality' => $this->location->municipality,
            'barangay' => $this->location->barangay,
            'zip_code' => $this->location->zip_code
        ];
    }

    /**
     * Get user's municipality
     */
    public function getMunicipalityAttribute(): ?string
    {
        return $this->location ? $this->location->municipality : null;
    }

    /**
     * Get user's province
     */
    public function getProvinceAttribute(): ?string
    {
        return $this->location ? $this->location->province : null;
    }

    /**
     * Get profile photo URL or default
     */
    public function getProfilePhotoUrlAttribute()
    {
        if (!empty($this->profile_photo)) {
            // Check if it's already a full URL
            if (filter_var($this->profile_photo, FILTER_VALIDATE_URL)) {
                return $this->profile_photo;
            }
            
            // Check if it's a storage path with 'storage/' prefix
            if (strpos($this->profile_photo, 'storage/') === 0) {
                return asset($this->profile_photo);
            }
            
            // If it's just the filename (stored in profile-photos directory)
            return asset('storage/' . $this->profile_photo);
        }
        
        // Return placeholder if no profile photo
        return asset('assets/images/users/user-3.jpg');
    }

    /**
     * Get cover photo URL or default
     */
    public function getCoverPhotoUrlAttribute()
    {
        if (!empty($this->cover_photo)) {
            // Check if it's already a full URL
            if (filter_var($this->cover_photo, FILTER_VALIDATE_URL)) {
                return $this->cover_photo;
            }
            
            // Check if it's a storage path with 'storage/' prefix
            if (strpos($this->cover_photo, 'storage/') === 0) {
                return asset($this->cover_photo);
            }
            
            // If it's just the filename (stored in cover-photos directory)
            return asset('storage/' . $this->cover_photo);
        }
        
        // Return placeholder if no cover photo
        return asset('assets/images/profile-bg.jpg');
    }

    /**
     * Check if user has profile photo
     */
    public function hasProfilePhoto(): bool
    {
        return !empty($this->profile_photo);
    }

    /**
     * Check if user has cover photo
     */
    public function hasCoverPhoto(): bool
    {
        return !empty($this->cover_photo);
    }

    /**
     * Check if user is studio-hr
     */
    public function isStudioHr(): bool
    {
        return $this->role === 'studio-hr';
    }

    /**
     * Check if user is studio-finance
     */
    public function isStudioFinance(): bool
    {
        return $this->role === 'studio-finance';
    }

    /**
     * Check if user is studio-photographer.
     */
    public function isStudioPhotographer(): bool
    {
        return $this->role === 'studio-photographer';
    }

    /**
     * Check if user is studio-staff (for HR/Finance staff)
     */
    public function isStudioStaff(): bool
    {
        return $this->role === 'studio-staff' || 
            $this->role === 'studio-hr' || 
            $this->role === 'studio-finance';
    }

    /**
     * Get all available employee roles
     */
    public static function getEmployeeRoles(): array
    {
        return [
            'studio-hr' => 'Human Resources',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
            'studio-staff' => 'Studio Staff',
        ];
    }

    /**
     * Get the employee schedule for this user.
     */
    public function employeeSchedule()
    {
        return $this->hasMany(\App\Models\StudioOwner\EmployeeScheduleModel::class, 'user_id');
    }

    /**
     * Get the leave requests created by the user.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequestModel::class, 'user_id');
    }

    /**
     * Get the leave requests approved by the user.
     */
    public function approvedLeaveRequests()
    {
        return $this->hasMany(LeaveRequestModel::class, 'approved_by');
    }

    /**
     * Get the leave requests rejected by the user.
     */
    public function rejectedLeaveRequests()
    {
        return $this->hasMany(LeaveRequestModel::class, 'rejected_by');
    }

    /**
     * Get the overtime requests created by the user.
     */
    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequestModel::class, 'user_id');
    }

    /**
     * Get the overtime requests approved by the user.
     */
    public function approvedOvertimeRequests()
    {
        return $this->hasMany(OvertimeRequestModel::class, 'approved_by');
    }

    /**
     * Get the overtime requests rejected by the user.
     */
    public function rejectedOvertimeRequests()
    {
        return $this->hasMany(OvertimeRequestModel::class, 'rejected_by');
    }
    
    /**
     * Get the roles assigned to this user.
     */
    public function roles()
    {
        return $this->belongsToMany(\App\Models\StudioOwner\RoleModel::class, 'tbl_user_roles', 'user_id', 'role_id')
            ->withPivot('studio_id')
            ->withTimestamps();
    }

    /**
     * Get active RBAC roles, optionally scoped by portal and studio.
     */
    public function activeRoles(?string $portal = null, ?int $studioId = null)
    {
        $query = $this->roles()->where('tbl_roles.status', 'active');

        if ($portal !== null) {
            $query->where('tbl_roles.portal', $portal);
        }

        if ($studioId !== null) {
            $query->wherePivot('studio_id', $studioId);
        }

        return $query;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName, ?int $studioId = null): bool
    {
        return $this->activeRoles(null, $studioId)->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roleNames, ?int $studioId = null): bool
    {
        return $this->activeRoles(null, $studioId)->whereIn('name', $roleNames)->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole($role, ?int $studioId = null)
    {
        if (is_string($role)) {
            $role = \App\Models\StudioOwner\RoleModel::where('name', $role)->first();
        }

        if ($role && !$this->roles()
            ->where('tbl_roles.id', $role->id)
            ->wherePivot('studio_id', $studioId)
            ->exists()) {
            $this->roles()->attach($role->id, ['studio_id' => $studioId]);
            self::$permissionCache = [];
        }

        return $this;
    }

    /**
     * Sync roles for the user.
     */
    public function syncRoles(array $roleNames, ?int $studioId = null)
    {
        $roleIds = \App\Models\StudioOwner\RoleModel::whereIn('name', $roleNames)->pluck('id')->toArray();

        if ($studioId === null) {
            $this->roles()->sync($roleIds);
            self::$permissionCache = [];

            return $this;
        }

        $this->roles()->newPivotStatement()
            ->where('user_id', $this->id)
            ->where('studio_id', $studioId)
            ->delete();

        foreach ($roleIds as $roleId) {
            $this->roles()->attach($roleId, ['studio_id' => $studioId]);
        }

        self::$permissionCache = [];

        return $this;
    }

    /**
     * Get all permissions for this user through their roles.
     */
    public function getAllPermissions(?int $studioId = null, ?string $portal = null): Collection
    {
        $cacheKey = implode(':', [
            $this->getKey(),
            $portal ?? 'all',
            $studioId ?? 'all',
        ]);

        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }

        $permissions = collect();

        $roles = $this->activeRoles($portal, $studioId)->with(['permissions' => function ($query) use ($portal) {
            $query->where('tbl_permissions.status', 'active');

            if ($portal !== null) {
                $query->where('tbl_permissions.portal', $portal);
            }
        }])->get();

        foreach ($roles as $role) {
            $permissions = $permissions->merge(
                $role->permissions->where('status', 'active')
            );
        }

        self::$permissionCache[$cacheKey] = $permissions->unique('id')->values();

        return self::$permissionCache[$cacheKey];
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionName, ?int $studioId = null): bool
    {
        $permissionIdentifiers = \App\Models\StudioOwner\PermissionModel::buildPermissionIdentifiers($permissionName);
        $portal = $this->getPortalName();

        return $this->getAllPermissions($studioId, $portal)->contains(function ($permission) use ($permissionIdentifiers) {
            return in_array($permission->name, $permissionIdentifiers, true)
                || in_array($permission->permission_string, $permissionIdentifiers, true);
        });
    }

    /**
     * Get the current portal identifier for this user.
     */
    public function getPortalName(): string
    {
        return $this->role;
    }

    /**
     * Get studio IDs assigned through active RBAC roles for the current portal.
     */
    public function getAssignedStudioIds(?string $portal = null): Collection
    {
        return $this->activeRoles($portal ?? $this->getPortalName())
            ->pluck('tbl_user_roles.studio_id')
            ->filter()
            ->unique()
            ->values();
    }
}
