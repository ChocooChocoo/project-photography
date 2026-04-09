<?php

namespace App\Models\StudioPhotographer;

use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotographerAttendanceModel extends Model
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
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance_meters',
        'check_in_location_status',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
        'check_out_location_status',
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
        'check_in_latitude' => 'decimal:7',
        'check_in_longitude' => 'decimal:7',
        'check_in_distance_meters' => 'integer',
        'check_out_latitude' => 'decimal:7',
        'check_out_longitude' => 'decimal:7',
        'check_out_distance_meters' => 'integer',
        'late_minutes' => 'integer',
        'undertime_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the photographer associated with this attendance.
     */
    public function photographer()
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
     * Determine whether the photographer has checked in.
     */
    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_time);
    }

    /**
     * Determine whether the photographer has checked out.
     */
    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_time);
    }

    /**
     * Get the worked duration for the attendance record.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        return $this->check_in_time->diff($this->check_out_time)->format('%H:%I:%S');
    }

    /**
     * Get the formatted check-in time.
     */
    public function getFormattedCheckInAttribute(): string
    {
        return $this->check_in_time ? $this->check_in_time->format('h:i A') : '-';
    }

    /**
     * Get the formatted check-out time.
     */
    public function getFormattedCheckOutAttribute(): string
    {
        return $this->check_out_time ? $this->check_out_time->format('h:i A') : '-';
    }

    /**
     * Get the badge class for check-in status.
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
     * Get the badge class for check-out status.
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
     * Scope attendance records for a specific user.
     *
     * @param mixed $query
     * @param int $userId
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope attendance records for a specific studio.
     *
     * @param mixed $query
     * @param int $studioId
     */
    public function scopeForStudio($query, int $studioId)
    {
        return $query->where('studio_id', $studioId);
    }

    /**
     * Scope attendance records for a specific date.
     *
     * @param mixed $query
     * @param string $date
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    /**
     * Get late minutes display text.
     */
    public function getLateDisplayAttribute(): string
    {
        return $this->late_minutes > 0 ? $this->late_minutes . ' min late' : '-';
    }

    /**
     * Get undertime minutes display text.
     */
    public function getUndertimeDisplayAttribute(): string
    {
        return $this->undertime_minutes > 0 ? $this->undertime_minutes . ' min early' : '-';
    }
}
