[← Previous: Phase 2](./phase-2.md) | [Next: Phase 4 →](./phase-4.md)

**Status:** 🔄 In Progress

# Phase 3: Equipment confirmation and inventory dropdowns

**Goal:** The assigned photographer confirms equipment so the status leaves "Awaiting confirmation"; a brand that differs from the client's preference shows a warning; equipment fields use the studio inventory instead of free text.

Decision: the photographer confirms. A brand mismatch warns and never blocks.

## Dependencies

- None (independent of Phases 1–2)

## Tasks

- [x] Studio inventory table and CRUD — already in `tbl_studio_equipment` (`database/migrations/2026_10_09_100000_add_booking_preparation.php:23`), `app/Models/StudioOwner/StudioEquipmentModel.php`, `app/Http/Controllers/StudioOwner/StudioEquipmentController.php`, routes `routes/web.php:221-224`
- [x] Availability tracking (item `status=available` and overlap check) — already in `app/Http/Controllers/StudioOwner/BookingEquipmentController.php:79-95`
- [x] Confirm endpoint for the assigned photographer — already in `app/Http/Controllers/StudioPhotographer/BookingEquipmentController.php:16-35`, route `assignment.equipment.confirm` (`routes/web.php:660`)
- [ ] Add a "Confirm equipment" button per item in the studio-photographer assignment view (data from `AssignedBookingController.php:74,104`) that POSTs to `assignment.equipment.confirm`. Root cause of the chat issue: no page ever calls this route.
- [ ] Add a `brand_mismatch` accessor to `app/Models/StudioOwner/BookingEquipmentModel.php`: true when the item `brand` and the booking's `preferred_camera_brand` are both set and differ (case-insensitive).
- [ ] Show a "Brand differs from client preference" warning in `public/assets/js/booking-preparation.js:88` (owner) and `resources/views/client/partials/booking-preparation.blade.php:21` (client).
- [ ] Owner equipment form: remove the free-text option and inputs (`booking-preparation.js:50-53`); list only inventory items from `tbl_studio_equipment`.
- [ ] Client booking form: change the preferred-brand text input (`resources/views/client/booking-forms.blade.php:441-442`) to a `<select>` of distinct brands from the studio's inventory, plus "No preference"; validate with `in:` that list in `app/Http/Controllers/Client/BookingController.php:426`.
- [ ] Extend `tests/Feature/Booking/EquipmentAssignmentTest.php` and `BookingPreparationTest.php`: photographer confirm sets `confirmed=true`; non-assigned user gets 403; mismatch flag true/false; free-text brand outside the list is rejected.

## Done when

- [ ] `php artisan test --compact --filter="EquipmentAssignmentTest|BookingPreparationTest"` passes.
- [ ] After the photographer confirms, the client page shows "Photographer confirmed", not "Awaiting equipment confirmation".
- [ ] A booking with preferred brand Canon and assigned NIKON shows the mismatch warning on owner and client pages.

---

[← Previous: Phase 2](./phase-2.md) | [Next: Phase 4 →](./phase-4.md)
