<?php

namespace App\Models\Freelancer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagesModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_freelancer_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'package_name',
        'package_description',
        'package_inclusions',
        'allow_time_customization',
        'duration',
        'maximum_edited_photos',
        'coverage_scope',
        'package_price',
        'online_gallery',
        'status',
        // ==== NEW FIELDS START ====
        'allow_multiple_locations',
        'max_locations'
        // ==== NEW FIELDS END ====
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'package_inclusions' => 'array',
        'package_price' => 'decimal:2',
        'online_gallery' => 'boolean',
        'allow_time_customization' => 'boolean',
        // ==== NEW CASTS START ====
        'allow_multiple_locations' => 'boolean',
        'max_locations' => 'integer',
        // ==== NEW CASTS END ====
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the package.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Get the category that owns the package.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\CategoriesModel::class, 'category_id');
    }

    /**
     * Validation rules for package creation.
     *
     * @var array
     */
    public static $rules = [
        'category_id' => 'required|exists:tbl_categories,id',
        'package_name' => 'required|string|max:255',
        'package_description' => 'required|string',
        'package_inclusions' => 'required|array|min:1',
        'package_inclusions.*' => 'required|string|max:255',
        'duration' => 'required|integer|min:1|max:24',
        'maximum_edited_photos' => 'required|integer|min:1|max:1000',
        'coverage_scope' => 'nullable|string|max:255',
        'package_price' => 'required|numeric|min:0',
        'online_gallery' => 'boolean',
        'status' => 'required|in:active,inactive',
        // ==== NEW RULES START ====
        'allow_multiple_locations' => 'boolean',
        'max_locations' => 'nullable|integer|min:1|max:10',
        // ==== NEW RULES END ====
    ];
    
    /**
     * Get max locations with validation (ensures between 1-10)
     */
    public function getMaxLocationsAttribute($value)
    {
        if ($this->allow_multiple_locations && $value) {
            return min(max((int)$value, 1), 10);
        }
        return 1; // Default to 1 when multiple locations not allowed
    }

    /**
     * Set max locations with validation
     */
    public function setMaxLocationsAttribute($value)
    {
        if ($this->allow_multiple_locations && $value) {
            $this->attributes['max_locations'] = min(max((int)$value, 1), 10);
        } else {
            $this->attributes['max_locations'] = null;
        }
    }

    /**
     * Check if package allows multiple locations
     */
    public function allowsMultipleLocations(): bool
    {
        return $this->allow_multiple_locations && $this->max_locations > 1;
    }

    /**
     * Get maximum locations display text
     */
    public function getMaxLocationsDisplayAttribute(): string
    {
        if (!$this->allow_multiple_locations) {
            return 'Single location only';
        }
        
        return 'Up to ' . $this->max_locations . ' locations';
    }

    /**
     * Get multiple locations badge class
     */
    public function getMultipleLocationsBadgeAttribute(): string
    {
        return $this->allow_multiple_locations 
            ? 'badge-soft-success' 
            : 'badge-soft-secondary';
    }

    /**
     * Get multiple locations icon
     */
    public function getMultipleLocationsIconAttribute(): string
    {
        return $this->allow_multiple_locations 
            ? 'ti ti-map-pin-check' 
            : 'ti ti-map-pin';
    }

    /**
     * Get time customization label
     */
    public function getTimeCustomizationLabelAttribute(): string
    {
        return $this->allow_time_customization ? 'Flexible' : 'Fixed';
    }

    /**
     * Get duration display text
     */
    public function getDurationDisplayAttribute(): string
    {
        if ($this->allow_time_customization) {
            return 'Client can choose';
        }
        
        return $this->duration ? $this->duration . ' hours' : 'Not specified';
    }

    /**
     * Get time customization badge class
     */
    public function getTimeCustomizationBadgeAttribute(): string
    {
        return $this->allow_time_customization 
            ? 'badge-soft-success' 
            : 'badge-soft-secondary';
    }

    /**
     * Get time customization icon
     */
    public function getTimeCustomizationIconAttribute(): string
    {
        return $this->allow_time_customization 
            ? 'ti ti-clock-edit' 
            : 'ti ti-clock';
    }
}