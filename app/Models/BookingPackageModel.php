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
    ];

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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