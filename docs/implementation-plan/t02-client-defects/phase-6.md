[← Previous: Phase 5](./phase-5.md) | [Next: Phase 7 →](./phase-7.md)

**Status:** ✅ Done

# Phase 6: Cancellation and refund rules

**Goal:** A cancelled booking paid only by downpayment keeps the downpayment as non-refundable; a fully paid booking gets a full or partial refund amount entered by the owner; every refund is recorded for audit by `payment_status`.

Default: with downpayments off, every paid booking is fully paid, so the full-payment refund path applies.

Finding: the refund rules were already built and tested (`BookingCancellationRecoveryService`, `RefundPolicyTest`, `RefundTargetDiscretionTest`). What was missing was telling the client the rule before and after cancelling.

## Dependencies

- Phase 5 complete (bookings can again be `partially_paid` through a downpayment)

## Tasks

- [x] Downpayment non-refundable rule (no refund queued unless fully paid) — already in `app/Services/BookingCancellationRecoveryService.php` (`clientCancelled`)
- [x] Owner sets refund target amount — already in `BookingCancellationRecoveryService::setRefundTarget` / `ownerEscalate`
- [x] Audit columns `refund_percentage`, `refund_amount`, `tbl_payments.refunded_amount`, `refund_reference`, `refund_notes`, `refunded_at` — already in `database/migrations/2026_08_15_000001_add_refund_targets_and_audit_fields.php` and `2026_08_12_000003_add_phase6_cancellation_fields.php`
- [x] ~~Stop setting `refund_pending` in `MyBookingsController::cancelBooking` for `partially_paid`~~ — already correct: it sets `refund_pending` only when `clientCancelled` queues a refund, which needs full payment. `test_studio_down_payment_client_cancel_queues_no_refund_target` already asserts the status stays `partially_paid`.
- [x] New `BookingModel::cancellationRefundNote()` states what happens to the money, using already-loaded payments when present:
  - downpayment only: "Your downpayment of ₱300.00 is non-refundable and is kept by the studio." (or "the photographer" for a freelancer);
  - full payment: "You paid in full, so the studio will decide your refund, up to ₱1,000.00.";
  - nothing paid: null.
- [x] Client cancel dialog (`resources/views/client/view-my-bookings.blade.php`) shows that note before the client confirms. It comes from the `data-refund-note` attribute and is inserted as text, not HTML.
- [x] `MyBookingsController::cancelBooking` reads the note before cancelling and returns "Booking cancelled. <note>", so the success alert repeats the rule.
- [x] ~~Show the message on `client/cancellation-recovery.blade.php`~~ — not applicable. That page is only for photographer-replacement recoveries, which are business cancellations and always a full refund. A downpayment-only client cancellation creates no recovery row.
- [x] Owner refund amount: already accepts full or partial amounts and rejects anything above the total paid (`applyRefundTarget` caps at the sum of succeeded payments; the endpoint returns 409, covered by `test_owner_refund_target_cannot_exceed_the_total_paid`).
- [x] Freelancer refund (`app/Http/Controllers/Freelancer/BookingController.php::updatePaymentStatus`): the fully-paid-only rule is kept. Refusing a `partially_paid` booking now says "A downpayment is non-refundable. Only fully paid bookings can be refunded."
- [x] Tests (`tests/Feature/Booking/ClientCancellationRefundFlowTest.php`):
  - the cancel message for a studio downpayment, a freelancer downpayment and a studio full payment;
  - `test_full_payment_cancel_with_a_4000_partial_refund_is_recorded_for_audit`: a 10,000 full payment, 10,001 refused (409), 4,000 accepted; the recovery is `refunded` with `refund_amount=4000.00` and `refund_percentage=40.00`; the payment is `partially_refunded` with `refunded_amount=4000.00`, the refund reference and `refunded_at`;
  - an unpaid cancel gives no note and the plain message;
  - a freelancer refund refused for a downpayment-only booking (fails against the old code).

## Done when

- [x] `php artisan test --compact --filter="RefundPolicyTest|ClientCancellationRefundFlowTest|OwnerCancellationRefundTest|ClientRefundsPageTest"` passes. With `RefundTargetDiscretionTest`, 27 tests and 155 assertions pass.
- [x] A downpayment-only cancellation creates no refund row and shows the non-refundable message (in the dialog and the response, asserted in the tests).
- [x] A fully paid cancellation with a 4,000 partial refund records `refund_amount=4000` and `refunded_at` (`test_full_payment_cancel_with_a_4000_partial_refund_is_recorded_for_audit`).
- [x] Browser check of the whole flow, client to owner to admin to client (see "Browser check" below).

## Verification (2026-10-24)

- Full suite: `php artisan test --compact` gave 706 passed (3559 assertions), after the browser fixes.
- Node: `node --test tests/Integration/*.test.cjs` gave 20 passed.
- Build: `npm run build` succeeded.
- `client/view-my-bookings.blade.php` compiles to valid PHP.
- Dev data: booking 22 (paid) gives "You paid in full, so the studio will decide your refund, up to ₱8,400.00."; booking 11 (unpaid) gives no note.

## Browser check (2026-10-24, Playwright, four 1920×1080 windows, dev database)

