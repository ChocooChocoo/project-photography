<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserModel extends Model
{
    use HasFactory, SoftDeletes; // soft delete keeps admin deletes consistent with App\Models\UserModel

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_users';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'location_id', // Add location_id
        'status',
        'email_verified',
        'verification_token',
        'token_expiry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'verification_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified' => 'boolean',
        'token_expiry' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user's location
     */
    public function location()
    {
        return $this->belongsTo(\App\Models\Admin\LocationModel::class, 'location_id');
    }

    /**
     * Get full name attribute
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $name = trim($this->first_name);
        
        if (!empty($this->middle_name)) {
            $name .= ' ' . trim($this->middle_name);
        }
        
        $name .= ' ' . trim($this->last_name);
        
        return $name;
    }

    /**
     * Get formatted role with display name
     *
     * @return string
     */
    public function getFormattedRoleAttribute(): string
    {
        $roleMap = [
            'admin' => 'Admin',
            'owner' => 'Studio Owner',
            'freelancer' => 'Freelancer',
            'client' => 'Client'
        ];

        return $roleMap[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Get formatted user type
     *
     * @return string
     */
    public function getFormattedUserTypeAttribute(): string
    {
        return ucfirst($this->user_type);
    }

    /**
     * Get formatted status with badge class
     *
     * @return array
     */
    public function getFormattedStatusAttribute(): array
    {
        $statusMap = [
            'active' => ['class' => 'success', 'label' => 'ACTIVE'],
            'inactive' => ['class' => 'warning', 'label' => 'INACTIVE'],
            'suspended' => ['class' => 'danger', 'label' => 'SUSPENDED']
        ];

        return $statusMap[$this->status] ?? ['class' => 'secondary', 'label' => strtoupper($this->status)];
    }

    /**
     * Get profile photo URL or default - FIXED FOR STORAGE PATH
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo) {
            // Check if it's already a full URL
            if (filter_var($this->profile_photo, FILTER_VALIDATE_URL)) {
                return $this->profile_photo;
            }
            
            // Check if it's a storage path
            if (strpos($this->profile_photo, 'storage/') === 0) {
                return asset($this->profile_photo);
            }
            
            // Handle storage path (storage/app/public/profile-photos/)
            if (strpos($this->profile_photo, 'profile-photos/') !== false) {
                return asset('storage/' . $this->profile_photo);
            }
            
            // Default storage path
            return asset('storage/' . $this->profile_photo);
        }
        
        // Use the specified placeholder image
        return asset('assets/uploads/profile_placeholder.jpg');
    }

    /**
     * Get email verification status
     *
     * @return string
     */
    public function getEmailVerifiedStatusAttribute(): string
    {
        return $this->email_verified ? 'Verified' : 'Not Verified';
    }

    /**
     * Get user's location details
     *
     * @return array|null
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
     *
     * @return string|null
     */
    public function getMunicipalityAttribute(): ?string
    {
        return $this->location ? $this->location->municipality : null;
    }

    /**
     * Get user's province
     *
     * @return string|null
     */
    public function getProvinceAttribute(): ?string
    {
        return $this->location ? $this->location->province : null;
    }

    /**
     * Get formatted location
     *
     * @return string
     */
    public function getFormattedLocationAttribute(): string
    {
        if (!$this->location) {
            return '—';
        }
        
        $location = [];
        
        if ($this->location->province) {
            $location[] = $this->location->province;
        }
        
        if ($this->location->municipality) {
            $location[] = $this->location->municipality;
        }
        
        return implode(', ', $location) ?: '—';
    }

    /**
     * Get the freelancer profile associated with the user.
     */
    public function freelancerProfile()
    {
        return $this->hasOne(\App\Models\Freelancer\ProfileModel::class, 'user_id');
    }

    /**
     * Get the services for the freelancer.
     */
    public function services()
    {
        return $this->hasMany(\App\Models\Freelancer\ServiceModel::class, 'user_id');
    }

    /**
     * Check if user has a profile photo
     *
     * @return bool
     */
    public function hasProfilePhoto(): bool
    {
        return !empty($this->profile_photo);
    }

    /**
     * Get profile photo or placeholder
     *
     * @return string
     */
    public function getProfilePhotoOrPlaceholderAttribute(): string
    {
        if ($this->hasProfilePhoto()) {
            return $this->profile_photo_url;
        }
        
        return asset('assets/uploads/profile_placeholder.jpg');
    }
}