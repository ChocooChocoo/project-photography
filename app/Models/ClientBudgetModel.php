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
        'minimum_budget',
        'maximum_budget',
        'preferred_budget',
        'category_id',
        'budget_type',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'minimum_budget' => 'decimal:2',
        'maximum_budget' => 'decimal:2',
        'preferred_budget' => 'decimal:2',
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
     * Scope a query to filter by budget range.
     */
    public function scopeInBudgetRange($query, $minAmount, $maxAmount)
    {
        return $query->where(function($q) use ($minAmount, $maxAmount) {
            $q->whereBetween('minimum_budget', [$minAmount, $maxAmount])
              ->orWhereBetween('maximum_budget', [$minAmount, $maxAmount])
              ->orWhereBetween('preferred_budget', [$minAmount, $maxAmount]);
        });
    }

    /**
     * Get the formatted minimum budget with currency symbol.
     */
    public function getFormattedMinimumBudgetAttribute(): string
    {
        return $this->minimum_budget ? '₱' . number_format($this->minimum_budget, 2) : '—';
    }

    /**
     * Get the formatted maximum budget with currency symbol.
     */
    public function getFormattedMaximumBudgetAttribute(): string
    {
        return $this->maximum_budget ? '₱' . number_format($this->maximum_budget, 2) : '—';
    }

    /**
     * Get the formatted preferred budget with currency symbol.
     */
    public function getFormattedPreferredBudgetAttribute(): string
    {
        return $this->preferred_budget ? '₱' . number_format($this->preferred_budget, 2) : '—';
    }

    /**
     * Get the budget range as formatted string.
     */
    public function getBudgetRangeAttribute(): string
    {
        if ($this->minimum_budget && $this->maximum_budget) {
            return '₱' . number_format($this->minimum_budget, 2) . ' - ₱' . number_format($this->maximum_budget, 2);
        } elseif ($this->minimum_budget) {
            return 'From ₱' . number_format($this->minimum_budget, 2);
        } elseif ($this->maximum_budget) {
            return 'Up to ₱' . number_format($this->maximum_budget, 2);
        } elseif ($this->preferred_budget) {
            return '₱' . number_format($this->preferred_budget, 2) . ' (Preferred)';
        }
        
        return 'Not specified';
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