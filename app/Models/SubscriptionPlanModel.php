<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_subscription_plans';

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
        'user_type',
        'plan_type',
        'billing_cycle',
        'plan_code',
        'name',
        'description',
        'price',
        'commission_rate',
        'trial_days',
        'max_booking',
        'max_studio_photographers',
        'max_studios',
        'staff_limit',
        'priority_level',
        'features',
        'support_level',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'trial_days' => 'integer',
        'max_studios' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Plan type labels for display
     */
    public const PLAN_TYPES = [
        'basic' => 'Basic',
        'premium' => 'Premium',
        'enterprise' => 'Enterprise',
    ];

    /**
     * Support level labels
     */
    public const SUPPORT_LEVELS = [
        'basic' => 'Basic Support',
        'priority' => 'Priority Support',
        'dedicated' => 'Dedicated Support',
    ];

    /**
     * Generate a unique plan code
     */
    public static function generatePlanCode($userType, $planType, $billingCycle)
    {
        $prefix = strtoupper(substr($userType, 0, 3));
        $plan = strtoupper($planType);
        $cycle = strtoupper(substr($billingCycle, 0, 3));
        
        $baseCode = "{$prefix}_{$plan}_{$cycle}";
        $code = $baseCode;
        $counter = 1;
        
        while (self::where('plan_code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Get studio subscriptions for this plan
     */
    public function studioSubscriptions()
    {
        return $this->hasMany(StudioPlanModel::class, 'plan_id');
    }

    /**
     * Get freelancer subscriptions for this plan
     */
    public function freelancerSubscriptions()
    {
        return $this->hasMany(FreelancerPlanModel::class, 'plan_id');
    }

    /**
     * Scope to filter by user type
     */
    public function scopeByUserType($query, $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope to filter active plans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute()
    {
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Get formatted billing cycle
     */
    public function getFormattedBillingCycleAttribute()
    {
        return ucfirst($this->billing_cycle);
    }

    /**
     * Get formatted plan type
     */
    public function getFormattedPlanTypeAttribute()
    {
        return self::PLAN_TYPES[$this->plan_type] ?? ucfirst($this->plan_type);
    }

    /**
     * Get formatted support level
     */
    public function getFormattedSupportLevelAttribute()
    {
        return self::SUPPORT_LEVELS[$this->support_level] ?? ucfirst($this->support_level);
    }

    /**
     * Get max bookings display text
     */
    public function getMaxBookingDisplayAttribute()
    {
        if ($this->max_booking === null) {
            return 'Unlimited';
        }
        return $this->max_booking . ' bookings/' . $this->billing_cycle;
    }

    /**
     * Get max studio photographers display text
     */
    public function getMaxStudioPhotographersDisplayAttribute()
    {
        if ($this->user_type !== 'studio') {
            return 'N/A';
        }
        if ($this->max_studio_photographers === null) {
            return 'Unlimited';
        }
        return $this->max_studio_photographers . ' photographers';
    }

    /**
     * Get max studios display text
     */
    public function getMaxStudiosDisplayAttribute()
    {
        if ($this->user_type !== 'studio') {
            return 'N/A';
        }
        if ($this->max_studios === null) {
            return 'Unlimited';
        }
        return $this->max_studios . ' studios';
    }

    /**
     * Get staff limit display text
     */
    public function getStaffLimitDisplayAttribute()
    {
        if ($this->user_type !== 'studio') {
            return 'N/A';
        }
        if ($this->staff_limit === null) {
            return 'Unlimited';
        }
        return $this->staff_limit . ' staff';
    }

    /**
     * Get priority level with label
     */
    public function getPriorityLevelLabelAttribute()
    {
        $levels = [
            0 => 'Normal',
            1 => 'Low Priority',
            2 => 'Medium Priority',
            3 => 'High Priority',
            4 => 'Very High Priority',
            5 => 'Top Priority'
        ];
        
        return $levels[$this->priority_level] ?? 'Level ' . $this->priority_level;
    }

    /**
     * Get priority badge class
     */
    public function getPriorityBadgeClassAttribute()
    {
        $classes = [
            0 => 'badge-soft-secondary',
            1 => 'badge-soft-info',
            2 => 'badge-soft-primary',
            3 => 'badge-soft-warning',
            4 => 'badge-soft-danger',
            5 => 'badge-soft-success',
        ];
        
        return $classes[$this->priority_level] ?? 'badge-soft-secondary';
    }
}