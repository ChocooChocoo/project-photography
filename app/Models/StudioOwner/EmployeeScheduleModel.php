<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudioOwner\UserModel;
use App\Models\StudioOwner\StudiosModel;

class EmployeeScheduleModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_employee_schedule';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'studio_id',
        'operating_days',
        'start_time',
        'end_time',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'operating_days' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user (employee) associated with this schedule.
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio associated with this schedule.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get formatted operating days as string.
     *
     * @return string
     */
    public function getFormattedOperatingDaysAttribute(): string
    {
        $days = $this->operating_days ?? [];
        
        // Ensure $days is always an array
        if (is_string($days)) {
            $decoded = json_decode($days, true);
            $days = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($days)) {
            $days = [];
        }
        
        if (empty($days)) {
            return 'Not set';
        }
        
        $dayMap = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun'
        ];
        
        $formatted = [];
        foreach ($days as $day) {
            $dayLower = strtolower($day);
            if (isset($dayMap[$dayLower])) {
                $formatted[] = $dayMap[$dayLower];
            } else {
                $formatted[] = ucfirst($day);
            }
        }
        
        return implode(', ', $formatted);
    }

    /**
     * Get formatted operating hours.
     *
     * @return string
     */
    public function getFormattedHoursAttribute(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return 'Not set';
        }
        
        try {
            $start = \Carbon\Carbon::parse($this->start_time)->format('h:i A');
            $end = \Carbon\Carbon::parse($this->end_time)->format('h:i A');
            
            return "{$start} - {$end}";
        } catch (\Exception $e) {
            return 'Not set';
        }
    }

    /**
     * Check if employee works on a specific day.
     *
     * @param string $day
     * @return bool
     */
    public function worksOnDay(string $day): bool
    {
        $days = $this->operating_days ?? [];
        
        if (is_string($days)) {
            $days = json_decode($days, true) ?? [];
        }
        
        return in_array(strtolower($day), array_map('strtolower', $days));
    }

    /**
     * Scope to filter by studio.
     */
    public function scopeByStudio($query, $studioId)
    {
        return $query->where('studio_id', $studioId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter schedules that include a specific day.
     */
    public function scopeIncludesDay($query, $day)
    {
        return $query->whereJsonContains('operating_days', strtolower($day));
    }
}