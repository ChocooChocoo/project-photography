<?php

namespace Tests\Feature\Gallery;

use Tests\TestCase;

class OwnerBookingsPollingTest extends TestCase
{
    private function bookingsView(): string
    {
        return file_get_contents(resource_path('views/owner/view-bookings.blade.php'));
    }

    public function test_bookings_view_polls_on_a_short_interval(): void
    {
        $view = $this->bookingsView();

        $this->assertStringContainsString('setInterval(poll, POLL_INTERVAL)', $view);
        $this->assertStringContainsString('var POLL_INTERVAL = 25000;', $view);
    }

    public function test_bookings_view_refreshes_rows_and_notification_bell(): void
    {
        $view = $this->bookingsView();

        $this->assertStringContainsString("data('unread-url')", $view);
        $this->assertStringContainsString('refreshNotificationBell', $view);
        $this->assertStringContainsString("tr[data-booking-id]", $view);
    }

    public function test_bookings_view_skips_polling_while_tab_is_hidden(): void
    {
        $view = $this->bookingsView();

        $this->assertStringContainsString('if (document.hidden)', $view);
        $this->assertStringContainsString('visibilitychange', $view);
    }

    public function test_bookings_view_preserves_existing_search_input(): void
    {
        $view = $this->bookingsView();

        // Polling must not clear or replace the data-table search field.
        $this->assertStringContainsString('data-table-search', $view);
        $this->assertStringNotContainsString("$('[data-table-search]').val('')", $view);
    }

    public function test_bookings_view_links_pending_recoveries_from_the_toolbar(): void
    {
        $view = $this->bookingsView();

        // The recovery screen is per-recovery, so the toolbar exposes a queue of
        // pending refunds that each link to owner.booking.recovery.view.
        $this->assertStringContainsString("route('owner.booking.recovery.view'", $view);
        $this->assertStringContainsString('refundQueueButton', $view);
        $this->assertStringContainsString('Refund Queue', $view);
    }

    public function test_bookings_view_scopes_polling_to_the_bookings_table(): void
    {
        $view = $this->bookingsView();

        // A second table elsewhere on the page must never be mistaken for this one.
        $this->assertStringContainsString('id="bookingsTable"', $view);
        $this->assertStringContainsString("var BOOKINGS_TABLE = '#bookingsTable tbody';", $view);
        $this->assertStringNotContainsString("document.querySelector('table tbody')", $view);
    }
}
