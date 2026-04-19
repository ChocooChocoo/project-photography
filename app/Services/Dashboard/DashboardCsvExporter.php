<?php

namespace App\Services\Dashboard;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export dashboard payloads as CSV.
 */
class DashboardCsvExporter
{
    /**
     * Stream a dashboard payload as CSV.
     *
     * @param  array<string, mixed>  $dashboard
     */
    public function download(string $fileName, array $dashboard): StreamedResponse
    {
        return response()->streamDownload(function () use ($dashboard): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['Dashboard Export']);
            fputcsv($output, ['Date Range', $dashboard['filters']['label'] ?? 'N/A']);
            fputcsv($output, []);

            fputcsv($output, ['KPI', 'Value', 'Context', 'Context Value']);

            foreach ($dashboard['kpis'] ?? [] as $kpi) {
                fputcsv($output, [
                    $kpi['label'] ?? '',
                    $kpi['display_value'] ?? '',
                    $kpi['sub_label'] ?? '',
                    $kpi['sub_value'] ?? '',
                ]);
            }

            foreach ($dashboard['charts'] ?? [] as $chart) {
                fputcsv($output, []);
                fputcsv($output, [$chart['title'] ?? 'Chart']);
                $seriesNames = collect($chart['series'] ?? [])->pluck('name')->values()->all();
                fputcsv($output, array_merge(['Category'], $seriesNames));

                $categories = $chart['categories'] ?? [];
                $series = $chart['series'] ?? [];

                foreach ($categories as $index => $category) {
                    $row = [$category];

                    foreach ($series as $seriesItem) {
                        $row[] = $seriesItem['data'][$index] ?? 0;
                    }

                    fputcsv($output, $row);
                }
            }

            foreach ($dashboard['tables'] ?? [] as $table) {
                fputcsv($output, []);
                fputcsv($output, [$table['title'] ?? 'Table']);
                fputcsv($output, $table['columns'] ?? []);

                foreach ($table['rows'] ?? [] as $row) {
                    fputcsv($output, $row);
                }
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
