<?php

namespace Tests\Feature\Booking;

use App\Models\Admin\CategoriesModel;
use App\Models\BookingModel;
use Tests\TestCase;

/**
 * Bookings made through the client form have no event name, so pages showed
 * "Event null" and galleries were named " Gallery". Fall back to the service
 * category, then the booking reference.
 */
class BookingEventNameTest extends TestCase
{
    public function test_a_booking_without_an_event_name_uses_its_category(): void
    {
        $booking = new BookingModel(['booking_reference' => 'BK-TEST1', 'event_name' => null]);
        $booking->setRelation('category', new CategoriesModel(['category_name' => 'Wedding Photography']));

        $this->assertSame('Wedding Photography', $booking->event_name);
        $this->assertSame('Wedding Photography', $booking->toArray()['event_name']);
        $this->assertSame('Wedding Photography Gallery', $booking->event_name.' Gallery');
    }

    public function test_a_booking_without_a_category_uses_its_reference(): void
    {
        $booking = new BookingModel(['booking_reference' => 'BK-TEST2', 'event_name' => '  ']);
        $booking->setRelation('category', null);

        $this->assertSame('Booking BK-TEST2', $booking->event_name);
    }

    public function test_a_named_event_keeps_its_name(): void
    {
        $booking = new BookingModel(['booking_reference' => 'BK-TEST3', 'event_name' => 'Santos Wedding']);

        $this->assertSame('Santos Wedding', $booking->event_name);
    }
}
