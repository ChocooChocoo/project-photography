<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Model;

class StudioEquipmentModel extends Model
{
    protected $table = 'tbl_studio_equipment';
    protected $fillable = ['studio_id', 'name', 'type', 'brand', 'model', 'status'];

    public function assignments()
    {
        return $this->hasMany(BookingEquipmentModel::class, 'equipment_id');
    }
}
