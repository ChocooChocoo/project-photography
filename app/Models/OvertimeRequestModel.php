<?php

namespace App\Models;

use App\Models\StudioOwner\StudiosModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represent an employee overtime request record.
 */
class OvertimeRequestModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_overtime_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_reference',
        'studio_id',
        'user_id',
        'overtime_date',
        'start_time',
        'end_time',
        'total_hours',
        'reason',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'overtime_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_hours' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the requester of the overtime request.
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio associated with the overtime request.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the user who approved the overtime request.
     */
    public function approver()
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    /**
     * Get the user who rejected the overtime request.
     */
    public function rejector()
    {
        return $this->belongsTo(UserModel::class, 'rejected_by');
    }

    /**
     * Get the display label of the overtime request status.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
