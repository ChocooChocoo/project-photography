<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represent a purchase order line item.
 */
class ProcurementPurchaseOrderItemModel extends Model
{
    protected $table = 'tbl_procurement_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'procurement_request_item_id',
        'item_name',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the owning purchase order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(ProcurementPurchaseOrderModel::class, 'purchase_order_id');
    }

    /**
     * Get the linked request item.
     */
    public function procurementRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItemModel::class, 'procurement_request_item_id');
    }
}
