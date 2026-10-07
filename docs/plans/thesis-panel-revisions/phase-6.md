← [Previous: Phase 5](./phase-5.md) | [Next: Phase 7](./phase-7.md) →

# Phase 6: Service Media, Materials, Photo Enhancement, Downpayment Off

**Status:** ✅ Done

**Goal:** Services and packages carry pictures and several videos plus a materials list. Clients pick raw or enhanced photos per package. The downpayment option is disabled.

## Dependencies
Phase 5 (shares `Client/BookingController` and `booking-forms.blade.php`).

## Tasks
- [x] Packages already store up to 5 cover images (`tbl_packages.cover_images`, `PackageStoreRequest.php:102-103`, `PackagesController.php:85-93`). Freelancer packages mirror it.
- [x] Package inclusions list exists (`package_inclusions`, `owner/create-packages.blade.php:54-66`).
- [x] Videos: add a `cover_videos` JSON column to `tbl_packages` and `tbl_freelancer_packages` (or a media table if size limits need it). Validate multiple files (`mimes:mp4,webm,mov`, set a size cap). Store on the `public` disk with relative paths. Edit and delete follow the `keep_cover_images` pattern (`PackagesController.php:160-185`).
- [x] Service media: `tbl_services` has no media. Add `images` and `videos` JSON columns and extend `ServiceRequest` (`:55-57`), `ServicesController`, and the service forms.
- [x] Show service and package media to clients (`client/booking-details.blade.php`, `booking-forms.blade.php`).
- [x] Materials: add a `materials` JSON column on packages (for example photo frames, with quantity), a repeater in `owner/create-packages.blade.php` and `edit-packages.blade.php`, and a client display. Copy the snapshot into `tbl_booking_packages` so later edits do not change old bookings.
- [x] Enhancement option: add `enhanced_photos_price` (nullable) on packages. Add `photo_edit_option` (`raw`|`enhanced`) and `enhancement_amount` on the booking snapshot. Add the choice to the booking form, add the amount in `store()` and `getSummary()` (`BookingController.php:520, 1698`), and show it on invoices.
- [x] Downpayment off: force `payment_type = full_payment` in `BookingController.php:453-464` and `:523-589`. Remove the downpayment radio at `booking-forms.blade.php:317-379`. Hide the studio deposit setting (`requires_downpayment`, `downpayment_percentage`) and freelancer deposit settings in owner and freelancer UIs. Keep existing partial-payment rows and balance payment working for old bookings.
- [x] Update `tests/Feature/StudioManagement/DepositPolicyTest.php` and booking tests for full payment only. Add tests for enhancement pricing and media validation.

## Done when
- [x] Owner can attach images and several videos to services and packages, and list materials.
- [x] Client sees media and materials, and can choose raw or enhanced with the price added to the total.
- [x] New bookings cannot use downpayment.
- [x] Existing bookings with balances still settle.

← [Previous: Phase 5](./phase-5.md) | [Next: Phase 7](./phase-7.md) →
