[← Previous: Phase 6](./phase-6.md) | [Next: Phase 8 →](./phase-8.md)

**Status:** ✅ Done

# Phase 7: Photo editing options and pricing

**Goal:** Clients choose RAW photos or one or more editing services (color grading, brightness and contrast, retouching); the price updates automatically and the photographer's task shows the chosen edits.

Scheduling decision: editing happens after the shoot, so shoot availability does not change. The edits appear on the photographer's task.

## Dependencies

- Phase 5 complete (the downpayment helper uses the final total, which now includes editing)

## Tasks

- [x] Single RAW/enhanced choice with a package price — already in `photo_edit_option`, `enhancement_amount` (`tbl_booking_packages`) and `enhanced_photos_price` (packages), migration `*_add_media_materials_enhancement.php`; resolved by `BookingController::resolvePhotoEdit`
- [x] Migration `database/migrations/2026_10_24_160000_add_photo_editing_services.php`:
  - adds `editing_prices` JSON to `tbl_packages` and `tbl_freelancer_packages`, holding a price per service;
  - adds `editing_services` JSON to `tbl_booking_packages`, holding a snapshot of service and price;
  - `down()` drops all three.
  - Models: the new `app/Models/Concerns/HasEditingOptions.php` trait (service catalog plus `editingOptions()`) is used by both package models. `BookingPackageModel` casts `editing_services` and appends `editing_summary` ("Color grading, Retouching" or "Unedited (RAW) photos").
- [x] Owner and freelancer package forms (shared `resources/views/partials/package-extras.blade.php`):
  - A "Photo Editing Prices" fieldset with one price per service; "Not offered" when empty.
  - The single "Enhanced Photos Price" input was replaced by this fieldset. A package that only has the old enhanced price shows a note, keeps working, and is replaced once per-service prices are set.
  - Validation in both `PackageStoreRequest` files: `editing_prices` only allows the three known keys, each 0–1,000,000.
- [x] `resolvePhotoEdit($package, $request)`:
  - takes `editing_services[]`;
  - refuses a service the package does not offer ("This package does not offer: Brightness and contrast.");
  - returns the services with prices and their sum;
  - treats no services as RAW;
  - still accepts the old `photo_edit_option=enhanced` for packages that only have the single enhanced price;
  - validation allows only known service keys.
- [x] Used in `store` (booking total, downpayment and the `editing_services` snapshot) and `getSummary` (returns `editing_services` labels and `editing_options`).
- [x] Client booking form (`client/booking-forms.blade.php`):
  - A "Photo Editing" fieldset lists one checkbox per offered service with its price, built from the package's `editing_options`. With none ticked, the client gets RAW photos.
  - Ticking a box re-prices the summary, which shows "Photo editing (Color grading, Retouching): ₱1,700.00".
  - Package cards list each offered service.
- [x] The chosen edits are shown:
  - in the studio-photographer assignment modal ("Photo editing: …");
  - on the freelancer studio-assignments page;
  - in the owner, freelancer and client booking modals and the client booking history;
  - on the client package details card, which lists each offered service.
  - Explicit eager-load column lists gained `photo_edit_option`, `enhancement_amount` and `editing_services`.
- [x] Tests:
  - **New `tests/Feature/Booking/PhotoEditingPricingTest.php`** (6 tests): offered services only; RAW keeps the price; services add to summary, total, payment and snapshot; the label keeps catalog order; downpayment taken from the edited total; unoffered and unknown services refused; the old enhanced choice still works.
  - **`tests/Feature/Owner/PackageMediaTest.php`:** saves per-service prices and rejects unknown or negative ones.
  - **Fixtures:** `DepositPolicyTest`, `PackageMediaTest` and `FreelancerAccessTest` gained the new columns or tables.

## Done when

- [x] `php artisan test --compact --filter="PackageMediaTest|PhotoEditingPricingTest"` passes. The full suite gives 713 passed (3603 assertions).
- [x] Summary and stored `total_amount` equal package price plus selected editing prices: ₱7,900 + ₱500 + ₱1,200 = ₱9,600, in the tests and in the browser.
- [x] The photographer assignment view lists the chosen edits (browser: "Photo editing: Color grading, Retouching").

