<?php

namespace App\Models\StudioOwner;

use App\Models\Admin\CategoriesModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudiosModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_studios';

    protected $fillable = [
        'user_id',
        'category_id',
        'location_id',
        'street',
        'barangay',
        'contact_number',
        'studio_email',
        'facebook_url',
        'instagram_url',
        'website_url',
        'studio_name',
        'studio_type',
        'year_established',
        'studio_description',
        'studio_logo',
        'starting_price',
        'downpayment_percentage',
        'operating_days',
        'start_time',
        'end_time',
        'max_clients_per_day',
        'advance_booking_days',
        'business_permit',
        'owner_id_document',
        'status',
        'rejection_note',
        // Removed: 'service_coverage_area',
    ];

    protected $casts = [
        'operating_days' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'downpayment_percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Removed: 'service_coverage_area' => 'array',
    ];

    /**
     * Get the category for the studio.
     */
    public function category()
    {
        return $this->belongsTo(CategoriesModel::class, 'category_id');
    }

    /**
     * Get the categories for the studio (many-to-many).
     */
    public function categories()
    {
        return $this->belongsToMany(
            \App\Models\Admin\CategoriesModel::class,
            'pvt_studio_categories',
            'studio_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * Get the location for the studio.
     */
    public function location()
    {
        return $this->belongsTo(\App\Models\Admin\LocationModel::class, 'location_id');
    }

    /**
     * Get the user that owns the studio.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\StudioOwner\UserModel::class, 'user_id');
    }

    /**
     * Get the studio schedules.
     */
    public function schedules()
    {
        return $this->hasMany(StudioScheduleModel::class, 'studio_id');
    }

    /**
     * Get the studio categories pivot.
     */
    public function studioCategories()
    {
        return $this->hasMany(StudioCategoryModel::class, 'studio_id');
    }

    public static function rules()
    {
        return [
            'studio_name' => 'required|string|max:255',
            'studio_type' => 'required|in:photography_studio,video_production,mixed_media',
            'year_established' => 'required|integer|min:1900|max:'.date('Y'),
            'studio_description' => 'required|string|min:10|max:1000',
            'studio_logo' => 'required|image|mimes:jpg,jpeg,png|max:3072',
            'province' => 'required|string',
            'municipality' => 'required|exists:tbl_locations,municipality',
            'barangay' => 'required|string',
            'street' => 'required|string|max:255',
            'zip_code' => 'required|string|size:4',
            'contact_number' => 'required|string|max:20',
            'studio_email' => 'required|email|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'service_categories' => 'required|array|min:1', // Changed: Multiple categories allowed
            'service_categories.*' => 'exists:tbl_categories,id',
            'starting_price' => 'required|numeric|min:0',
            'downpayment_percentage' => 'nullable|numeric|min:0|max:100',
            'operating_days' => 'required|array|min:1',
            'operating_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_clients_per_day' => 'required|integer|min:1|max:100',
            'advance_booking_days' => 'required|integer|min:1|max:30',
            'business_permit' => 'required|file|mimes:pdf,jpg,jpeg,png|max:3072',
            'owner_id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:3072',
        ];
    }

    /**
     * Get the packages for the studio.
     */
    public function packages()
    {
        return $this->hasMany(PackagesModel::class, 'studio_id');
    }

    /**
     * Get the services for the studio.
     */
    public function services()
    {
        return $this->hasMany(\App\Models\StudioOwner\ServicesModel::class, 'studio_id');
    }

    /**
     * Get the studio photographers assigned to this studio.
     */
    public function studioPhotographers()
    {
        return $this->hasMany(\App\Models\StudioOwner\StudioPhotographersModel::class, 'studio_id');
    }

    /**
     * Get the ratings for the studio.
     */
    public function ratings()
    {
        return $this->hasMany(\App\Models\StudioRatingModel::class, 'studio_id');
    }

    /**
     * Get the leave requests filed under the studio.
     */
    public function leaveRequests()
    {
        return $this->hasMany(\App\Models\LeaveRequestModel::class, 'studio_id');
    }
}
