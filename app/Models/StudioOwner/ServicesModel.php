<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicesModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'studio_id',
        'category_id',
        'service_name', // This will now store JSON
        'starting_from',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'service_name' => 'array', // Cast JSON to array
        'starting_from' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the studio that owns the service.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the category that owns the service.
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\Admin\CategoriesModel::class, 'category_id');
    }

    /**
     * Get service names as array.
     */
    public function getServiceNamesAttribute()
    {
        return $this->service_name ?: [];
    }

    /**
     * Set service names as JSON.
     */
    public function setServiceNamesAttribute($value)
    {
        $this->attributes['service_name'] = json_encode($value);
    }
}
