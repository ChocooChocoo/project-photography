<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Provide common dashboard helpers for filter resolution and chart shaping.
 */
abstract class BaseDashboardService
{
    /**
     * Resolve the requested date filters with sensible defaults.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    protected function resolveFilters(array $input): array
    {
        $startDate = isset($input['start_date']) && !empty($input['start_date'])
            ? Carbon::parse($input['start_date'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $endDate = isset($input['end_date']) && !empty($input['end_date'])
            ? Carbon::parse($input['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'label' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'),
        ];
    }

    /**
     * Get filter bounds as Carbon instances.
     *
     * @param  array<string, string>  $filters
     * @return array{0:\Carbon\Carbon,1:\Carbon\Carbon}
     */
    protected function getBounds(array $filters): array
    {
        return [
            Carbon::parse($filters['start_date'])->startOfDay(),
            Carbon::parse($filters['end_date'])->endOfDay(),
        ];
    }

    /**
     * Determine the trend grouping key.
     */
    protected function getTrendGranularity(Carbon $startDate, Carbon $endDate): string
    {
        return $startDate->diffInDays($endDate) > 45 ? 'month' : 'day';
    }

    /**
     * Build empty trend labels between the given bounds.
     *
     * @return array<int, string>
     */
    protected function buildTrendLabels(Carbon $startDate, Carbon $endDate, string $granularity): array
    {
        if ($granularity === 'month') {
            $labels = [];
            $cursor = $startDate->copy()->startOfMonth();
            $lastMonth = $endDate->copy()->startOfMonth();

            while ($cursor->lessThanOrEqualTo($lastMonth)) {
                $labels[] = $cursor->format('M Y');
                $cursor->addMonth();
            }

            return $labels;
        }

        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());

        return collect($period)->map(fn (Carbon $date) => $date->format('M d'))->values()->all();
    }

    /**
     * Normalize grouped date aggregates into the current trend labels.
     *
     * @param  array<string, int|float>  $aggregates
     * @return array<int, float>
     */
    protected function normalizeTrendData(array $labels, array $aggregates): array
    {
        return collect($labels)
            ->map(fn (string $label) => (float) ($aggregates[$label] ?? 0))
            ->values()
            ->all();
    }

    /**
     * Group a collection by date label for charts.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $records
     * @param  callable  $dateResolver
     * @param  callable|null  $valueResolver
     * @return array<string, float>
     */
    protected function groupTrendValues($records, callable $dateResolver, ?callable $valueResolver = null, ?string $granularity = null): array
    {
        return $records
            ->filter(fn ($record) => $dateResolver($record) !== null)
            ->groupBy(function ($record) use ($dateResolver, $granularity) {
                $date = Carbon::parse($dateResolver($record));

                return $granularity === 'month'
                    ? $date->format('M Y')
                    : $date->format('M d');
            })
            ->map(function ($group) use ($valueResolver) {
                if ($valueResolver === null) {
                    return (float) $group->count();
                }

                return (float) $group->sum(fn ($record) => (float) $valueResolver($record));
            })
            ->all();
    }

    /**
     * Format a currency value.
     */
    protected function formatCurrency(float|int|string $amount): string
    {
        return 'PHP ' . number_format((float) $amount, 2);
    }

    /**
     * Format a percentage value.
     */
    protected function formatPercentage(float|int $value): string
    {
        return number_format((float) $value, 1) . '%';
    }

    /**
     * Build a standard KPI card payload.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function makeKpi(
        string $key,
        string $label,
        string $displayValue,
        string $icon,
        string $color,
        string $subLabel,
        string $subValue,
        array $options = []
    ): array {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'display_value' => $displayValue,
            'icon' => $icon,
            'color' => $color,
            'sub_label' => $subLabel,
            'sub_value' => $subValue,
        ], $options);
    }

    /**
     * Build a standard chart payload.
     *
     * @param  array<int, array{name:string,data:array<int, float|int|string>}>  $series
     * @param  array<int, string>  $categories
     * @return array<string, mixed>
     */
    protected function makeChart(
        string $key,
        string $title,
        string $type,
        array $series,
        array $categories,
        array $options = []
    ): array {
        return array_merge([
            'key' => $key,
            'title' => $title,
            'type' => $type,
            'height' => 320,
            'series' => $series,
            'categories' => $categories,
        ], $options);
    }

    /**
     * Build a standard table payload.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, string>>  $rows
     * @return array<string, mixed>
     */
    protected function makeTable(string $key, string $title, array $columns, array $rows, string $emptyMessage): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'empty_message' => $emptyMessage,
        ];
    }

    /**
     * Build the common dashboard response payload.
     *
     * @param  array<int, array<string, mixed>>  $kpis
     * @param  array<int, array<string, mixed>>  $charts
     * @param  array<int, array<string, mixed>>  $tables
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function makeDashboardPayload(
        array $filters,
        array $kpis,
        array $charts,
        array $tables,
        array $meta = []
    ): array {
        return [
            'filters' => $filters,
            'kpis' => $kpis,
            'charts' => $charts,
            'tables' => $tables,
            'meta' => $meta,
        ];
    }
}
