<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemRevenueModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_system_revenue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_reference',
        'booking_id',
        'payment_id',
        'subscription_id',
        'stripe_invoice_id',
        'revenue_type',
        'total_amount',
        'platform_fee_percentage',
        'platform_fee_amount',
        'provider_amount',
        'provider_type',
        'provider_id',
        'client_id',
        'status',
        'breakdown',
        'settled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_fee_percentage' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'breakdown' => 'array',
        'settled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the booking associated with the revenue.
     */
    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    /**
     * Get the payment associated with the revenue.
     */
    public function payment()
    {
        return $this->belongsTo(PaymentModel::class, 'payment_id');
    }

    /**
     * NEW: Get the subscription associated with the revenue.
     */
    public function subscription()
    {
        return $this->belongsTo(StudioPlanModel::class, 'subscription_id');
    }

    /**
     * Get the client who made the booking/subscription.
     */
    public function client()
    {
        return $this->belongsTo(UserModel::class, 'client_id');
    }

    /**
     * Get the provider (studio or freelancer).
     */
    public function provider()
    {
        if ($this->provider_type === 'studio') {
            return $this->belongsTo(\App\Models\StudioOwner\StudiosModel::class, 'provider_id');
        } else {
            return $this->belongsTo(\App\Models\Freelancer\ProfileModel::class, 'provider_id', 'user_id');
        }
    }

    /**
     * Generate a unique transaction reference.
     */
    public static function generateTransactionReference()
    {
        do {
            $reference = 'REV-'.strtoupper(uniqid());
        } while (self::where('transaction_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Calculate revenue split for a given amount
     */
    public static function calculateRevenueSplit($amount, $feePercentage = 10.00)
    {
        $platformFee = ($amount * $feePercentage) / 100;
        $providerAmount = $amount - $platformFee;

        return [
            'total_amount' => (float) $amount,
            'platform_fee_percentage' => (float) $feePercentage,
            'platform_fee_amount' => round($platformFee, 2),
            'provider_amount' => round($providerAmount, 2),
        ];
    }

    /**
     * Create revenue record for a booking payment
     */
    public static function createForPayment($booking, $payment)
    {
        try {
            // Calculate revenue split
            $revenueSplit = self::calculateRevenueSplit($payment->amount, 10.00);

            // Determine provider type and ID
            if ($booking->booking_type === 'studio') {
                $providerType = 'studio';
                $providerId = $booking->provider_id;
            } else {
                $providerType = 'freelancer';
                $providerId = $booking->provider_id;
            }

            // Create revenue record
            $revenue = self::create([
                'transaction_reference' => self::generateTransactionReference(),
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'revenue_type' => 'booking',
                'total_amount' => $revenueSplit['total_amount'],
                'platform_fee_percentage' => $revenueSplit['platform_fee_percentage'],
                'platform_fee_amount' => $revenueSplit['platform_fee_amount'],
                'provider_amount' => $revenueSplit['provider_amount'],
                'provider_type' => $providerType,
                'provider_id' => $providerId,
                'client_id' => $booking->client_id,
                'status' => 'completed',
                'breakdown' => [
                    'booking_reference' => $booking->booking_reference,
                    'payment_reference' => $payment->payment_reference,
                    'payment_type' => $booking->payment_type,
                    'platform_fee_percentage' => '10%',
                    'calculation' => [
                        'total_payment' => $payment->amount,
                        'platform_fee' => $revenueSplit['platform_fee_amount'],
                        'provider_earnings' => $revenueSplit['provider_amount'],
                    ],
                    'booking_summary' => [
                        'total_amount' => $booking->total_amount,
                        'down_payment' => $booking->down_payment,
                        'remaining_balance' => $booking->remaining_balance,
                    ],
                ],
                'settled_at' => now(),
            ]);

            \Log::info('Revenue record created for booking', [
                'revenue_id' => $revenue->id,
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'platform_fee' => $revenueSplit['platform_fee_amount'],
                'provider_amount' => $revenueSplit['provider_amount'],
            ]);

            return $revenue;

        } catch (\Exception $e) {
            \Log::error('Failed to create revenue record for booking', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id ?? null,
                'payment_id' => $payment->id ?? null,
            ]);

            return null;
        }
    }

    /**
     * NEW: Create revenue record for a studio subscription
     */
    public static function createForSubscription($studioPlan, $studio)
    {
        try {
            if ($studioPlan->stripe_invoice_id) {
                $existing = self::where('stripe_invoice_id', $studioPlan->stripe_invoice_id)->first();
                if ($existing) {
                    return $existing;
                }
            }
            // Get the plan from the snapshot or relationship
            $plan = $studioPlan->plan ?? null;
            $planData = $studioPlan->plan_snapshot ?? ($plan ? $plan->toArray() : []);

            // Calculate revenue split (same 10% platform fee)
            $revenueSplit = self::calculateRevenueSplit($studioPlan->amount_paid, 10.00);

            // Create revenue record
            try {
                $revenue = self::create([
                    'transaction_reference' => self::generateTransactionReference(),
                    'subscription_id' => $studioPlan->id,
                    'stripe_invoice_id' => $studioPlan->stripe_invoice_id,
                    'revenue_type' => 'subscription',
                    'total_amount' => $revenueSplit['total_amount'],
                    'platform_fee_percentage' => $revenueSplit['platform_fee_percentage'],
                    'platform_fee_amount' => $revenueSplit['platform_fee_amount'],
                    'provider_amount' => $revenueSplit['provider_amount'],
                    'provider_type' => 'studio',
                    'provider_id' => $studio->id,
                    'client_id' => $studio->user_id, // Studio owner is the client
                    'status' => 'completed',
                    'breakdown' => [
                        'subscription_reference' => $studioPlan->subscription_reference,
                        'plan_name' => $planData['name'] ?? 'Unknown Plan',
                        'plan_type' => $planData['plan_type'] ?? 'N/A',
                        'billing_cycle' => $planData['billing_cycle'] ?? 'N/A',
                        'platform_fee_percentage' => '10%',
                        'calculation' => [
                            'total_payment' => $studioPlan->amount_paid,
                            'platform_fee' => $revenueSplit['platform_fee_amount'],
                            'provider_earnings' => $revenueSplit['provider_amount'],
                        ],
                        'subscription_period' => [
                            'start_date' => $studioPlan->start_date->format('Y-m-d'),
                            'end_date' => $studioPlan->end_date->format('Y-m-d'),
                        ],
                    ],
                    'settled_at' => now(),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($studioPlan->stripe_invoice_id && $existing = self::where('stripe_invoice_id', $studioPlan->stripe_invoice_id)->first()) {
                    return $existing;
                }
                throw $e;
            }

            \Log::info('Revenue record created for subscription', [
                'revenue_id' => $revenue->id,
                'subscription_id' => $studioPlan->id,
                'studio_id' => $studio->id,
                'amount' => $studioPlan->amount_paid,
                'platform_fee' => $revenueSplit['platform_fee_amount'],
                'provider_amount' => $revenueSplit['provider_amount'],
            ]);

            return $revenue;

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to create revenue record for subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $studioPlan->id ?? null,
                'studio_id' => $studio->id ?? null,
            ]);

            return null;
        }
    }

    /**
     * Check if revenue is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Mark revenue as settled.
     */
    public function markAsSettled()
    {
        $this->update([
            'status' => 'completed',
            'settled_at' => now(),
        ]);
    }

    /**
     * Mark revenue as refunded.
     */
    public function markAsRefunded()
    {
        $this->update([
            'status' => 'refunded',
        ]);
    }

    /**
     * Get formatted status with badge class.
     */
    public function getStatusBadgeClass()
    {
        $classes = [
            'pending' => 'badge-soft-warning',
            'completed' => 'badge-soft-success',
            'refunded' => 'badge-soft-danger',
            'cancelled' => 'badge-soft-secondary',
        ];

        return $classes[$this->status] ?? 'badge-soft-secondary';
    }
}
