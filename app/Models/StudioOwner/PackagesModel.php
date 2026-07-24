<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PackagesModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'studio_id',
        'category_id',
        'package_name',
        'package_description',
        'package_inclusions',
        'duration',
        'maximum_edited_photos',
        'coverage_scope',
        'package_price',
        'online_gallery',
        'photographer_count',
        'package_location',
        'allow_time_customization',
        'status',
        'allow_multiple_locations',
        'max_locations',
        'cover_images',
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
        'photographer_count' => 'integer',
        'package_location' => 'array',
        'duration' => 'integer',
        'allow_time_customization' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'allow_multiple_locations' => 'boolean',
        'max_locations' => 'integer',
        'cover_images' => 'array',
    ];

    /**
     * Get the studio that owns the package.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the category that owns the package.
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\Admin\CategoriesModel::class, 'category_id');
    }

    /**
     * Validation rules for package creation.
     *
     * @return array
     */
    public static function rules($id = null)
    {
        return [
            'studio_id' => 'required|exists:tbl_studios,id',
            'category_id' => 'required|exists:tbl_categories,id',
            'package_name' => 'required|string|max:255|unique:tbl_packages,package_name,' . $id . ',id,studio_id,' . request()->studio_id,
            'package_description' => 'required|string|min:10|max:1000',
            'package_inclusions' => 'required|array|min:1',
            'package_inclusions.*' => 'required|string|max:255',
            'package_location' => 'required|array|min:1',
            'package_location.*' => 'required|in:In-Studio,On-Location',
            'allow_time_customization' => 'required|boolean',
            'duration' => 'nullable|integer|min:1|max:24',
            'maximum_edited_photos' => 'required|integer|min:1|max:1000',
            'coverage_scope' => 'nullable|string|max:500',
            'package_price' => 'required|numeric|min:0',
            'online_gallery' => 'required|boolean',
            'photographer_count' => 'required|integer|min:0|max:10',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array
     */
    public static function messages()
    {
        return [
            'studio_id.required' => 'Please select a studio.',
            'studio_id.exists' => 'Selected studio does not exist.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'package_name.required' => 'Package name is required.',
            'package_name.unique' => 'A package with this name already exists for this studio.',
            'package_inclusions.required' => 'At least one inclusion is required.',
            'package_inclusions.*.required' => 'Each inclusion item is required.',
            // ==== Start: Update validation messages for duration ====
            'duration.integer' => 'Duration must be a valid number.',
            'duration.min' => 'Duration must be at least 1 hour.',
            'duration.max' => 'Duration cannot exceed 24 hours.',
            'allow_time_customization.required' => 'Please select if time customization is allowed.',
            'allow_time_customization.boolean' => 'Invalid value for time customization.',
            // ==== End: Update validation messages for duration ====
            'maximum_edited_photos.min' => 'Minimum edited photos must be at least 1.',
            'maximum_edited_photos.max' => 'Maximum edited photos cannot exceed 1000.',
            'package_price.min' => 'Package price cannot be negative.',
        ];
    }

    /**
     * Scope to filter by studio owner.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOwner($query, $userId)
    {
        return $query->whereHas('studio', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope to filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the package location as a formatted string for display.
     * 
     * @return string
     */
    public function getFormattedLocationAttribute(): string
    {
        $locations = $this->package_location ?? [];
        
        if (empty($locations)) {
            return 'Not specified';
        }
        
        if (count($locations) === 1) {
            return $locations[0];
        }
        
        return implode(' / ', $locations);
    }

    /**
     * Check if package is available for in-studio sessions.
     * 
     * @return bool
     */
    public function isInStudioAvailable(): bool
    {
        return in_array('In-Studio', $this->package_location ?? []);
    }

    /**
     * Check if package is available for on-location sessions.
     * 
     * @return bool
     */
    public function isOnLocationAvailable(): bool
    {
        return in_array('On-Location', $this->package_location ?? []);
    }

    /**
     * Check if package is available for both locations.
     * 
     * @return bool
     */
    public function isBothLocationsAvailable(): bool
    {
        $locations = $this->package_location ?? [];
        return in_array('In-Studio', $locations) && in_array('On-Location', $locations);
    }

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
            $this->attributes['max_locations'] = 1;
        }
    }

    /**
     * Check if package allows multiple locations
     */
    public function allowsMultipleLocations(): bool
    {
        return $this->allow_multiple_locations && 
               $this->max_locations > 1 && 
               $this->isOnLocationAvailable();
    }

    /**
     * Get maximum locations display text
     */
    public function getMaxLocationsDisplayAttribute(): string
    {
        if (!$this->allow_multiple_locations || !$this->isOnLocationAvailable()) {
            return 'Single location only';
        }
        
        return 'Up to ' . $this->max_locations . ' locations';
    }

    /**
     * Get the first cover image as a thumbnail, verifying it still exists on disk.
     */
    public function getCoverThumbnailAttribute()
    {
        $path = $this->cover_images[0] ?? null;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }
}
