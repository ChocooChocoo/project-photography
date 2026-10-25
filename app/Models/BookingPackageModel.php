<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPackageModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_booking_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'package_id',
        'package_type',
        'package_name',
        'package_price',
        'package_inclusions',
        'duration',
        'maximum_edited_photos',
        'coverage_scope',
        'materials',
        'photo_edit_option',
        'enhancement_amount',
        'editing_services',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = ['editing_summary'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'package_price' => 'decimal:2',
        'package_inclusions' => 'array',
        'materials' => 'array',
        'enhancement_amount' => 'decimal:2',
        'editing_services' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * What the client asked for: "Unedited (RAW) photos" or the booked
     * editing services, e.g. "Color grading, Retouching".
     */
    public function getEditingSummaryAttribute(): string
    {
        $labels = \App\Models\StudioOwner\PackagesModel::EDITING_SERVICES + ['enhanced' => 'Enhanced photos'];
        // MySQL JSON reorders keys, so list services in the fixed catalog order.
        $services = array_keys(array_intersect_key($labels, $this->editing_services ?? []));
        if ($services === [] && $this->photo_edit_option === 'enhanced') {
            $services = ['enhanced'];
        }

        return $services === []
            ? 'Unedited (RAW) photos'
            : implode(', ', array_map(fn ($key) => $labels[$key] ?? $key, $services));
    }

    /**
     * Older snapshots copied the package's raw JSON list (["Cavite","Laguna"]); show it as text.
     */
    public function getCoverageScopeAttribute($value): ?string
    {
        return \App\Models\StudioOwner\PackagesModel::coverageScopeLabel($value);
    }

    /**
     * Get the booking associated with the package.
     */
    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    /**
     * Get the package details for studio packages.
     */
    public function studioPackage()
    {
        return $this->belongsTo(\App\Models\StudioOwner\PackagesModel::class, 'package_id');
    }

    /**
     * Get the package details for freelancer packages.
     */
    public function freelancerPackage()
    {
        return $this->belongsTo(\App\Models\Freelancer\PackagesModel::class, 'package_id');
    }

    /**
     * Get the package based on type.
     */
    public function package()
    {
        if ($this->package_type === 'studio') {
            return $this->studioPackage();
        } else {
            return $this->freelancerPackage();
        }
    }
}