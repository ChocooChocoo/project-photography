<?php

namespace Tests\Feature\Dashboard;

use App\Services\Dashboard\BaseDashboardService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Client test C-04: a dashboard filter or export link with a date that is not
 * a date (for example start_date=abc) returned a 500. It now falls back to the
 * default last-30-days range.
 */
class DashboardFilterDatesTest extends TestCase
{
    private function resolve(array $input): array
    {
        $service = new class extends BaseDashboardService
        {
            public function filters(array $input): array
            {
                return $this->resolveFilters($input);
            }
        };

        return $service->filters($input);
    }

    public function test_unreadable_dates_fall_back_to_the_default_range(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');

        $filters = $this->resolve(['start_date' => 'abc', 'end_date' => 'xyz']);

        $this->assertSame('2026-09-26', $filters['start_date']);
        $this->assertSame('2026-10-25', $filters['end_date']);
    }

    public function test_valid_and_reversed_dates_still_work(): void
    {
        $this->assertSame('2026-09-01', $this->resolve(['start_date' => '2026-10-25', 'end_date' => '2026-09-01'])['start_date']);
        $this->assertSame('2026-10-25', $this->resolve(['start_date' => '2026-10-25', 'end_date' => '2026-09-01'])['end_date']);
    }
}
