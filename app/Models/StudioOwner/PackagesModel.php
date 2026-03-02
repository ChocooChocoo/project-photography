<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'package_inclusions' => 'array',
        'coverage_scope' => 'array',
        'package_price' => 'decimal:2',
        'online_gallery' => 'boolean',
        'photographer_count' => 'integer',
        'package_location' => 'array',
        'duration' => 'integer',
        'allow_time_customization' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}