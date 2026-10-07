<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingContractModel extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_FULLY_SIGNED = 'fully_signed';

    protected $table = 'tbl_booking_contracts';

    protected $fillable = [
        'booking_id',
        'terms_html',
        'studio_signature',
        'studio_signed_by',
        'studio_signed_user_id',
        'studio_signed_at',
        'client_signature',
        'client_signed_by',
        'client_signed_at',
        'status',
    ];

    protected $casts = [
        'studio_signed_at' => 'datetime',
        'client_signed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function isFullySigned(): bool
    {
        return $this->studio_signed_at !== null && $this->client_signed_at !== null;
    }
}
