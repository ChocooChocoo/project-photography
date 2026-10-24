[← Previous: Phase 6](./phase-6.md) | [Next: Phase 8 →](./phase-8.md)

**Status:** 🔄 In Progress

# Phase 7: Photo editing options and pricing

**Goal:** Clients choose RAW photos or one or more editing services (color grading, brightness and contrast, retouching); the price updates automatically and the photographer's task shows the chosen edits.

Scheduling decision: editing happens after the shoot, so shoot availability does not change. The edits appear on the photographer's task.

## Dependencies

- Phase 5 complete (the downpayment helper uses the final total, which now includes editing)

## Tasks

- [x] Single RAW/enhanced choice with a package price — already in `photo_edit_option`, `enhancement_amount` (`tbl_booking_packages`) and `enhanced_photos_price` (packages), migration `*_add_media_materials_enhancement.php:13-29`; resolved by `BookingController::resolvePhotoEdit` (line 1693)
- [ ] Migration: add `editing_prices` JSON to studio and freelancer package tables (keys `color_grading`, `brightness_contrast`, `retouching`) and `editing_services` JSON to `tbl_booking_packages`; update `app/Models/StudioOwner/PackagesModel.php`, `app/Models/Freelancer/PackagesModel.php`, `app/Models/BookingPackageModel.php` casts.
- [ ] Owner and freelancer package forms: price input per editing service (empty = not offered).
- [ ] Extend `resolvePhotoEdit` to accept RAW or a list of services, validate against the package's offered services, and return the summed `enhancement_amount`; keep `enhanced_photos_price` as the single-option fallback for old packages.
- [ ] Use it in `store` (lines 547-556) and `getSummary` (lines 1720-1744) so totals and the downpayment match.
- [ ] `resources/views/partials/package-extras.blade.php`: RAW radio plus service checkboxes with prices; live total update in the page script.
- [ ] Show selected edits on the studio-photographer and freelancer assignment views and on the client booking details.
- [ ] Tests: extend `tests/Feature/Owner/PackageMediaTest.php`; add a pricing test (RAW = package price; color grading + retouching = package price + both prices; unoffered service rejected).

## Done when

- [ ] `php artisan test --compact --filter="PackageMediaTest|PhotoEditingPricingTest"` passes.
- [ ] Summary and stored `total_amount` equal package price plus selected editing prices.
- [ ] The photographer assignment view lists the chosen edits (acceptance test "Photos, videos and extras on packages").

---

[← Previous: Phase 6](./phase-6.md) | [Next: Phase 8 →](./phase-8.md)
