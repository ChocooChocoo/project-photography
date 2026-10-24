[← Previous: Phase 4](./phase-4.md) | [Next: Phase 6 →](./phase-6.md)

**Status:** 🔄 In Progress

# Phase 5: Downpayment settings

**Goal:** Owners and freelancers turn downpayments on or off and set them as a percentage or a fixed amount; checkout charges the downpayment and the order summary and billing show the downpayment and remaining balance.

Why it went missing: commit `8cf4e6e2` ("disable downpayment", from T01) hard-coded full payment in code and views. No migration dropped anything. T02 supersedes that T01 item.

Field names: T02's `is_downpayment_required` / `downpayment_rate` map to the existing `tbl_studios.requires_downpayment` / `downpayment_percentage`. Only the fixed-amount option is new.

## Dependencies

- None (independent of Phases 1–4)

## Tasks

- [x] Booking columns `down_payment`, `remaining_balance`, `deposit_policy`, `payment_type`, `payment_status` — already in `tbl_bookings` (`database/migrations/2026_02_08_102822_create_tbl_bookings_table.php:33-37`, `2026_02_08_150413_add_payment_type_to_bookings_table.php:15`)
- [x] Studio columns `requires_downpayment`, `downpayment_percentage`, fillable and validated — already in `app/Models/StudioOwner/StudiosModel.php:40-41,187-188`, saved in `StudioController.php:262-263,608-609`
- [x] Freelancer `deposit_policy`, `deposit_type`, `deposit_amount` — already in freelancer migrations (`2026_01_29_152148`, `2026_02_27_072011`)
- [x] Remaining-balance payment — already in `app/Http/Controllers/Client/MyBookingsController.php:557` (`initializeBalancePayment`), route `routes/web.php:723`
- [ ] Migration: add `tbl_studios.downpayment_type` enum(`percentage`,`fixed`) default `percentage` and `downpayment_amount` decimal nullable; add to `StudiosModel` fillable, casts and rules.
- [ ] Restore the toggle and inputs in `resources/views/owner/edit-studio.blade.php`, `create-studio.blade.php` and the display in `view-studio.blade.php` from `git show 8cf4e6e2^:<path>`, plus the type selector and fixed-amount input.
- [ ] Restore freelancer deposit fields in `resources/views/freelancer/setup-profile.blade.php` and `view-profile.blade.php` from `8cf4e6e2^`.
- [ ] Add one helper `BookingModel::computeDownpayment(float $total, $provider): array` returning `[payment_type, down_payment, remaining_balance, deposit_policy]`; fixed amount is capped at the total.
- [ ] Use the helper in `app/Http/Controllers/Client/BookingController.php` `store` (replace hard-coded block at lines 562-568) and `getSummary` (lines 1729-1744); restore the `payment_type` validation (line 490) when the provider requires a downpayment; remove the stale "30% Deposit" text (lines 1099-1110, 2089).
- [ ] Show downpayment, remaining balance and "Pay balance" in the summary modal (`resources/views/client/booking-forms.blade.php:485+`) and billing (`client/booking-details.blade.php`, `payment-card.blade.php`).
- [ ] Tests: extend `tests/Feature/StudioManagement/DepositPolicyTest.php` and `tests/Feature/Booking/BookingPaymentSettlementTest.php` for off, percentage and fixed; checkout charges the downpayment; webhook sets `partially_paid`; balance payment sets `paid`.

## Done when

- [ ] `php artisan test --compact --filter="DepositPolicyTest|BookingPaymentSettlementTest|WebhookTest"` passes.
- [ ] With the toggle off, checkout charges the full total (acceptance test "Full payment").
- [ ] With 30% on a 10,000 booking, checkout charges 3,000 and billing shows a 7,000 balance; with fixed 2,000, it charges 2,000.

---

[← Previous: Phase 4](./phase-4.md) | [Next: Phase 6 →](./phase-6.md)
