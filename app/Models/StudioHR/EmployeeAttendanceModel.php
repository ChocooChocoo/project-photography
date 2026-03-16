<?php

namespace App\Models\StudioHR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\EmployeeScheduleModel;

class EmployeeAttendanceModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_employee_attendance';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'studio_id',
        'schedule_id',
        'attendance_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'check_in_time',
        'check_out_time',
        'check_in_image',
        'check_out_image',
        'check_in_status',
        'check_out_status',
        'late_minutes',
        'undertime_minutes',
        'check_in_ip',
        'check_out_ip',
        'check_in_user_agent',
        'check_out_user_agent',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'late_minutes' => 'integer',
        'undertime_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee associated with this attendance.
     */
    public function employee()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio associated with this attendance.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the schedule associated with this attendance.
     */
    public function schedule()
    {
        return $this->belongsTo(EmployeeScheduleModel::class, 'schedule_id');
    }

    /**
     * Check if employee has checked in.
     */
    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_time);
    }

    /**
     * Check if employee has checked out.
     */
    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_time);
    }

    /**
     * Get total hours worked.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        $duration = $this->check_in_time->diff($this->check_out_time);
        return $duration->format('%H:%I:%S');
    }

    /**
     * Get formatted check-in time.
     */
    public function getFormattedCheckInAttribute(): string
    {
        return $this->check_in_time ? $this->check_in_time->format('h:i A') : '—';
    }

    /**
     * Get formatted check-out time.
     */
    public function getFormattedCheckOutAttribute(): string
    {
        return $this->check_out_time ? $this->check_out_time->format('h:i A') : '—';
    }

    /**
     * Get status badge class for check-in (already exists, but ensure this method is present)
     */
    public function getCheckInStatusBadgeAttribute(): string
    {
        $classes = [
            'ON_TIME' => 'badge-soft-success',
            'LATE' => 'badge-soft-warning',
        ];

        return $classes[$this->check_in_status] ?? 'badge-soft-secondary';
    }

    /**
     * Get status badge class for check-out.
     */
    public function getCheckOutStatusBadgeAttribute(): string
    {
        $classes = [
            'ON_TIME' => 'badge-soft-success',
            'UNDERTIME' => 'badge-soft-danger',
        ];

        return $classes[$this->check_out_status] ?? 'badge-soft-secondary';
    }

    /**
     * Scope for today's attendance.
     */
    public function scopeForToday($query)
    {
        return $query->whereDate('attendance_date', now()->toDateString());
    }

    /**
     * Scope for specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    /**
     * Scope for specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific studio.
     */
    public function scopeForStudio($query, $studioId)
    {
        return $query->where('studio_id', $studioId);
    }

    /**
     * Scope for late check-ins.
     */
    public function scopeLate($query)
    {
        return $query->where('check_in_status', 'LATE');
    }

    /**
     * Scope for undertime check-outs.
     */
    public function scopeUndertime($query)
    {
        return $query->where('check_out_status', 'UNDERTIME');
    }

    /**
     * Scope for on-time check-ins.
     */
    public function scopeOnTime($query)
    {
        return $query->where('check_in_status', 'ON_TIME');
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('attendance_date', now()->month)
                     ->whereYear('attendance_date', now()->year);
    }

    /**
     * Get late minutes with suffix.
     */
    public function getLateDisplayAttribute(): string
    {
        if ($this->late_minutes > 0) {
            return $this->late_minutes . ' min late';
        }
        return '—';
    }

    /**
     * Get undertime minutes with suffix.
     */
    public function getUndertimeDisplayAttribute(): string
    {
        if ($this->undertime_minutes > 0) {
            return $this->undertime_minutes . ' min early';
        }
        return '—';
    }

    /**
     * Check if attendance is complete (both check-in and check-out done).
     */
    public function isComplete(): bool
    {
        return $this->isCheckedIn() && $this->isCheckedOut();
    }

    /**
     * Calculate worked hours in decimal format.
     */
    public function getWorkedHoursDecimalAttribute(): float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return 0;
        }

        $minutes = $this->check_in_time->diffInMinutes($this->check_out_time);
        return round($minutes / 60, 2);
    }
}