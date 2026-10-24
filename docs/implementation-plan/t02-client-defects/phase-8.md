[← Previous: Phase 7](./phase-7.md) | [Next: Phase 9 →](./phase-9.md)

**Status:** ✅ Done

# Phase 8: Rescheduling requests

**Goal:** A client asks for a new date or time; the studio owner or freelancer approves, rejects or offers other dates after an availability check; the client gets every response as a notification.

Default: no mobile app or API exists (`routes/` holds only `web.php`), so "web and mobile" means `tbl_notifications` rows shown in the responsive web app.

## Dependencies

- None from earlier phases (uses booking date and time only)

## Tasks

- [x] Availability checks — already in `app/Services/PhotographerAvailabilityService.php` and the booking timeslot check
- [x] Notification helper — already in `app/Traits/Notifiable.php` (`createNotification`)
- [x] Migration `database/migrations/2026_10_24_180000_create_tbl_booking_reschedule_requests.php` creates `tbl_booking_reschedule_requests`:
  - columns `booking_id`, `requested_by`, `requested_date`, `start_time`, `end_time`, `reason`, `status`, `alternatives` (JSON), `response_note`, `responded_by`, `responded_at` and timestamps;
  - `status` is `pending`, `approved`, `rejected`, `alternatives_offered` or `accepted`.
  - Model: `app/Models/BookingRescheduleRequestModel.php`. `cancelled` was dropped as a status because there is no client "withdraw" action, which is not needed yet.
- [x] The timeslot check moved from the private `Client\BookingController::hasTimeslotConflict` to `BookingModel::timeslotTaken(..., $exceptBookingId)`. The controller method now delegates to it, so there is one copy, and rescheduling skips the booking being moved.
- [x] `app/Services/BookingRescheduleService.php` holds all the rules:
  - **Request:** only `pending` or `confirmed` bookings, and only one open request per booking.
  - **Every slot** offered or approved is checked: the date is after today, the end is after the start, the provider works that weekday (studio or freelancer schedule), no other pending or confirmed booking of the provider overlaps, and every assigned photographer is free (via `PhotographerAvailabilityService` on a copy of the booking with the new times).
  - **Approve and accept** check again, then move the booking.
  - **Messages** are plain, e.g. "The studio does not work on Mondays.", "Another booking already uses Nov 27, 2026 2:00 PM to 5:00 PM.", "Reschedule User is not available at the new time."
- [x] `app/Http/Controllers/BookingRescheduleController.php` (one controller for both sides):
  - Client: `clientShow`, `clientStore`, `clientAccept`. Only the client's own bookings are found; anything else is 404.
  - Provider: `providerShow`, `approve`, `reject`, `offer`. A studio owner only reaches bookings of their own studios, and a freelancer only their own freelancer bookings; anything else is 404.
  - Empty option rows in the offer form are ignored.
- [x] Routes in `routes/web.php`, inside the existing groups and their middleware:
  - client: `client.booking.reschedule.show/store/accept`;
  - owner: `owner.booking.reschedule.show/approve/reject/offer`, with `permission:owner.bookings.manage`;
  - freelancer: `freelancer.booking.reschedule.show/approve/reject/offer`.
- [x] `EnforceStudioSubscriptionAccess` works out the studio from `{rescheduleId}` and treats `owner.booking.reschedule.*` like other fulfilment of a paid booking. This avoids the two-studio problem found in Phase 6.
- [x] UI: one partial, `resources/views/partials/booking-reschedule.blade.php`, plus `partials/booking-reschedule-script.blade.php` (`window.mountReschedule`). It is loaded into the client (`view-my-bookings`), owner (`view-bookings`) and freelancer (`view-bookings`) booking modals, the same way as the printed-photo panel.
  - The client sees the current schedule and a request form, then "Waiting for the studio to answer", the offered dates as "Choose …" buttons, and the earlier requests.
  - The provider sees the request, "Approve new time", "Decline request" with a note, and "Or offer other dates (up to 3)".
  - Errors show inside the form that was submitted.
  - The plan named `client/booking-details.blade.php`, but that page is the package details page; the client's bookings live in the My Bookings modal.
- [x] Notifications:
  - the provider when a request is made (`reschedule_requested`);
  - the client on approval, decline and offered dates (`reschedule_approved`, `reschedule_rejected`, `reschedule_alternatives`, with the dates in the message and in `data.alternatives`);
  - the provider when the client accepts an offered date (`reschedule_accepted`);
  - assigned photographers whenever the booking moves (`booking_rescheduled`).
- [x] Tests: new `tests/Feature/Booking/RescheduleRequestTest.php` (8 tests, 58 assertions, real migrations and routes):
  - request and owner notification;
  - one open request at a time;
  - refusals for another client, a past date and a cancelled booking;
  - approve moves the booking and notifies the client and photographer;
  - a taken slot and a busy photographer are refused with nothing changed;
  - offered dates, then the client accepts one;
  - an unavailable offer is refused;
  - decline with a note, and another owner gets 404;
  - a closed weekday;
  - the freelancer flow, which the studio owner cannot answer.

