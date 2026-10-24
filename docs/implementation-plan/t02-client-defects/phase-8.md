[← Previous: Phase 7](./phase-7.md) | [Next: Phase 9 →](./phase-9.md)

**Status:** 🔄 In Progress

# Phase 8: Rescheduling requests

**Goal:** A client asks for a new date or time; the studio owner or freelancer approves, rejects or offers other dates after an availability check; the client gets every response as a notification.

Default: no mobile app or API exists (`routes/` holds only `web.php`), so "web and mobile" means `tbl_notifications` rows shown in the responsive web app.

## Dependencies

- None from earlier phases (uses booking date and time only)

## Tasks

- [x] Availability checks — already in `app/Services/PhotographerAvailabilityService.php:33,102` and `BookingController::hasTimeslotConflict` (line 371) / `checkDateAvailability` (line 836)
- [x] Notification helper — already in `app/Traits/Notifiable.php:27` (`createNotification`)
- [ ] Migration `tbl_booking_reschedule_requests`: `booking_id`, `requested_by`, `requested_date`, `start_time`, `end_time`, `reason`, `status` (`pending`, `approved`, `rejected`, `alternatives_offered`, `accepted`, `cancelled`), `alternatives` JSON, `responded_by`, `responded_at`, timestamps. Model `app/Models/BookingRescheduleRequestModel.php`.
- [ ] Move the timeslot-conflict check out of the private `BookingController` method into the service or `BookingModel` only so both booking and reschedule can call it; no copy.
- [ ] Client: request action in `app/Http/Controllers/Client/MyBookingsController.php` (one open request per booking, only before the event, booking `confirmed` or `pending`), and accept-alternative action; form on `resources/views/client/booking-details.blade.php`.
- [ ] Owner: approve, reject and offer-alternatives actions in `app/Http/Controllers/StudioOwner/BookingController.php`; approve and accept re-check availability, then update the booking date and times; UI in `resources/views/owner/view-bookings.blade.php`.
- [ ] Freelancer: same actions in `app/Http/Controllers/Freelancer/BookingController.php` and `resources/views/freelancer/view-bookings.blade.php`.
- [ ] Notifications: owner/freelancer on new request; client on approve, reject and alternatives (alternatives listed in the message and `data`); assigned photographers on date change.
- [ ] Routes in `routes/web.php` inside the existing client, owner and freelancer groups with their middleware.
- [ ] Tests: new `tests/Feature/Booking/RescheduleRequestTest.php` (request created; conflict blocks approve; approve updates booking; alternatives notify client; client accepts alternative; other studio's owner gets 403).

## Done when

- [ ] `php artisan test --compact --filter=RescheduleRequestTest` passes.
- [ ] An approved request changes the booking date and the client sees an unread notification.
- [ ] Approving into a slot taken by another booking fails with an availability message.

---

[← Previous: Phase 7](./phase-7.md) | [Next: Phase 9 →](./phase-9.md)
