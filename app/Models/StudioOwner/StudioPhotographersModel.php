<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudioPhotographersModel extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_photographers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'studio_id',
        'owner_id',
        'photographer_id',
        'position',
        'specialization', // This should be clarified - is this FK to tbl_categories.id or tbl_services.id?
        'years_of_experience',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the studio associated with the photographer.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the owner who added the photographer.
     */
    public function owner()
    {
        return $this->belongsTo(\App\Models\StudioOwner\UserModel::class, 'owner_id');
    }

    /**
     * Get the photographer user details.
     */
    public function photographer()
    {
        return $this->belongsTo(\App\Models\StudioOwner\UserModel::class, 'photographer_id');
    }

    /**
     * Get the service that is the photographer's specialization.
     * Remove this if specialization points to tbl_categories.id
     */
    public function specializationService()
    {
        return $this->belongsTo(\App\Models\StudioOwner\ServicesModel::class, 'specialization');
    }

    /**
     * Check if photographer is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get the category that is the photographer's specialization.
     * Use this if specialization points to tbl_categories.id
     */
    public function specializationCategory()
    {
        return $this->belongsTo(\App\Models\Admin\CategoriesModel::class, 'specialization');
    }
}