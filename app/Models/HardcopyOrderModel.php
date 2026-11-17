<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardcopyOrderModel extends Model
{
    protected $table = 'tbl_booking_hardcopy_orders';

    /** Order statuses that let the owner complete the booking. */
    public const COMPLETION_READY_STATUSES = ['ready', 'shipped', 'delivered'];

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'accepted_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(HardcopyItemModel::class, 'order_id');
    }

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }
}
