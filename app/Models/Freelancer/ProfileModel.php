<?php

namespace App\Models\Freelancer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProfileModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_freelancers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'contract_template',
        'location_id',
        'brand_name',
        'tagline',
        'bio',
        'years_experience',
        'brand_logo',
        'street',
        'barangay',
        'service_area',
        'starting_price',
        'deposit_policy',
        'deposit_type',
        'deposit_amount',
        'portfolio_works',
        'facebook_url',
        'instagram_url',
        'website_url',
        'valid_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'portfolio_works' => 'array',
        'starting_price' => 'decimal:2',
        // ==== Start: Deposit Policy Enhancement ==== //
        'deposit_amount' => 'decimal:2',
        // ==== End: Deposit Policy Enhancement ==== //
    ];

    /**
     * Get the user associated with the freelancer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UserModel::class, 'user_id');
    }

    /**
     * Get the location associated with the freelancer profile.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\LocationModel::class, 'location_id');
    }

    /**
     * Get the categories associated with the freelancer.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Admin\CategoriesModel::class,
            'pvt_freelancer_categories',
            'user_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * Get the schedule associated with the freelancer.
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(FreelancerScheduleModel::class, 'user_id', 'user_id');
    }

    /**
     * Get the services associated with the freelancer.
     */
    public function services()
    {
        return $this->hasMany(\App\Models\Freelancer\ServiceModel::class, 'user_id', 'user_id');
    }

    /**
     * Get the ratings for the freelancer.
     */
    public function ratings()
    {
        return $this->hasMany(\App\Models\FreelancerRatingModel::class, 'freelancer_id', 'user_id');
    }

    // ==== Start: Deposit Policy Enhancement ==== //
    
    /**
     * Get formatted deposit display text
     */
    public function getDepositDisplayAttribute(): string
    {
        if ($this->deposit_policy !== 'required') {
            return 'No deposit required (Full payment upon booking)';
        }

        if ($this->deposit_type === 'fixed') {
            return 'PHP ' . number_format($this->deposit_amount, 2) . ' fixed deposit';
        } elseif ($this->deposit_type === 'percentage') {
            return $this->deposit_amount . '% of total amount deposit';
        }

        return 'Deposit required (Amount not configured)';
    }

    /**
     * Calculate deposit amount based on total booking amount
     * 
     * @param float $totalAmount
     * @return float|null Returns null if deposit not required, otherwise calculated amount
     */
    public function calculateDepositAmount(float $totalAmount): ?float
    {
        if ($this->deposit_policy !== 'required') {
            return null; // No deposit required
        }

        if ($this->deposit_type === 'fixed') {
            return (float) $this->deposit_amount;
        } elseif ($this->deposit_type === 'percentage') {
            return ($totalAmount * (float) $this->deposit_amount) / 100;
        }

        return null;
    }

    /**
     * Check if deposit is configured properly
     */
    public function hasValidDepositConfiguration(): bool
    {
        if ($this->deposit_policy !== 'required') {
            return true; // Not required is always valid
        }

        return !is_null($this->deposit_type) && 
               !is_null($this->deposit_amount) && 
               $this->deposit_amount > 0;
    }

    /**
     * Get deposit type label
     */
    public function getDepositTypeLabelAttribute(): string
    {
        $labels = [
            'fixed' => 'Fixed Amount',
            'percentage' => 'Percentage (%)',
        ];

        return $labels[$this->deposit_type] ?? 'Not specified';
    }

    // ==== End: Deposit Policy Enhancement ==== //
}