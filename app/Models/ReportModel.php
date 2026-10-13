<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportModel extends Model
{
    protected $table = 'tbl_reports';

    protected $guarded = ['id'];

    protected $hidden = ['evidence', 'active_key'];

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function reporter()
    {
        return $this->belongsTo(UserModel::class, 'reporter_id');
    }
}
