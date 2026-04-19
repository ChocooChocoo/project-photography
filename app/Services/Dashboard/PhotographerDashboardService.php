<?php

namespace App\Services\Dashboard;

use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\UserModel;

/**
 * Build analytics for the studio photographer dashboard.
 */
class PhotographerDashboardService extends BaseDashboardService
{
    /**
     * Build the photographer dashboard payload.
     *
     * @param  \App\Models\UserModel  $user
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function build(UserModel $user, array $input = []): array
    {
        $filters = $this->resolveFilters($input);
        [$startDate, $endDate] = $this->getBounds($filters);
        $granularity = $this->getTrendGranularity($startDate, $endDate);
        $labels = $this->buildTrendLabels($startDate, $endDate, $granularity);

        $assignments = BookingAssignedPhotographerModel::query()
            ->where('photographer_id', $user->id)
            ->whereBetween('assigned_at', [$startDate, $endDate])
            ->with(['booking.client', 'studio'])
            ->get();

        $upcomingAssignments = BookingAssignedPhotographerModel::query()
            ->where('photographer_id', $user->id)
            ->whereIn('status', ['assigned', 'confirmed', 'on_site', 'in_progress'])
            ->whereHas('booking', function ($query) {
                $query->whereDate('event_date', '>=', now()->toDateString());
            })
            ->with(['booking.client', 'studio'])
            ->orderBy('assigned_at')
            ->get();

        $completionTrend = $this->groupTrendValues(
            $assignments->where('status', 'completed'),
            fn (BookingAssignedPhotographerModel $assignment) => $assignment->completed_at ?? $assignment->updated_at,
            null,
            $granularity
        );

        $statusLabels = ['Assigned', 'Confirmed', 'On Site', 'In Progress', 'Completed', 'Cancelled'];
        $statusValues = [
            $assignments->where('status', 'assigned')->count(),
            $assignments->where('status', 'confirmed')->count(),
            $assignments->where('status', 'on_site')->count(),
            $assignments->where('status', 'in_progress')->count(),
            $assignments->where('status', 'completed')->count(),
            $assignments->where('status', 'cancelled')->count(),
        ];

        $completionRate = $assignments->count() > 0
            ? ($assignments->where('status', 'completed')->count() / $assignments->count()) * 100
            : 0;

        $scheduleRows = $upcomingAssignments
            ->take(8)
            ->map(function (BookingAssignedPhotographerModel $assignment): array {
                return [
                    optional($assignment->booking)->booking_reference ?? 'N/A',
                    optional($assignment->studio)->studio_name ?? 'N/A',
                    optional(optional($assignment->booking)->event_date)?->format('M d, Y') ?? 'N/A',
                    ucfirst(str_replace('_', ' ', (string) $assignment->status)),
                ];
            })
            ->values()
            ->all();

        return $this->makeDashboardPayload(
            $filters,
            [
                $this->makeKpi(
                    'assigned_jobs',
                    'Assigned Jobs',
                    (string) $assignments->count(),
                    'ti ti-briefcase',
                    'primary',
                    'This Range',
                    $filters['label']
                ),
                $this->makeKpi(
                    'upcoming_jobs',
                    'Upcoming Jobs',
                    (string) $upcomingAssignments->count(),
                    'ti ti-calendar-time',
                    'warning',
                    'Next Schedule',
                    optional(optional($upcomingAssignments->first())->booking?->event_date)->format('M d, Y') ?? 'None'
                ),
                $this->makeKpi(
                    'completed_tasks',
                    'Completed Tasks',
                    (string) $assignments->where('status', 'completed')->count(),
                    'ti ti-checklist',
                    'success',
                    'In Progress',
                    (string) $assignments->where('status', 'in_progress')->count()
                ),
                $this->makeKpi(
                    'completion_rate',
                    'Completion Rate',
                    $this->formatPercentage($completionRate),
                    'ti ti-chart-arcs',
                    'info',
                    'Cancelled',
                    (string) $assignments->where('status', 'cancelled')->count()
                ),
            ],
            [
                $this->makeChart('assignment_status', 'Assignment Status Breakdown', 'donut', [
                    [
                        'name' => 'Assignments',
                        'data' => $statusValues,
                    ],
                ], $statusLabels),
                $this->makeChart('completion_trend', 'Completion Trend', 'line', [
                    [
                        'name' => 'Completed Jobs',
                        'data' => $this->normalizeTrendData($labels, $completionTrend),
                    ],
                ], $labels),
            ],
            [
                $this->makeTable(
                    'upcoming_schedule',
                    'Upcoming Schedule',
                    ['Booking Reference', 'Studio', 'Event Date', 'Status'],
                    $scheduleRows,
                    'No upcoming assignments found.'
                ),
            ],
            [
                'subtitle' => 'Your assignment performance and upcoming work.',
            ]
        );
    }
}
