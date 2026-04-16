<?php

namespace App\Models\Procurement;

use App\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represent an audit trail record for procurement actions.
 */
class ProcurementAuditTrailModel extends Model
{
    protected $table = 'tbl_procurement_audit_trails';

    protected $fillable = [
        'procurement_request_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'note',
        'metadata',
    ];

    protected $casts = [
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
     * Get the actor that performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_id');
    }
}
