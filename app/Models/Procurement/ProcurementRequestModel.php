<?php

namespace App\Models\Procurement;

use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represent a procurement request workflow.
 */
class ProcurementRequestModel extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_FINANCE_REVIEW = 'pending_finance_review';
    public const STATUS_RETURNED_FOR_REVISION = 'returned_for_revision';
    public const STATUS_PENDING_OWNER_APPROVAL = 'pending_owner_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_DEFECT_REPORTED = 'defect_reported';
    public const STATUS_RETURN_IN_PROGRESS = 'return_in_progress';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PAYMENT_PROCESSING = 'payment_processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'tbl_procurement_requests';

    protected $fillable = [
        'request_reference',
        'studio_id',
        'requester_id',
        'requester_role',
        'status',
        'is_urgent',
        'is_high_value',
        'required_date',
        'purpose',
        'inventory_bypass_reason',
        'finance_review_note',
        'owner_review_note',
        'estimated_total',
        'approved_total',
        'invoice_reference',
        'invoice_amount',
        'invoice_date',
        'payment_reference',
        'payment_note',
        'finance_reviewed_by',
        'finance_reviewed_at',
        'owner_reviewed_by',
        'owner_reviewed_at',
        'receipt_confirmed_by',
        'delivered_at',
        'receipt_confirmed_at',
        'payment_processed_at',
        'completed_at',
        'escalated_at',
        'cancelled_at',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'is_high_value' => 'boolean',
        'required_date' => 'date',
        'estimated_total' => 'decimal:2',
        'approved_total' => 'decimal:2',
        'invoice_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'finance_reviewed_at' => 'datetime',
        'owner_reviewed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'receipt_confirmed_at' => 'datetime',
        'payment_processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'escalated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the owning studio.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the requester.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'requester_id');
    }

    /**
     * Get the finance reviewer.
     */
    public function financeReviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'finance_reviewed_by');
    }

    /**
     * Get the owner reviewer.
     */
    public function ownerReviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'owner_reviewed_by');
    }

    /**
     * Get the requester that confirmed receipt.
     */
    public function receiptConfirmer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'receipt_confirmed_by');
    }

    /**
     * Get the request items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequestItemModel::class, 'procurement_request_id');
    }

    /**
     * Get the purchase order.
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(ProcurementPurchaseOrderModel::class, 'procurement_request_id');
    }

    /**
     * Get the documents.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementDocumentModel::class, 'procurement_request_id');
    }

    /**
     * Get the audit trail entries.
     */
    public function auditTrails(): HasMany
    {
        return $this->hasMany(ProcurementAuditTrailModel::class, 'procurement_request_id');
    }

    /**
     * Get defect-return entries.
     */
    public function defectReturns(): HasMany
    {
        return $this->hasMany(ProcurementDefectReturnModel::class, 'procurement_request_id');
    }

    /**
     * Get the CAPEX assets.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(ProcurementAssetModel::class, 'procurement_request_id');
    }

    /**
     * Get the OPEX stock rows touched by this request.
     */
    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(ProcurementInventoryStockModel::class, 'procurement_request_id');
    }

    /**
     * Scope open requests.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Scope overdue requests awaiting escalation.
     */
    public function scopeOverdueForEscalation($query, int $hours)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING_FINANCE_REVIEW,
            self::STATUS_PENDING_OWNER_APPROVAL,
        ])->whereNull('escalated_at')
            ->where('created_at', '<=', now()->subHours($hours));
    }

    /**
     * Determine whether the requester can edit this request.
     */
    public function canBeEditedByRequester(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED_FOR_REVISION,
        ], true);
    }

    /**
     * Determine whether the requester can cancel this request.
     */
    public function canBeCancelledByRequester(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_FINANCE_REVIEW,
            self::STATUS_RETURNED_FOR_REVISION,
        ], true);
    }

    /**
     * Get a friendly status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_FINANCE_REVIEW => 'Pending Finance Review',
            self::STATUS_RETURNED_FOR_REVISION => 'Returned for Revision',
            self::STATUS_PENDING_OWNER_APPROVAL => 'Pending Owner Approval',
            self::STATUS_DEFECT_REPORTED => 'Defect Reported',
            self::STATUS_RETURN_IN_PROGRESS => 'Return In Progress',
            self::STATUS_PAYMENT_PROCESSING => 'Payment Processing',
            default => str($this->status)->replace('_', ' ')->title()->toString(),
        };
    }

    /**
     * Get the badge class used for the current status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_FINANCE_REVIEW,
            self::STATUS_PENDING_OWNER_APPROVAL,
            self::STATUS_RETURNED_FOR_REVISION,
            self::STATUS_DELIVERED,
            self::STATUS_DEFECT_REPORTED,
            self::STATUS_RETURN_IN_PROGRESS,
            self::STATUS_PAYMENT_PROCESSING => 'warning',
            self::STATUS_APPROVED,
            self::STATUS_RECEIVED,
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_ORDERED => 'info',
            default => 'secondary',
        };
    }
}
