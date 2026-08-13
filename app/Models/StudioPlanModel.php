<?php

namespace App\Models;

use App\Models\StudioOwner\StudiosModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_invoice_id',
        'stripe_first_failure_invoice_id',
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
        'scheduled_cancellation_at',
        'first_failure_at',
        'cancellation_reason',
        'trial_ends_at',
        'grace_ends_at',
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
        'scheduled_cancellation_at' => 'datetime',
        'first_failure_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
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
        'grace' => 'Grace Period',
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
            $reference = 'SUB-'.strtoupper(uniqid());
        } while (self::where('subscription_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active' || $this->payment_status !== 'paid') {
            return false;
        }

        if ($this->trial_ends_at !== null) {
            return now()->lt($this->trial_ends_at);
        }

        return $this->end_date >= now()->toDateString();
    }

    /**
     * Limit the query to subscriptions whose effective period has not ended.
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>', now());
                    })
                    ->orWhere(function (Builder $query) {
                        $query->whereNull('trial_ends_at')
                            ->whereDate('end_date', '>=', now()->toDateString());
                    });
            });
    }

    /**
     * Limit the query to subscriptions that still grant studio access.
     */
    public function scopeCurrentlyAccessible(Builder $query): Builder
    {
        return $query
            ->where('payment_status', 'paid')
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query->where('status', 'grace')
                            ->where('grace_ends_at', '>', now());
                    })
                    ->orWhere(function (Builder $query) {
                        $query->where('status', 'active')
                            ->where(function (Builder $query) {
                                $query
                                    ->where(function (Builder $query) {
                                        $query->whereNotNull('trial_ends_at')
                                            ->where('trial_ends_at', '>', now()->subDays(7));
                                    })
                                    ->orWhere(function (Builder $query) {
                                        $query->whereNull('trial_ends_at')
                                            ->whereDate('end_date', '>=', now()->subDays(7)->toDateString());
                                    });
                            });
                    });
            });
    }

    /**
     * Get the contractual end of the seven-day grace period.
     */
    public function graceDeadline(): \Illuminate\Support\Carbon
    {
        if ($this->grace_ends_at !== null) {
            return $this->grace_ends_at->copy();
        }

        if ($this->trial_ends_at !== null) {
            return $this->trial_ends_at->copy()->addDays(7);
        }

        return $this->end_date->copy()->addDays(8)->startOfDay();
    }

    /**
     * Determine whether this subscription still grants studio access.
     */
    public function hasAccess(): bool
    {
        return in_array($this->status, ['active', 'grace'], true)
            && $this->payment_status === 'paid'
            && now()->lt($this->graceDeadline());
    }

    /**
     * Determine whether the subscription is inside its recorded grace period.
     */
    public function isInGrace(): bool
    {
        return $this->status === 'grace' && $this->hasAccess();
    }

    /**
     * Get whole grace days remaining, rounding partial days up.
     */
    public function graceDaysRemaining(): int
    {
        $seconds = $this->graceDeadline()->getTimestamp() - now()->getTimestamp();

        return max(0, (int) ceil($seconds / 86400));
    }

    /**
     * Check if subscription is expiring soon (within 7 days)
     */
    public function isExpiringSoon()
    {
        if (! $this->isActive()) {
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
        if (! $this->isOnTrial()) {
            return false;
        }

        $daysUntilTrialEnd = now()->diffInDays($this->trial_ends_at, false);

        return $daysUntilTrialEnd <= 7 && $daysUntilTrialEnd >= 0;
    }

    /** Determine whether the owner may schedule period-end cancellation. */
    public function canBeCancelled()
    {
        return in_array($this->status, ['active', 'grace'], true)
            && $this->payment_status === 'paid'
            && $this->scheduled_cancellation_at === null;
    }

    /**
     * Get cancellation deadline date
     */
    public function getCancellationDeadline()
    {
        return $this->scheduled_cancellation_at ?? $this->end_date->copy()->endOfDay();
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
            'grace' => 'badge-soft-warning',
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