## Done when

- [x] `php artisan test --compact --filter=RescheduleRequestTest` passes (8 tests). The full suite gives 721 passed (3661 assertions).
- [x] An approved request changes the booking date and the client sees an unread notification (test and browser).
- [x] Approving into a slot taken by another booking fails with an availability message ("Another booking already uses …"); the browser also showed the closed-day message.

## Browser check (2026-10-24, Playwright, three 1920×1080 windows, dev database)

Booking `BK-6ADC6C55376A8` (id 63, Quarry Road, Dec 10 10:00–13:00, photographer Eduardo). `php artisan migrate` created the table.

1. **Window 1, client Beatriz.** In My Bookings, the booking modal shows the Reschedule panel. She asked for Mon Dec 14 10:00–13:00 with a reason and saw "Reschedule request sent… Waiting for the studio to answer." Screenshots: `.playwright-mcp/p8-w1-client-form.png`, `p8-w1-client-sent.png`.
2. **Window 2, owner Yolanda.**
   - The bell shows "Reschedule requested: The client asked to move booking BK-6ADC6C55376A8 to Dec 14, 2026 10:00 AM to 1:00 PM." Screenshot: `p8-w2-owner-request.png`.
   - "Approve new time" returned 422 "The studio does not work on Mondays." Screenshot: `p8-w2-owner-refused.png`.
   - She offered Wed Dec 16 10:00–13:00 and Fri Dec 18 14:00–17:00 with a note and saw "Other dates sent to the client… Waiting for the client to choose." Screenshots: `p8-w2-owner-offer.png`, `p8-w2-owner-offered.png`.
3. **Window 1, client.**
   - The bell shows "Other dates offered: … Choose one of: Dec 16, 2026 10:00 AM to 1:00 PM; Dec 18, 2026 2:00 PM to 5:00 PM. We are closed on Mondays…".
   - She clicked "Choose Dec 18, 2026 2:00 PM to 5:00 PM" and saw "Your booking has been moved. Current schedule: Dec 18, 2026 2:00 PM to 5:00 PM". Screenshots: `p8-w1-client-choices.png`, `p8-w1-client-moved.png`.
4. **Window 2, owner.** The bell shows "Offered date accepted: The client chose Dec 18, 2026 2:00 PM to 5:00 PM…".
5. **Window 3, photographer Eduardo.** The bell shows "Booking moved: … now takes place on Dec 18, 2026 2:00 PM to 5:00 PM", and the assignment shows "Event Date December 18, 2026, Schedule 14:00:00 - 17:00:00". Screenshot: `p8-w3-photographer.png`.
6. **Direct approval.**
   - The client asked for Wed Dec 23 09:00–12:00 and the owner clicked "Approve new time": "Reschedule approved. The booking has been moved." Earlier requests list "Approved" and "Moved to an offered date". Screenshot: `p8-w2-owner-approved.png`.
   - The client's bell shows "Reschedule approved: … now takes place on Dec 23, 2026 9:00 AM to 12:00 PM."

No JavaScript errors on any page. Test data: booking 63 now runs Dec 23 09:00–12:00, with two reschedule requests recorded.

## Notes

- After a move, the other details in the open booking modal (such as the event date line) update only when the modal is reopened. The reschedule panel itself shows the new schedule straight away.

## Verification (2026-10-24)

- Full suite: `php artisan test --compact` gave 721 passed (3661 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 20 passed.
- Build: `npm run build` succeeded.
- Every changed view compiles.
- Pint: the new service, controller, model, migration and test are formatted.

## Files changed

- `database/migrations/2026_10_24_180000_create_tbl_booking_reschedule_requests.php` (new)
- `app/Models/BookingRescheduleRequestModel.php` (new), `app/Models/BookingModel.php` (`timeslotTaken`)
- `app/Services/BookingRescheduleService.php` (new)
- `app/Http/Controllers/BookingRescheduleController.php` (new), `app/Http/Controllers/Client/BookingController.php` (delegates to `timeslotTaken`)
- `app/Http/Middleware/EnforceStudioSubscriptionAccess.php`
- `routes/web.php`
- `resources/views/partials/booking-reschedule.blade.php` (new), `partials/booking-reschedule-script.blade.php` (new)
- `resources/views/client/view-my-bookings.blade.php`, `owner/view-bookings.blade.php`, `freelancer/view-bookings.blade.php`
- `tests/Feature/Booking/RescheduleRequestTest.php` (new)

---

[← Previous: Phase 7](./phase-7.md) | [Next: Phase 9 →](./phase-9.md)
