<?php

namespace App\Models\Procurement;

use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represent a recorded CAPEX asset from procurement.
 */
class ProcurementAssetModel extends Model
{
    protected $table = 'tbl_procurement_assets';

    protected $fillable = [
        'procurement_request_id',
        'procurement_request_item_id',
        'studio_id',
        'recorded_by',
        'asset_name',
        'serial_number',
        'warranty_expires_at',
        'acquisition_cost',
        'location',
        'status',
    ];

    protected $casts = [
        'warranty_expires_at' => 'date',
        'acquisition_cost' => 'decimal:2',
    ];

    /**
     * Get the parent procurement request.
     */
    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestModel::class, 'procurement_request_id');
    }

    /**
     * Get the parent request item.
     */
    public function procurementRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItemModel::class, 'procurement_request_item_id');
    }

    /**
     * Get the studio that owns the asset.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the recorder.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'recorded_by');
    }
}
