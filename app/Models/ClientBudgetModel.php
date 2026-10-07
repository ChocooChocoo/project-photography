<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserModel;
use App\Models\Admin\CategoriesModel;

class ClientBudgetModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_client_budget';

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
        'client_id',
        'budget_name',
        'description',
        'preferred_budget',
        'spent_amount',
        'category_id',
        'budget_type',
        'status',
    ];

    /**
     * Accessors included when the model is serialised to JSON (used by the budget page JS).
     *
     * @var array<int, string>
     */
    protected $appends = ['formatted_preferred_budget', 'formatted_spent_amount', 'budget_range'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferred_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the client (user) that owns this budget.
     */
    public function client()
    {
        return $this->belongsTo(UserModel::class, 'client_id');
    }

    /**
     * Get the category associated with this budget.
     */
    public function category()
    {
        return $this->belongsTo(CategoriesModel::class, 'category_id');
    }

    /**
     * Scope a query to only include active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive budgets.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to filter by client.
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Get the formatted preferred budget with currency symbol.
     */
    public function getFormattedPreferredBudgetAttribute(): string
    {
        return $this->preferred_budget ? '₱' . number_format($this->preferred_budget, 2) : '—';
    }

    /**
     * Get the formatted spent amount with currency symbol.
     */
    public function getFormattedSpentAmountAttribute(): string
    {
        return '₱' . number_format($this->spent_amount ?? 0, 2);
    }

    /**
     * Get the budget as a formatted string.
     */
    public function getBudgetRangeAttribute(): string
    {
        return $this->preferred_budget ? '₱' . number_format($this->preferred_budget, 2) : 'Not specified';
    }

    /**
     * Get status badge class for UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === 'active' 
            ? 'badge-soft-success' 
            : 'badge-soft-secondary';
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active' ? 'Active' : 'Inactive';
    }
}