<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRescheduleRequestModel extends Model
{
    public const OPEN_STATUSES = ['pending', 'alternatives_offered'];

    protected $table = 'tbl_booking_reschedule_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'requested_date' => 'date',
        'alternatives' => 'array',
        'responded_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
