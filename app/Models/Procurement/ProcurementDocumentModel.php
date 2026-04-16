<?php

namespace App\Models\Procurement;

use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represent a procurement-related uploaded document.
 */
class ProcurementDocumentModel extends Model
{
    protected $table = 'tbl_procurement_documents';

    protected $fillable = [
        'procurement_request_id',
        'purchase_order_id',
        'uploaded_by',
        'document_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the parent procurement request.
     */
    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestModel::class, 'procurement_request_id');
    }

    /**
     * Get the related purchase order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(ProcurementPurchaseOrderModel::class, 'purchase_order_id');
    }

    /**
     * Get the uploader.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'uploaded_by');
    }
}
