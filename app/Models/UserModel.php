<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Models\Admin\LocationModel;

class UserModel extends Authenticatable
{
    use HasFactory, Notifiable;

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
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
            'studio-staff' => 'Studio Staff',
        ];
    }

    /**
     * Get the RBAC permissions for this user.
     */
    public function rbac()
    {
        return $this->hasMany(\App\Models\StudioOwner\RbacModel::class, 'user_id');
    }

    /**
     * Get the employee schedule for this user.
     */
    public function employeeSchedule()
    {
        return $this->hasMany(\App\Models\StudioOwner\EmployeeScheduleModel::class, 'user_id');
    }

}