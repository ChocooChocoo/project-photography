<?php

namespace App\Models;

use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use Illuminate\Database\Eloquent\Model;

class BookingCancellationRecoveryModel extends Model
{
    protected $table = 'tbl_booking_cancellation_recoveries';

    protected $fillable = [
        'booking_id', 'studio_id', 'original_assignment_id', 'replacement_assignment_id',
        'status', 'refund_percentage', 'refund_amount', 'deadline', 'replacement_proposed_at', 'replacement_confirmed_at',
        'client_responded_at', 'resolved_at', 'outcome_reason', 'photographer_reason',
    ];

    protected $casts = [
        'refund_percentage' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'deadline' => 'datetime',
        'replacement_proposed_at' => 'datetime',
        'replacement_confirmed_at' => 'datetime',
        'client_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function originalAssignment()
    {
        return $this->belongsTo(BookingAssignedPhotographerModel::class, 'original_assignment_id');
    }

    public function replacementAssignment()
    {
        return $this->belongsTo(BookingAssignedPhotographerModel::class, 'replacement_assignment_id');
    }
}
