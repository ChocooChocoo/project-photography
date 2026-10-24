[← Previous: Phase 5](./phase-5.md) | [Next: Phase 7 →](./phase-7.md)

**Status:** 🔄 In Progress

# Phase 6: Cancellation and refund rules

**Goal:** A cancelled booking paid only by downpayment keeps the downpayment as non-refundable; a fully paid booking gets a full or partial refund amount entered by the owner; every refund is recorded for audit by `payment_status`.

Default: with downpayments off, every paid booking is fully paid, so the full-payment refund path applies.

## Dependencies

- Phase 5 complete (bookings can again be `partially_paid` through a downpayment)

## Tasks

- [x] Downpayment non-refundable rule (no refund queued unless fully paid) — already in `app/Services/BookingCancellationRecoveryService.php:341-357` (`clientCancelled`)
- [x] Owner sets refund target amount — already in `BookingCancellationRecoveryService::setRefundTarget` (line 234)
- [x] Audit columns `refund_percentage`, `refund_amount`, `tbl_payments.refunded_amount`, `refund_reference`, `refund_notes`, `refunded_at` — already in `database/migrations/2026_08_15_000001_add_refund_targets_and_audit_fields.php` and `2026_08_12_000003_add_phase6_cancellation_fields.php`
- [ ] In `app/Http/Controllers/Client/MyBookingsController.php::cancelBooking` (line 289), stop setting `refund_pending` when the booking is only `partially_paid`; keep `partially_paid` so no refund is implied.
- [ ] Show "Your downpayment is non-refundable and is kept by the studio" in the cancel confirmation and on `resources/views/client/cancellation-recovery.blade.php` when only the downpayment was paid.
- [ ] Check the owner refund-amount input accepts full or partial amounts with a maximum of the amount paid (sum of succeeded `tbl_payments.amount`); add server-side `max` validation if missing.
- [ ] Freelancer refund: keep the fully-paid-only rule in `app/Http/Controllers/Freelancer/BookingController.php:179,204`; add the same downpayment message.
- [ ] Tests: extend `RefundPolicyTest`, `ClientCancellationRefundFlowTest`, `OwnerCancellationRefundTest` for downpayment-only cancel (no refund, status stays `partially_paid`), full-paid full refund, full-paid partial refund, refund above paid amount rejected.

## Done when

- [ ] `php artisan test --compact --filter="RefundPolicyTest|ClientCancellationRefundFlowTest|OwnerCancellationRefundTest|ClientRefundsPageTest"` passes.
- [ ] A downpayment-only cancellation creates no refund row and shows the non-refundable message.
- [ ] A fully paid cancellation with a 4,000 partial refund records `refund_amount=4000` and `refunded_at`.

---

[← Previous: Phase 5](./phase-5.md) | [Next: Phase 7 →](./phase-7.md)
