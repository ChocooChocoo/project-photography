<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriesModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_name',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the studio packages for this category.
     */
    public function packages()
    {
        return $this->hasMany(\App\Models\StudioOwner\PackagesModel::class, 'category_id');
    }

    /**
     * Get the freelancer packages for this category.
     */
    public function freelancerPackages()
    {
        return $this->hasMany(\App\Models\Freelancer\PackagesModel::class, 'category_id');
    }

    /**
     * Validation rules for category creation.
     *
     * @var array
     */
    public static $rules = [
        'category_name' => 'required|string|max:255|unique:tbl_categories',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ];

    /**
     * Scope a query to only include active categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}