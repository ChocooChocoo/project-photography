<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardcopyOrderModel extends Model
{
    protected $table = 'tbl_booking_hardcopy_orders';

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
