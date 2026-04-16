<?php

namespace App\Models\Procurement;

use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represent a consumable inventory stock row.
 */
class ProcurementInventoryStockModel extends Model
{
    protected $table = 'tbl_procurement_inventory_stocks';

    protected $fillable = [
        'studio_id',
        'procurement_request_id',
        'procurement_request_item_id',
        'created_by',
        'updated_by',
        'item_name',
        'normalized_item_name',
        'description',
        'unit_of_measure',
        'stock_quantity',
        'reorder_threshold',
        'last_recorded_cost',
        'last_received_at',
    ];

    protected $casts = [
        'stock_quantity' => 'decimal:2',
        'reorder_threshold' => 'decimal:2',
        'last_recorded_cost' => 'decimal:2',
        'last_received_at' => 'datetime',
    ];

    /**
     * Get the studio that owns the stock row.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the source procurement request.
     */
    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestModel::class, 'procurement_request_id');
    }

    /**
     * Get the source request item.
     */
    public function procurementRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItemModel::class, 'procurement_request_item_id');
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    /**
     * Get the updater.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'updated_by');
    }
}
