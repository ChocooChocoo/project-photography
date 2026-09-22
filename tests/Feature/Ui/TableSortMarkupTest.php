<?php

namespace Tests\Feature\Ui;

use Tests\TestCase;

/**
 * Guards the table sort fixes.
 *
 * The shared sorter in public/assets/js/pages/custom-table.js must read the
 * explicit sort value first. The booking and refund tables must carry that
 * value on the reference, the date, and the amount cells.
 *
 * The test reads the files as text. It does not render the pages.
 */
class TableSortMarkupTest extends TestCase
{
    private const SORTER = 'public/assets/js/pages/custom-table.js';

    public function test_the_sorter_reads_the_explicit_sort_value_first(): void
    {
        $sorter = $this->readView(self::SORTER);

        $this->assertStringContainsString(
            'data-sort-value',
            $sorter,
            'The sorter does not read the explicit data-sort-value marker.'
        );
        $this->assertStringContainsString(
            '[data-sort="',
            $sorter,
            'The sorter does not fall back to the header key child.'
        );
        $this->assertStringContainsString(
            'Node.TEXT_NODE',
            $sorter,
            'The sorter does not fall back to the first direct text node.'
        );
    }

    public function test_the_sorter_only_treats_explicit_date_shapes_as_dates(): void
    {
        $sorter = $this->readView(self::SORTER);

        $this->assertStringContainsString(
            'let dateLike=',
            $sorter,
            'The sorter does not gate the date branch behind an explicit date shape.'
        );
        $this->assertStringContainsString(
            '/^\\d{4}-\\d{1,2}-\\d{1,2}/',
            $sorter,
            'The sorter does not accept the YYYY-MM-DD shape as a date.'
        );
        $this->assertStringContainsString(
            'if(dateLike){let ts=Date.parse(e);',
            $sorter,
            'The sorter parses a date without checking the date shape first.'
        );
    }

    public function test_the_sorter_compares_strings_with_numeric_awareness(): void
    {
        $sorter = $this->readView(self::SORTER);

        $this->assertStringNotContainsString(
            'localeCompare(sy)',
            $sorter,
            'The sorter compares strings without numeric awareness, so the identifier 10 sorts before 9.'
        );
        $this->assertStringContainsString(
            'localeCompare(sy,undefined,{numeric:!0})',
            $sorter,
            'The mixed-type branch does not compare strings with numeric awareness.'
        );
        $this->assertStringContainsString(
            'localeCompare(y,undefined,{numeric:!0})',
            $sorter,
            'The same-type string branch does not compare with numeric awareness.'
        );
    }

    public function test_booking_and_refund_views_carry_a_reference_sort_value(): void
    {
        $views = [
            'resources/views/client/view-my-bookings.blade.php' => 'data-sort-value="{{ $booking->booking_reference }}"',
            'resources/views/client/view-booking-history.blade.php' => 'data-sort-value="{{ $booking->booking_reference }}"',
            'resources/views/client/view-refunds.blade.php' => 'data-sort-value="{{ $booking->booking_reference }}"',
            'resources/views/studio-photographer/view-assigned-booking.blade.php' => 'data-sort-value="{{ $assignment->booking->booking_reference ?? \'N/A\' }}"',
        ];

        foreach ($views as $path => $expected) {
            $this->assertStringContainsString(
                $expected,
                $this->readView($path),
                "{$path} does not carry the booking reference as a sort value."
            );
        }
    }

    public function test_booking_and_refund_views_carry_a_date_sort_value(): void
    {
        $views = [
            'resources/views/client/view-my-bookings.blade.php' => 'data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format(\'Y-m-d\') }}"',
            'resources/views/client/view-booking-history.blade.php' => 'data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format(\'Y-m-d\') }}"',
            'resources/views/client/view-refunds.blade.php' => 'data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format(\'Y-m-d\') }}"',
            'resources/views/freelancer/view-bookings.blade.php' => 'data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format(\'Y-m-d\') }}"',
            'resources/views/freelancer/booking-history.blade.php' => 'data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format(\'Y-m-d\') }}"',
            'resources/views/owner/view-bookings.blade.php' => 'data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format(\'Y-m-d\') }}"',
            'resources/views/studio-photographer/view-assigned-booking.blade.php' => 'data-sort-value="{{ $assignment->booking ? \Carbon\Carbon::parse($assignment->booking->event_date)->format(\'Y-m-d\') : \'N/A\' }}"',
        ];

        foreach ($views as $path => $expected) {
            $this->assertStringContainsString(
                $expected,
                $this->readView($path),
                "{$path} does not carry an ISO date as a sort value."
            );
        }
    }

    public function test_booking_and_refund_views_carry_an_amount_sort_value(): void
    {
        $views = [
            'resources/views/client/view-my-bookings.blade.php' => 'data-sort-value="{{ $booking->total_amount }}"',
            'resources/views/client/view-booking-history.blade.php' => 'data-sort-value="{{ $booking->total_amount }}"',
            'resources/views/client/view-refunds.blade.php' => 'data-sort-value="{{ $targetAmount }}"',
            'resources/views/freelancer/view-bookings.blade.php' => 'data-sort-value="{{ $booking->total_amount }}"',
            'resources/views/freelancer/booking-history.blade.php' => 'data-sort-value="{{ $booking->total_amount }}"',
            'resources/views/owner/view-bookings.blade.php' => 'data-sort-value="{{ $booking->total_amount }}"',
            'resources/views/studio-photographer/view-assigned-booking.blade.php' => 'data-sort-value="{{ $assignment->booking ? $assignment->booking->total_amount : 0 }}"',
        ];

        foreach ($views as $path => $expected) {
            $this->assertStringContainsString(
                $expected,
                $this->readView($path),
                "{$path} does not carry the raw amount as a sort value."
            );
        }
    }

    private function readView(string $relativePath): string
    {
        $path = base_path($relativePath);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
