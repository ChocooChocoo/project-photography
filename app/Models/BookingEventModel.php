<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingEventModel extends Model
{
    protected $table = 'tbl_booking_events';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array', 'occurred_at' => 'datetime'];
}
