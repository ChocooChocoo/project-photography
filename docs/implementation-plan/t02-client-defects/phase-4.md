[← Previous: Phase 3](./phase-3.md) | [Next: Phase 5 →](./phase-5.md)

**Status:** 🔲 Not Started

# Phase 4: Printed photos — delivery gate and fulfillment rule

**Goal:** Printed-photo delivery cannot start before the event has ended, and the client's accepted pickup or courier choice is final unless the client accepts a new quote.

Decisions: the gate is "event end time has passed", not booking status `completed`, because completion already waits for delivery (`BookingModel::getDeliveryCompletionBlockReason`, `app/Models/BookingModel.php:624-636`). The client's accepted choice wins and locks.

## Dependencies

- None (independent of Phases 1–3)

## Tasks

- [ ] Add `BookingModel::eventHasEnded(): bool` in `app/Models/BookingModel.php` (event date plus end time is at or before now).
- [ ] In `app/Http/Controllers/HardcopyOrderController.php::status` (lines 115-133), reject moves to preparing, ready, shipped or delivered with a clear message while `eventHasEnded()` is false. Root cause: `editable()` (lines 26-29) only checks `confirmed`/`in_progress`.
- [ ] Hide or disable those status options in `resources/views/partials/hardcopy-orders.blade.php` until the event has ended, with the reason shown.
- [ ] Fulfillment lock: in `accept` (lines 75-90), save the client's choice as final. In `store` (line 63), if the order already has `accepted_at`, keep the client's `fulfillment_method`, clear `accepted_at` only when the owner changes price or method, and notify the client to re-accept (via `Notifiable::createNotification`).
- [ ] Partial: after acceptance show "Client chose: Studio pickup / Third-party courier" read-only to the owner; courier name and tracking fields appear only for courier orders (lines 31-33 already do this).
- [ ] Extend `tests/Feature/Booking/HardcopyOrderTest.php`: dispatch before event end returns an error; after event end succeeds; owner re-quote on an accepted order resets acceptance and notifies the client; client choice is not overwritten by the owner.
- [ ] Run `tests/Feature/Booking/OwnerCompletionGateTest.php` to confirm completion still waits for delivery.

## Done when

- [ ] `php artisan test --compact --filter="HardcopyOrderTest|OwnerCompletionGateTest"` passes.
- [ ] A booking whose event has not ended cannot be marked shipped; the same booking after the end time can.
- [ ] An owner change after client acceptance leaves `fulfillment_method` as the client chose until the client accepts again.

---

[← Previous: Phase 3](./phase-3.md) | [Next: Phase 5 →](./phase-5.md)
