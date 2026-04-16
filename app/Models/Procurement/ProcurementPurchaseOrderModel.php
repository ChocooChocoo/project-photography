<?php

namespace App\Models\Procurement;

use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represent a procurement purchase order.
 */
class ProcurementPurchaseOrderModel extends Model
{
    protected $table = 'tbl_procurement_purchase_orders';

    protected $fillable = [
        'procurement_request_id',
        'po_number',
        'supplier_name',
        'supplier_email',
        'supplier_contact_number',
        'supplier_address',
        'delivery_address',
        'payment_terms',
        'order_date',
        'total_amount',
        'notes',
        'ordered_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the parent procurement request.
     */
    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestModel::class, 'procurement_request_id');
    }

    /**
     * Get the finance user that ordered the PO.
     */
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'ordered_by');
    }

    /**
     * Get the PO line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProcurementPurchaseOrderItemModel::class, 'purchase_order_id');
    }

    /**
     * Get the related documents.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementDocumentModel::class, 'purchase_order_id');
    }
}
