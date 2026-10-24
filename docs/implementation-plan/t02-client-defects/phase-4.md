[← Previous: Phase 3](./phase-3.md) | [Next: Phase 5 →](./phase-5.md)

**Status:** ✅ Done

# Phase 4: Printed photos — delivery gate and fulfillment rule

**Goal:** Printed-photo delivery cannot start before the event has ended, and the client's accepted pickup or courier choice is final unless the client accepts a new quote.

Decisions: the gate is "event end time has passed", not booking status `completed`, because completion already waits for delivery (`BookingModel::getDeliveryCompletionBlockReason`). The client's accepted choice wins and locks.

Root causes:

- **Delivery too early.** `HardcopyOrderController::status()` checked only that the booking was open (`editable()`), that the client had accepted, and that it was fully paid. Nothing checked that the event had happened.
- **Choices overwriting each other.** `store()` saved the studio's "Suggested fulfillment" in `fulfillment_method`, and `accept()` overwrote it with the client's choice. The suggestion was lost, and the panel showed one value with no rule.

## Dependencies

- None (independent of Phases 1–3)

## Tasks

- [x] Added `BookingModel::eventEndsAt()` and `eventHasEnded()` in `app/Models/BookingModel.php`.
  - The end is the last event day plus the end time, in Asia/Manila.
  - A single-day booking whose end time is not after its start time runs past midnight.
  - With no end time, the event ends at 11:59 PM.
  - `FreelancerBookingAccess::eventEnd()` now returns `$booking->eventEndsAt()`, so freelancer access and printed photos use one rule.
- [x] `HardcopyOrderController::status()` refuses preparing, ready, shipped and delivered until the event has ended. The message is "Printed photo delivery can start after the event ends on Oct 20, 2026 11:59 PM." Cancelling an unaccepted quote is still allowed.
- [x] `resources/views/partials/hardcopy-orders.blade.php`: before the event ends, the owner sees "Delivery can start after the event ends on …" instead of the next-status button.
- [x] Fulfillment rule:
  - New migration `database/migrations/2026_10_24_120000_add_suggested_fulfillment_to_hardcopy_orders.php` adds `suggested_fulfillment_method`. It copies the existing value into older orders, and `down()` drops the column.
  - `store()` saves the studio's suggestion there.
  - `accept()` changes only `fulfillment_method`, the client's final choice.
- [x] ~~In `store`, reset `accepted_at` and notify the client when the owner changes an accepted order~~ — dropped, not needed. `store()` already refuses a new quote while an order is active (409), and `accept()` already ignores a second acceptance, so neither side can change an accepted order.
- [x] Partial wording:
  - Before acceptance: "Studio suggests: Studio pickup." The client sees "Your choice is final once you accept." and the suggestion is preselected. The owner sees "Awaiting client acceptance. The client makes the final choice."
  - After acceptance: "The client chose Third-party courier (you suggested Studio pickup). The client's choice is final." The client sees "You chose …".
  - Courier name and tracking fields still appear only for courier orders.
- [x] `tests/Feature/Booking/HardcopyOrderTest.php`: 3 new tests.
  - Delivery is refused at noon on the event day, the owner panel shows the waiting message, and it works after midnight.
  - The client's courier choice wins, the studio's pickup suggestion is kept, a second acceptance changes nothing, and an owner re-quote is refused (409).
  - The migration backfills and rolls back.
- [x] `tests/Feature/Booking/OwnerCompletionGateTest.php` still passes, so completion still waits for delivery.

## Done when

- [x] `php artisan test --compact --filter="HardcopyOrderTest|OwnerCompletionGateTest"` passes. Together with `FreelancerAccessTest` and `DeliveryMigrationTest`, 42 tests pass. Against the old controller and partial, the 2 behaviour tests fail.
- [x] A booking whose event has not ended cannot be marked shipped, and the same booking after the end time can (shown with "preparing", which is the first step of the same check).
- [x] An owner change after client acceptance leaves `fulfillment_method` as the client chose. The owner cannot re-quote an active order, and the client's second acceptance is ignored.

## Verification (2026-10-24)

- Full suite: `php artisan test --compact` gave 695 passed (3473 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 19 passed.
- Build: `npm run build` succeeded.
- `php artisan migrate --pretend` shows only this phase's migration as pending: add the column, then copy the existing values.
- Pint: the controller, service and migration pass. `BookingModel.php` has style issues that were already there; the new methods follow Pint style.
- Not measured: no browser in this session, so the panels were checked through rendered HTML in tests only.

## Browser check (2026-10-24, Playwright, 1920×1080, dev database)

- `php artisan migrate` ran the new migration on the dev database.
- **Owner `yolanda.inocencio@gmail.com`, booking FS-BKS-00022.** Created a quote (Framed print 8x10, PHP 500, suggested Third-party courier). The panel shows "Studio suggests: Third-party courier. Awaiting client acceptance. The client makes the final choice."
- **Client `cristian.panganiban@gmail.com`.** `/client/bookings/22/hardcopy` shows "Studio suggests: Third-party courier. Your choice is final once you accept.", with courier preselected.
- **Accept and dispatch (follow-up check).**
  - The client accepted quote #1 choosing Studio pickup. The order kept pickup with the courier suggestion stored, and the balance rose by PHP 500. A second acceptance asking for courier was ignored (still pickup).
  - The balance payment was marked paid (standing in for the gateway). The owner then clicked "Mark preparing", "Mark ready" and "Mark delivered" (all 200). The order is now Delivered, and pickup skips "shipped".
  - Screenshots: `.playwright-mcp/gap3-owner-ready.png`, `gap3-owner-delivered.png`.
- **Before-event gate (follow-up check).** Future booking 61 (event Nov 28, fully paid, accepted pickup order #2).
  - The owner panel shows "Delivery can start after the event ends on Nov 28, 2026 1:00 PM." with no Mark button.
  - A forced status request gets 422 with the same message.
  - Screenshot: `gap2-delivery-gate.png`.
- **Wording fix found in the browser.** After accepting, the client saw "The client's choice is final." The client now sees "Your choice is final.", and the owner still sees "The client's choice is final." `HardcopyOrderTest` asserts both.
- Test data added: hardcopy order #1 (pending) on booking 22.
- Screenshots: `.playwright-mcp/p4-owner-quote.png`, `p4-client-quote.png`.

## Notes

- Deployment: run `php artisan migrate` before using printed-photo quotes. Without the new column, creating a quote fails.

## Files changed

- `app/Models/BookingModel.php`
- `app/Services/FreelancerBookingAccess.php`
- `app/Http/Controllers/HardcopyOrderController.php`
- `database/migrations/2026_10_24_120000_add_suggested_fulfillment_to_hardcopy_orders.php` (new)
- `resources/views/partials/hardcopy-orders.blade.php`
- `tests/Feature/Booking/HardcopyOrderTest.php`

---

[← Previous: Phase 3](./phase-3.md) | [Next: Phase 5 →](./phase-5.md)
