<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudioOwner\StudiosModel;

class StudioPlanModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_plans';

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
        'studio_id',
        'plan_id',
        'subscription_reference',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'start_date',
        'end_date',
        'next_billing_date',
        'paid_at',
        'amount_paid',
        'payment_status',
        'status',
        'plan_snapshot',
        'stripe_response',
        'usage_metrics',
        'cancelled_at',
        'cancellation_reason',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'plan_snapshot' => 'array',
        'stripe_response' => 'array',
        'usage_metrics' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status labels
     */
    public const STATUS_LABELS = [
        'active' => 'Active',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        'pending' => 'Pending',
    ];

    /**
     * Payment status labels
     */
    public const PAYMENT_STATUS_LABELS = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    /**
     * Get the studio associated with this subscription
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the plan associated with this subscription
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlanModel::class, 'plan_id');
    }

    /**
     * Generate a unique subscription reference
     */
    public static function generateSubscriptionReference()
    {
        do {
            $reference = 'SUB-' . strtoupper(uniqid());
        } while (self::where('subscription_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->end_date >= now()->toDateString() &&
               $this->payment_status === 'paid';
    }

    /**
     * Check if subscription is expiring soon (within 7 days)
     */
    public function isExpiringSoon()
    {
        if (!$this->isActive()) {
            return false;
        }
        
        $daysUntilExpiry = now()->diffInDays($this->end_date, false);
        return $daysUntilExpiry <= 7 && $daysUntilExpiry >= 0;
    }

    /**
     * Check if this subscription is currently within its free trial period.
     */
    public function isOnTrial()
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    /**
     * Check if the trial is ending soon (within 7 days).
     */
    public function isTrialEndingSoon()
    {
        if (!$this->isOnTrial()) {
            return false;
        }

        $daysUntilTrialEnd = now()->diffInDays($this->trial_ends_at, false);
        return $daysUntilTrialEnd <= 7 && $daysUntilTrialEnd >= 0;
    }

    /**
     * Check if subscription can be cancelled (within 3 days from paid_at or start_date)
     */
    public function canBeCancelled()
    {
        // Must be active and paid
        if ($this->status !== 'active' || $this->payment_status !== 'paid') {
            return false;
        }

        // Get reference date (use paid_at if available, otherwise start_date)
        $referenceDate = $this->paid_at ?? $this->start_date;
        
        // Get cancellation deadline (reference date + 3 days)
        $cancellationDeadline = $referenceDate->copy()->addDays(3)->endOfDay();
        
        // Can cancel if current time is before or on the deadline
        return now()->lte($cancellationDeadline);
    }

    /**
     * Get cancellation deadline date
     */
    public function getCancellationDeadline()
    {
        $referenceDate = $this->paid_at ?? $this->start_date;
        return $referenceDate->copy()->addDays(3);
    }

    /**
     * Update usage metrics
     */
    public function updateUsageMetrics($metrics)
    {
        $currentMetrics = $this->usage_metrics ?? [];
        $this->usage_metrics = array_merge($currentMetrics, $metrics);
        $this->save();
    }

    /**
     * Increment booking count
     */
    public function incrementBookingCount()
    {
        $metrics = $this->usage_metrics ?? [];
        $currentBookings = $metrics['total_bookings'] ?? 0;
        $metrics['total_bookings'] = $currentBookings + 1;
        $metrics['last_booking_at'] = now()->toDateTimeString();
        
        $this->usage_metrics = $metrics;
        $this->save();
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get formatted payment status
     */
    public function getFormattedPaymentStatusAttribute()
    {
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'active' => 'badge-soft-success',
            'expired' => 'badge-soft-secondary',
            'cancelled' => 'badge-soft-danger',
            'pending' => 'badge-soft-warning',
        ];
        
        return $classes[$this->status] ?? 'badge-soft-secondary';
    }

    /**
     * Get payment status badge class
     */
    public function getPaymentStatusBadgeClassAttribute()
    {
        $classes = [
            'paid' => 'badge-soft-success',
            'pending' => 'badge-soft-warning',
            'failed' => 'badge-soft-danger',
            'refunded' => 'badge-soft-secondary',
        ];
        
        return $classes[$this->payment_status] ?? 'badge-soft-secondary';
    }
}