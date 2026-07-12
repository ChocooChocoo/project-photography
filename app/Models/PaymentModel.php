<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;

    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'tbl_payments';

    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'booking_id',
        'payment_reference',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'paymongo_payment_id',
        'paymongo_source_id',
        'amount',
        'payment_method',
        'status',
        'payment_details',
        'paid_at',
    ];

    /**
    * The attributes that should be cast.
    *
    * @var array<string, string>
    */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
    * Get the booking associated with the payment.
    */
    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    /**
    * Generate a unique payment reference.
    */
    public static function generatePaymentReference()
    {
        do {
            $reference = 'PAY-' . strtoupper(uniqid());
        } while (self::where('payment_reference', $reference)->exists());

        return $reference;
    }

    /**
    * Check if payment is successful.
    */
    public function isSuccessful()
    {
        return $this->status === 'succeeded';
    }

    /**
    * ========== FIXED: Mark payment as paid and update booking ==========
    */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
        
        // Update booking payment status and remaining balance
        if ($this->booking) {
            $this->booking->updatePaymentStatus();
        }
    }

    /**
    * Check if payment is pending.
    */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
    * Check if payment is failed.
    */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
    * Get payment details as array.
    */
    public function getPaymentDetailsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        
        return $value ?: [];
    }

    /**
    * Set payment details as JSON.
    */
    public function setPaymentDetailsAttribute($value)
    {
        $this->attributes['payment_details'] = is_string($value) ? $value : json_encode($value);
    }

    /**
    * Get the system revenue records for this payment.
    */
    public function revenueRecords()
    {
        return $this->hasMany(SystemRevenueModel::class, 'payment_id');
    }

    /**
    * Check if payment has been processed for revenue.
    */
    public function hasRevenueRecord()
    {
        return $this->revenueRecords()->exists();
    }
}