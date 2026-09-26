<?php

namespace Tests\Feature\Owner;

use Tests\TestCase;

/**
 * Guards the owner bookings table refresh and status-update fixes.
 *
 * The polling merge path appends rows to the DOM, so the shared table must
 * re-read them and re-render the current page slice. The status modal and the
 * assign gate live only in the template, so this test asserts on the rendered
 * template literals in the blade file.
 */
class BookingTablePaginationTest extends TestCase
{
    private const VIEW = 'resources/views/owner/view-bookings.blade.php';

    private const TABLE = 'public/assets/js/pages/custom-table.js';

    public function test_status_modal_disables_confirm_and_states_the_reason_when_no_statuses_are_available(): void
    {
        $view = $this->readView(self::VIEW);

        $this->assertStringContainsString('Object.keys(availableStatuses).length > 0', $view);
        $this->assertStringContainsString("$('#confirmStatusUpdate').prop('disabled', !hasAvailableStatuses)", $view);
        $this->assertStringContainsString('This booking is cancelled. A cancelled booking keeps its status.', $view);
        $this->assertStringContainsString('id="statusNotAvailableAlert"', $view);
    }

    public function test_failed_status_save_refreshes_the_booking_details(): void
    {
        $view = $this->readView(self::VIEW);

        $this->assertStringContainsString('function refreshStatusModalData(bookingId)', $view);
        $this->assertStringContainsString('refreshStatusModalData(currentBookingId)', $view);
    }

    public function test_assign_action_stays_visible_for_an_in_progress_booking_with_a_free_slot(): void
    {
        $view = $this->readView(self::VIEW);

        $this->assertStringContainsString(
            "!['completed', 'cancelled'].includes(booking.status) && data.current_assigned_count < data.max_photographers",
            $view
        );
        $this->assertStringNotContainsString("['in_progress', 'completed'].includes(booking.status)", $view);
    }

    public function test_assign_action_is_replaced_when_the_slot_count_is_full(): void
    {
        $view = $this->readView(self::VIEW);

        $this->assertStringContainsString('data.current_assigned_count >= data.max_photographers', $view);
        $this->assertStringContainsString('Maximum photographers assigned', $view);
    }

    public function test_live_refresh_re_reads_rows_and_keeps_five_rows_per_page(): void
    {
        $table = $this->readView(self::TABLE);
        $view = $this->readView(self::VIEW);

        $this->assertStringContainsString('window.PlatinumTable=new CustomTable', $table);
        $this->assertStringContainsString('refresh(t){', $table);
        $this->assertStringContainsString('Showing <span class="fw-semibold">', $table);
        $this->assertStringContainsString('window.PlatinumTable.refresh(BOOKINGS_TABLE)', $view);
        $this->assertStringContainsString('data-table-rows-per-page="5"', $view);
    }

    private function readView(string $relativePath): string
    {
        $path = base_path($relativePath);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
