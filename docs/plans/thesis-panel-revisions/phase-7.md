← [Previous: Phase 6](./phase-6.md) | [Next: Phase 8](./phase-8.md) →

# Phase 7: Short-Term and Long-Term Bookings with Signed Contracts

**Status:** ✅ Done

**Goal:** A booking covers one day (short-term) or two or more days (long-term). Long-term bookings need a contract that the business and the client both sign.

## Dependencies
Phase 6 (booking form and payment rules settled).

## Tasks
- [x] A terms checkbox exists (`BookingController.php:395`). Its link is dead (`booking-forms.blade.php:473-474`), and acceptance is not stored.
- [x] Multi-day dates: add `event_end_date` (nullable) and `booking_duration_type` (`short_term`|`long_term`) to `tbl_bookings`. Keep `event_date` as the start. Update validation in `BookingController@store` (`:377-470`), `checkAvailability` (`:207`), `hasTimeslotConflict` (`:343`) and `getCalendarAvailability` (`:1896`) to cover the whole span.
- [x] Booking form: add an end-date field (`booking-forms.blade.php:209`) and show the duration type live. Decide how `recurrence_pattern` (recurring child bookings) relates to multi-day, and document it.
  - **Decision:** a booking is either multi-day or recurring, never both. The form disables the recurring switch once an end date is set (and the end date once recurring is on), and `store()` rejects both together. A booking spans at most 30 days (about 90 days ahead on the form).
- [x] Contract storage: create `tbl_booking_contracts` (`booking_id`, `terms_html` snapshot, `studio_signature`, `studio_signed_by`, `studio_signed_at`, `client_signature`, `client_signed_at`, `status`). Model in `app/Models/`.
- [x] Contract text: owner edits a contract template per studio (terms, cancellation, payment). Merge booking details at creation. This also supplies the missing terms page; fix the dead terms link.
- [x] Signing: a canvas signature pad in vanilla JS (no new dependency) plus a typed full name, stored as a data URL string capped in size or saved as a file on the `public` disk. Provide a client signing page and an owner signing page, and a printable contract view.
- [x] Gate: a long-term booking cannot move from `pending` to `confirmed` (`BookingModel::canTransitionTo`, `:509`) until both signatures exist. Short-term skips the contract. Record `terms_accepted_at` for all bookings.
  - **Behavior:** the client can still pay a long-term booking, but it stays `pending`. When the second signature lands and the booking is paid, it confirms automatically. Every payment path and both provider confirm actions go through `BookingModel::confirmIfEligible()` / `canBeConfirmed()`. A pending long-term booking expires after 7 days instead of 48 hours.
- [x] Notifications through `app/Traits/Notifiable.php` when a contract is ready to sign and when it is fully signed.
- [x] Tests: duration type from dates, conflict detection across days, gate blocks confirmation without both signatures, short-term unaffected.

## Done when
- [x] A one-day booking works as before.
- [x] A booking of two or more days creates a contract, both sides sign, and only then is it confirmed.
- [x] Signed contracts can be viewed and printed by both parties.

← [Previous: Phase 6](./phase-6.md) | [Next: Phase 8](./phase-8.md) →
