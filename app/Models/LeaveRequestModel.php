<?php

namespace App\Models;

use App\Models\StudioOwner\StudiosModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represent an employee leave request record.
 */
class LeaveRequestModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_leave_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_reference',
        'studio_id',
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
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
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the requester of the leave request.
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio associated with the leave request.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the user who approved the leave request.
     */
    public function approver()
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    /**
     * Get the user who rejected the leave request.
     */
    public function rejector()
    {
        return $this->belongsTo(UserModel::class, 'rejected_by');
    }

    /**
     * Get the display label of the leave type.
     *
     * @return string
     */
    public function getLeaveTypeLabelAttribute(): string
    {
        return self::getAvailableLeaveTypes()[$this->leave_type] ?? ucwords(str_replace('_', ' ', $this->leave_type));
    }

    /**
     * Get the display label of the leave request status.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get the available leave types for forms and validation.
     *
     * @return array<string, string>
     */
    public static function getAvailableLeaveTypes(): array
    {
        return [
            'vacation_leave' => 'Vacation Leave',
            'sick_leave' => 'Sick Leave',
            'emergency_leave' => 'Emergency Leave',
            'maternity_leave' => 'Maternity Leave',
            'paternity_leave' => 'Paternity Leave',
            'bereavement_leave' => 'Bereavement Leave',
            'unpaid_leave' => 'Unpaid Leave',
            'other_leave' => 'Other Leave',
        ];
    }
}
