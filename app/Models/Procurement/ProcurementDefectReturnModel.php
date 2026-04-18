<?php

namespace App\Models\Procurement;

use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represent a defective procurement item return cycle.
 */
class ProcurementDefectReturnModel extends Model
{
    public const STATUS_REPORTED = 'reported';
    public const STATUS_RETURN_IN_PROGRESS = 'return_in_progress';
    public const STATUS_REPLACEMENT_DELIVERED = 'replacement_delivered';
    public const STATUS_RESOLVED = 'resolved';

    protected $table = 'tbl_procurement_defect_returns';

    protected $fillable = [
        'procurement_request_id',
        'procurement_request_item_id',
        'reported_by',
        'processed_by',
        'reported_quantity',
        'reason_code',
        'reason_other',
        'requester_note',
        'finance_note',
        'status',
        'reported_at',
        'processed_at',
        'replacement_delivered_at',
        'resolved_at',
    ];

    protected $casts = [
        'reported_quantity' => 'decimal:2',
        'reported_at' => 'datetime',
        'processed_at' => 'datetime',
        'replacement_delivered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the parent procurement request.
     */
    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestModel::class, 'procurement_request_id');
    }

    /**
     * Get the linked procurement item.
     */
    public function procurementRequestItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequestItemModel::class, 'procurement_request_item_id');
    }

    /**
     * Get the requester that reported the defect.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reported_by');
    }

    /**
     * Get the finance user that processed the return.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'processed_by');
    }

    /**
     * Determine whether the defect return is still open.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [
            self::STATUS_REPORTED,
            self::STATUS_RETURN_IN_PROGRESS,
            self::STATUS_REPLACEMENT_DELIVERED,
        ], true);
    }

    /**
     * Get a friendly label for the defect-return status.
     */
    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    /**
     * Get the badge class used for the current defect-return status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RESOLVED => 'success',
            default => 'warning',
        };
    }
}
