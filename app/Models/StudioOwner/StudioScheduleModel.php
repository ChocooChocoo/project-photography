<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Model;

class StudioScheduleModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'studio_id',
        'location_id',
        'operating_days',
        'opening_time',
        'closing_time',
        'booking_limit',
        'advance_booking',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'operating_days' => 'array',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Null when an in-studio session from $startTime to $endTime (H:i) fits the
     * opening hours; otherwise the message to show the client.
     */
    public function outsideHoursMessage(string $startTime, string $endTime): ?string
    {
        $open = substr((string) $this->getRawOriginal('opening_time'), 0, 5);
        $close = substr((string) $this->getRawOriginal('closing_time'), 0, 5);

        if ($open === '' || $close === '' || ($startTime >= $open && $endTime <= $close)) {
            return null;
        }

        return 'The studio is open '.\Carbon\Carbon::parse($open)->format('g:i A').' to '.\Carbon\Carbon::parse($close)->format('g:i A')
            .'. Choose a time within those hours, or choose On-Location.';
    }

    /**
     * Get the studio associated with the schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the location associated with the schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(\App\Models\Admin\LocationModel::class, 'location_id');
    }

    /**
     * Get operating days as formatted string.
     *
     * @return string
     */
    public function getFormattedOperatingDaysAttribute()
    {
        $days = $this->operating_days ?? [];
        
        // Ensure $days is always an array
        if (is_string($days)) {
            // Try to decode if it's a JSON string
            $decoded = json_decode($days, true);
            $days = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($days)) {
            $days = [];
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
            if (isset($dayMap[strtolower($day)])) {
                $formatted[] = $dayMap[strtolower($day)];
            }
        }
        
        return implode(', ', $formatted);
    }

    /**
     * Get operating hours as formatted string.
     *
     * @return string
     */
    public function getFormattedOperatingHoursAttribute()
    {
        $opening = \Carbon\Carbon::parse($this->opening_time)->format('h:iA');
        $closing = \Carbon\Carbon::parse($this->closing_time)->format('h:iA');
        return $opening . ' - ' . $closing;
    }

    /**
     * Get coverage area as formatted string.
     *
     * @return string
     */
    public function getFormattedCoverageAreaAttribute()
    {
        $areas = $this->coverage_area ?? [];
        
        // Ensure $areas is always an array
        if (is_string($areas)) {
            // Try to decode if it's a JSON string
            $decoded = json_decode($areas, true);
            $areas = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($areas)) {
            $areas = [];
        }
        
        return implode(', ', $areas);
    }
}