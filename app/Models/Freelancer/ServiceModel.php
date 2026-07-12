<?php

namespace App\Models\Freelancer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_freelancer_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'services_name',
        'starting_from',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'services_name' => 'array',
        'starting_from' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the service.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Get the categories for the freelancer service.
     */
    public function categories()
    {
        return $this->belongsToMany(
            \App\Models\Admin\CategoriesModel::class,
            'pvt_freelancer_categories',
            'user_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Check if user already has a service entry for THIS CATEGORY
            $exists = static::where('user_id', $model->user_id)
                ->where('category_id', $model->category_id)
                ->exists();
            
            if ($exists) {
                throw new \Exception('You already have services in this category. Please update existing entry.');
            }
        });
    }

    /**
    * Get the category for the service.
    */
    public function category()
    {
        return $this->belongsTo(\App\Models\Admin\CategoriesModel::class, 'category_id');
    }
}