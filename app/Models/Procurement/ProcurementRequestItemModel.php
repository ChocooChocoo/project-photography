<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represent an item inside a procurement request.
 */
class ProcurementRequestItemModel extends Model
{
    protected $table = 'tbl_procurement_request_items';

    protected $fillable = [
        'procurement_request_id',
        'item_name',
        'normalized_item_name',
        'description',
        'category',
        'expense_type',
        'quantity',
        'unit_of_measure',
        'estimated_unit_cost',
        'estimated_total_cost',
        'approved_unit_cost',
        'approved_total_cost',
        'received_quantity',
        'condition_notes',
        'preferred_supplier',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_cost' => 'decimal:2',
        'estimated_total_cost' => 'decimal:2',
        'approved_unit_cost' => 'decimal:2',
        'approved_total_cost' => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    /**
     * Get the owning procurement request.
     */
    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestModel::class, 'procurement_request_id');
    }

    /**
     * Get the purchase order line items.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(ProcurementPurchaseOrderItemModel::class, 'procurement_request_item_id');
    }

    /**
     * Get the recorded assets.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(ProcurementAssetModel::class, 'procurement_request_item_id');
    }

    /**
     * Get the linked stock rows.
     */
    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(ProcurementInventoryStockModel::class, 'procurement_request_item_id');
    }

    /**
     * Determine whether the item is equipment.
     */
    public function isEquipment(): bool
    {
        return $this->category === 'equipment';
    }
}