## Browser check (2026-10-24, Playwright, four 1920×1080 windows, dev database)

`php artisan migrate` added the three columns.

1. **Window 1, owner `yolanda.inocencio@gmail.com`.**
   - Editing "Vows and Verses Collection - Basic" (package 31, ₱7,900) shows the Photo Editing Prices fieldset.
   - Setting Color grading ₱500 and Retouching ₱1,200 and saving gives "Package updated successfully!". `editingOptions()` returns those two. Screenshot: `.playwright-mcp/p7-w1-owner-prices.png`.
2. **Window 2, client `beatriz.inocencio@gmail.com`, booking form for Quarry Road.**
   - The checkboxes read "Color grading (+₱500.00)" and "Retouching (+₱1,200.00)".
   - Ticking both gives a summary of extra ₱1,700.00, total ₱9,600.00, and 30% downpayment ₱2,880.00. Screenshot: `p7-w2-client-options.png`.
   - Saving through `/client/bookings` created booking 63 `BK-6ADC6C55376A8` with total 9,600, downpayment 2,880 and the snapshot `{color_grading: 500, retouching: 1200}`.
3. **Window 3, photographer `eduardo.katigbak@gmail.com`** (assigned as test data). The assignment modal shows "Vows and Verses Collection - Basic: PHP 7900.00, Photo editing: Color grading, Retouching". Screenshot: `p7-w3-photographer.png`.
4. **Owner booking modal.** It shows "Photo editing: Color grading, Retouching (PHP 1700.00)". Screenshot: `p7-w1-owner-booking.png`.
5. **Client booking modal.** It shows "₱7900.00 + ₱1700.00 photo editing (Color grading, Retouching)". Screenshot: `p7-w2-client-booking.png`.
6. **Window 4, freelancer `lorena.novilla@gmail.com`.** Create Package shows the same three editing price fields. Screenshot: `p7-w4-freelancer-form.png`.

Found and fixed in the browser: MySQL's JSON column returns keys in its own order, so the label read "Retouching, Color grading". `editing_summary` now follows the catalog order, and a test asserts it.

No JavaScript errors on any page.

Test data added: package 31 editing prices (₱500 and ₱1,200), booking 63 for Beatriz (confirmed), and assignment 26 (Eduardo).

## Verification (2026-10-24)

- Full suite: `php artisan test --compact` gave 713 passed (3603 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 20 passed.
- Build: `npm run build` succeeded.
- Every changed Blade view compiles to valid PHP.
- Pint: the new trait, migration and test pass. `BookingPackageModel.php` is missing a final newline, which was already the case.

## Files changed

- `database/migrations/2026_10_24_160000_add_photo_editing_services.php` (new)
- `app/Models/Concerns/HasEditingOptions.php` (new), `app/Models/StudioOwner/PackagesModel.php`, `app/Models/Freelancer/PackagesModel.php`, `app/Models/BookingPackageModel.php`
- `app/Http/Requests/StudioOwner/PackageStoreRequest.php`, `app/Http/Requests/Freelancer/PackageStoreRequest.php`
- `app/Http/Controllers/Client/BookingController.php`, `Client/MyBookingsController.php`, `Freelancer/BookingController.php`, `Freelancer/StudioAssignmentController.php`, `StudioPhotographer/AssignedBookingController.php`
- `resources/views/partials/package-extras.blade.php`
- `resources/views/client/booking-forms.blade.php`, `booking-details.blade.php`, `view-my-bookings.blade.php`, `view-booking-history.blade.php`
- `resources/views/owner/view-bookings.blade.php`, `resources/views/freelancer/view-bookings.blade.php`, `view-studio-assignments.blade.php`, `resources/views/studio-photographer/view-assigned-booking.blade.php`
- Tests: `tests/Feature/Booking/PhotoEditingPricingTest.php` (new), `tests/Feature/Owner/PackageMediaTest.php`, `tests/Feature/StudioManagement/DepositPolicyTest.php`, `tests/Feature/Booking/FreelancerAccessTest.php`

---

[← Previous: Phase 6](./phase-6.md) | [Next: Phase 8 →](./phase-8.md)
