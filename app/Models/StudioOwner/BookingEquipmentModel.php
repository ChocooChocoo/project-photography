<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingEquipmentModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_booking_equipment';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'equipment_id',
        'brand',
        'assignment_id',
        'equipment_name',
        'equipment_type',
        'notes',
        'confirmed',
        'confirmed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = ['brand_mismatch'];

    /**
     * True when the item has a brand and the client asked for a different one.
     * This only warns; it never blocks an assignment.
     */
    public function getBrandMismatchAttribute(): bool
    {
        $brand = mb_strtolower(trim((string) $this->brand));
        $preferred = mb_strtolower(trim((string) $this->booking?->preferred_camera_brand));

        return $brand !== '' && $preferred !== '' && $brand !== $preferred;
    }

    /**
     * Get the booking this equipment belongs to.
     */
    public function booking()
    {
        return $this->belongsTo(\App\Models\BookingModel::class, 'booking_id');
    }

    /**
     * Get the photographer assignment this equipment is linked to.
     */
    public function assignment()
    {
        return $this->belongsTo(\App\Models\StudioOwner\BookingAssignedPhotographerModel::class, 'assignment_id');
    }
}
