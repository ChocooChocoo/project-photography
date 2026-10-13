<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardcopyItemModel extends Model
{
    protected $table = 'tbl_booking_hardcopy_items';

    protected $guarded = ['id'];

    protected $casts = ['unit_price' => 'decimal:2', 'quantity' => 'integer'];
}
