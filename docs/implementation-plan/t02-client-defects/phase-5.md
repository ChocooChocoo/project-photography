[← Previous: Phase 4](./phase-4.md) | [Next: Phase 6 →](./phase-6.md)

**Status:** ✅ Done

# Phase 5: Downpayment settings

**Goal:** Owners and freelancers turn downpayments on or off and set them as a percentage or a fixed amount; checkout charges the downpayment and the order summary and billing show the downpayment and remaining balance.

Why it went missing: commit `8cf4e6e2` ("disable downpayment", from T01) hard-coded full payment in code and views. No migration dropped anything. T02 supersedes that T01 item.

Field names: T02's `is_downpayment_required` / `downpayment_rate` map to the existing `tbl_studios.requires_downpayment` / `downpayment_percentage`. Only the fixed-amount option is new.

Rule (as before `8cf4e6e2`):

- **Studio with downpayments on.** The client chooses a downpayment or full payment.
- **Studio with downpayments off.** Full payment only.
- **Freelancer.** The profile's deposit policy decides; the client has no choice.
- **Fixed amounts.** A fixed amount at or above the total becomes full payment.

## Dependencies

- None (independent of Phases 1–4)

## Tasks

- [x] Booking columns `down_payment`, `remaining_balance`, `deposit_policy`, `payment_type`, `payment_status` — already in `tbl_bookings` (`database/migrations/2026_02_08_102822_create_tbl_bookings_table.php:33-37`, `2026_02_08_150413_add_payment_type_to_bookings_table.php:15`)
- [x] Studio columns `requires_downpayment`, `downpayment_percentage`, fillable and validated — already in `app/Models/StudioOwner/StudiosModel.php`, saved in `StudioController.php`
- [x] Freelancer `deposit_policy`, `deposit_type`, `deposit_amount` — already in freelancer migrations (`2026_01_29_152148`, `2026_02_27_072011`), validated in `ProfileSetupRequest` and saved by `Freelancer\ProfileController::store`
- [x] Remaining-balance payment — already in `app/Http/Controllers/Client/MyBookingsController.php` (`initializeBalancePayment`), route `client.booking.balance.payment`; Pay Balance button in `client/view-my-bookings.blade.php`
- [x] Migration `database/migrations/2026_10_24_140000_add_downpayment_type_to_tbl_studios.php`: `downpayment_type` enum(`percentage`,`fixed`) default `percentage` and `downpayment_amount` decimal nullable; `down()` drops both. `StudiosModel` gains fillable, cast and rules (`downpayment_amount` required when the type is fixed); `StudioController` store/update save them and clear the amount for percentage.
- [x] Owner settings: restored the toggle and percentage input in `owner/edit-studio.blade.php` and `create-studio.blade.php` from `8cf4e6e2^` (reverse-applied that commit's view hunks).
  - Added a "Downpayment Type" select and a "Fixed Downpayment Amount (PHP)" input.
  - The switch now reads "Offer a downpayment option", with the help text "When on, clients choose a downpayment or full payment. When off, clients pay in full."
  - `owner/view-studio.blade.php` shows "30% of the booking total", "₱2,000.00 fixed amount" or "Not offered".
- [x] Freelancer deposit fields restored in `freelancer/setup-profile.blade.php` and `view-profile.blade.php`. The toggle script had stayed in the page, and the backend never stopped saving these fields.
- [x] Helpers in `app/Models/BookingModel.php`:
  - `depositTerms($type, $provider)` gives the provider's setting, or null when it is full payment only.
  - `paymentTerms($type, $provider, $total, $requested)` gives `payment_type`, `down_payment`, `remaining_balance`, `deposit_policy` and the display fields.
  - `depositInfo()` reads a booking's own stored terms for display.
- [x] `Client\BookingController`:
  - `store` uses `paymentTerms` for single and recurring bookings. Validation accepts `payment_type` in `downpayment, full_payment`.
  - `getSummary` uses the same helper and returns `deposit_type` and `deposit_amount`.
  - `create` passes `depositTerms` to the form.
  - The checkout description uses the booking's stored terms (e.g. "Booking Downpayment (30%) - REF") instead of the hard-coded "30% Deposit".
- [x] Booking form (`client/booking-forms.blade.php`): the payment section shows the "30% downpayment" / "Full payment" choice for studios, a "Downpayment required" notice for freelancers with a policy, and the full-payment notice otherwise.
  - The three copies of the summary-row script are now one `renderDepositRows()`.
  - That also fixes two bugs: the downpayment and balance rows never showed, because `.show()` cannot beat Bootstrap's `d-none`; and a fixed downpayment was labelled "30%".
- [x] Billing (`client/view-my-bookings.blade.php`, `MyBookingsController`, `client/payment-success.blade.php`): the payment type and the modal's downpayment row come from `depositInfo()`, so they show what the booking was made with. They no longer read the provider's current setting, which drifted when it changed and showed "30% Downpayment" for fixed amounts. The dead `loadDownpaymentPercentages()` script was removed.
- [x] Tests:
  - **`tests/Feature/StudioManagement/DepositPolicyTest.php`.** The three "always full payment" tests were replaced. It now covers:
    - a 30% choice charging 3,000 with 7,000 left, and billing still showing 30% after the owner changes to 50%;
    - full payment still being selectable;
    - the switch off forcing full payment;
    - a fixed 2,000 charge, and a fixed amount at or above the total becoming full payment;
    - the summary for both choices;
    - freelancer percentage, fixed and none.
  - **`BookingFormsPresentationTest`.** The choice renders for percentage and fixed, and only the full-payment notice shows when the switch is off.
  - **`StudioCommercialSettingsTest`.** A fixed type needs an amount, saving works, and switching back to percentage clears the amount.
  - **Fixtures.** `StudioCommercialSettingsTest`, `StudioUpdateTest` and `BookingFormsPresentationTest` gained the new columns.
  - **Already covered.** `BookingPaymentSettlementTest` already checks that a downpayment webhook settles as `partially_paid`.

## Done when

- [x] `php artisan test --compact --filter="DepositPolicyTest|BookingPaymentSettlementTest|WebhookTest"` passes; the full suite gives 701 passed (3528 assertions).
- [x] With the toggle off, checkout charges the full total (acceptance test "Full payment"). Covered by `test_downpayments_turned_off_means_full_payment_even_if_requested`; full payment is also still selectable when the toggle is on.
- [x] With 30% on a 10,000 booking, checkout charges 3,000 and stores a 7,000 balance; with fixed 2,000, it charges 2,000. Both are covered by `DepositPolicyTest`.
- [x] Browser check of the owner settings, booking form, summary and billing (see "Browser check" below).

## Verification (2026-10-24)

- Full suite: `php artisan test --compact` gave 701 passed (3528 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 19 passed.
- Build: `npm run build` succeeded.
- Compile check: every changed Blade view compiles to valid PHP (`token_get_all(..., TOKEN_PARSE)`).
- `php artisan migrate --pretend` shows the two new `tbl_studios` columns as the only pending change.
- Pint: the migration passes. `DepositPolicyTest.php` has line-ending and style issues that were already there.

## Browser check (2026-10-24, Playwright, 1920×1080, dev database)

`php artisan migrate` added the two columns. All six studios have `requires_downpayment = true` (30–40%).

- **Owner (`yolanda.inocencio@gmail.com`), Edit Studio, Quarry Road.**
  - The page shows the "Offer a downpayment option" switch (on), Percentage 30.00, Downpayment Type and Fixed Downpayment Amount. Screenshot: `.playwright-mcp/p5-edit-studio-before.png`.
  - Choosing Fixed amount with ₱2,000 and clicking "Update Studio" returned 200 "Studio updated successfully!", stored `downpayment_type = fixed` and `downpayment_amount = 2000.00`.
  - The View Studio modal shows "₱2,000.00 fixed amount", and T02 Annex shows "30% of the booking total". Screenshot: `p5-view-studio.png`.
- **Client (`beatriz.inocencio@gmail.com`), booking form for Quarry Road.**
  - The form asks "How would you like to pay?" with "₱2,000.00 downpayment" and "Full payment". Screenshot: `p5-booking-form-choice.png`.
  - With the ₱7,900 package and the downpayment choice, the summary shows "Downpayment (fixed amount): ₱2,000.00" and "Remaining Balance: ₱5,900.00", with both rows visible. With full payment, the type reads "Full Payment" and both rows are hidden.
  - The summary modal Price Breakdown shows Package ₱7,900.00, Downpayment ₱2,000.00, Remaining ₱5,900.00 and Total ₱7,900.00. Screenshot: `p5-summary-modal.png`.
- **Switch off.** With `requires_downpayment = false`, the form shows only "Full Payment: The full amount is collected upon booking." Screenshot: `p5-booking-form-off.png`.
- **Billing.** Booking FS-BKS-00011 (30%) on My Bookings shows "30% Downpayment" in the table. The receipt modal shows "Downpayment (30%): ₱3436.50" and "Payment Type: 30% Downpayment". Screenshot: `p5-billing-modal.png`.
- **Freelancer `lorena.novilla@gmail.com`** (policy required, 30%).
  - The profile shows "Deposit Policy 30.00% of total amount deposit". Screenshot: `p5-freelancer-profile.png`.
  - The client booking form shows "Downpayment required: 30% downpayment now, and the remaining balance before the event."
  - The summary for a ₱4,200 package gives a downpayment of 1,260.00 and a balance of 2,940.00. Screenshot: `p5-freelancer-booking-form.png`.
- **Not done in the browser:**
  - Submitting a booking and paying through the gateway (external checkout). Feature tests cover the stored downpayment and the first payment amount.
  - The freelancer setup form, which only opens for a new freelancer with no profile. Its view compiles.
- Quarry Road was set back to its original setting afterwards (on, percentage 30%). No JavaScript errors on any page.

## Notes

- `requires_downpayment` defaults to true, so studios that never turned it off will offer the downpayment choice again, as before T01. Owners can switch it off in Edit Studio.
- Deployment: run `php artisan migrate` before owners edit studio settings; saving a studio writes the new columns.

## Files changed

- `app/Models/BookingModel.php`
- `app/Models/StudioOwner/StudiosModel.php`
- `app/Http/Controllers/Client/BookingController.php`
- `app/Http/Controllers/Client/MyBookingsController.php`
- `app/Http/Controllers/StudioOwner/StudioController.php`
- `database/migrations/2026_10_24_140000_add_downpayment_type_to_tbl_studios.php` (new)
- `resources/views/client/booking-forms.blade.php`, `view-my-bookings.blade.php`, `payment-success.blade.php`
- `resources/views/owner/edit-studio.blade.php`, `create-studio.blade.php`, `view-studio.blade.php`
- `resources/views/freelancer/setup-profile.blade.php`, `view-profile.blade.php`
- Tests: `tests/Feature/StudioManagement/DepositPolicyTest.php`, `StudioCommercialSettingsTest.php`, `tests/Feature/Client/BookingFormsPresentationTest.php`, `tests/Feature/Owner/StudioUpdateTest.php`

---

[← Previous: Phase 4](./phase-4.md) | [Next: Phase 6 →](./phase-6.md)