Test data: booking 62 `T02-DOWN-153952` (Beatriz, ₱7,900, ₱2,370 downpayment paid) and booking 61 `T02-FUTURE-104623` (Cristian, ₱8,400 paid in full).

1. **Window 1, client Beatriz.**
   - The cancel dialog shows "Your downpayment of ₱2,370.00 is non-refundable and is kept by the studio." Screenshot: `.playwright-mcp/p6-w1-down-dialog.png`.
   - After confirming, the alert reads "Booking cancelled. Your downpayment of ₱2,370.00 is non-refundable and is kept by the studio." Screenshot: `p6-w1-down-cancelled.png`.
   - In the database the booking is `cancelled` and still `partially_paid`, with no recovery row, and the payment is still `succeeded`.
2. **Window 2, client Cristian.**
   - The dialog shows "You paid in full, so the studio will decide your refund, up to ₱8,400.00." Screenshot: `p6-w2-full-dialog.png`.
   - After confirming, the same note shows and recovery #2 is `refund_pending` with no amount. Screenshot: `p6-w2-full-cancelled.png`.
3. **Window 3, owner Yolanda.**
   - The recovery page says "Enter an amount from 0.00 to 8,400.00".
   - ₱9,000, sent with the browser limit removed, is refused: "The refund amount must be between 0 and the total amount paid." Screenshot: `p6-w3-owner-over.png`.
   - ₱4,000 gives "Refund target saved.", stored as 4000.00 (47.62%). Screenshot: `p6-w3-owner-saved.png`.
4. **Window 4, admin.**
   - The queue lists the booking with "Owner refund target: PHP 4,000.00 (47.62% of succeeded payments)". Screenshot: `p6-w4-admin-queue.png`.
   - "Record target refund" with reference `stripe_refund_t02_partial_4000` gives "Refund evidence recorded." Screenshot: `p6-w4-admin-done.png`.
   - The recovery is now `refunded`. The payment is `partially_refunded` with `refunded_amount=4000.00`, the reference and `refunded_at`.
5. **Window 2 again.** The client's Refunds page shows "Target: ₱4,000.00, Paid: ₱8,400.00, Refunded: ₱4,000.00, REFUNDED". Screenshot: `p6-w2-client-refunds.png`.

## Bugs found in the browser and fixed

- **Owner could not save a refund target.**
  - Symptom: an owner with two studios was sent to the subscription page and nothing was saved.
  - Cause: `EnforceStudioSubscriptionAccess` could not work out the studio for `/owner/cancellation-recoveries/{recoveryId}/...`, so it fell back to "the owner's only studio", which fails for an owner with two. An owner whose plan had ended was also blocked from settling a refund for money already taken.
  - Fix: `resolveBooking()` now reads the booking from `{recoveryId}`, and the `owner.booking.recovery.*` routes count as fulfilment of a paid booking.
  - Test: `SubscriptionAccessTest::test_refund_recovery_actions_resolve_the_recoverys_studio_and_allow_paid_bookings` (it failed with 403 before the fix).
- **Client Refunds page showed "Paid: ₱0.00" after a partial refund.**
  - Cause: it only summed `succeeded` payments, but a refund marks the original payment `partially_refunded` or `refunded`.
  - Fix: `client/view-refunds.blade.php` now counts all three statuses.
  - Test: `ClientRefundsPageTest::test_partial_refund_still_shows_what_the_client_paid`.
- **Admin refund queue showed the event one day early (Nov 27 for Nov 28).**
  - Cause: booking dates reach the page as UTC (`2026-11-27T16:00:00Z`), and the formatter kept only the first ten characters.
  - Fix: `eventDate()` in `admin/booking-refunds.blade.php` formats in Asia/Manila.
  - Test: `tests/Integration/AdminRefundDate.test.cjs` (it failed on the old code).

## Findings (not changed here)

- **Client booking calendar uses the wrong date.** It builds each day with `new Date(y, m, d).toISOString().split('T')[0]` (`client/booking-forms.blade.php`, about line 2811). In Asia/Manila this gives the previous day, so each calendar day looks up the day before's availability. It is outside Phase 6 and should be tracked as a separate defect.
- **Booking status after a partial refund.** The booking stays `refund_pending` while the recovery is `refunded`. This is existing, tested behaviour (`test_studio_full_payment_client_cancel_defers_refund_to_the_owner` asserts it). Revisit it if the client expects a "partially refunded" booking status.

## Files changed

- `app/Models/BookingModel.php`
- `app/Http/Controllers/Client/MyBookingsController.php`
- `app/Http/Controllers/Freelancer/BookingController.php`
- `resources/views/client/view-my-bookings.blade.php`
- `tests/Feature/Booking/ClientCancellationRefundFlowTest.php`
- `app/Http/Middleware/EnforceStudioSubscriptionAccess.php`
- `resources/views/client/view-refunds.blade.php`
- `resources/views/admin/booking-refunds.blade.php`
- `tests/Feature/SubscriptionAccessTest.php`, `tests/Feature/Booking/ClientRefundsPageTest.php`, `tests/Integration/AdminRefundDate.test.cjs` (new)

---

[← Previous: Phase 5](./phase-5.md) | [Next: Phase 7 →](./phase-7.md)
