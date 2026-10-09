← [Previous: Phase 7](./phase-7.md) | [Next: Phase 9](./phase-9.md) →

# Phase 8: Preparation Timeline, Equipment Transparency, On-Site Proof

**Status:** ✅ Implemented and verified (2026-10-09)

**Goal:** After payment the client sees booking milestones and the equipment (including camera brand) that will be used. The photographer submits picture proof that they are on site.

## Dependencies
Phase 4 (assignment rules). Phase 7 for contract milestones.

## Tasks
- [x] Equipment backend exists: `tbl_booking_equipment`, `BookingEquipmentModel`, owner endpoints `StudioOwner/BookingEquipmentController.php` (`web.php:202-204`), photographer confirm (`StudioPhotographer/BookingEquipmentController.php:16`). No view uses it.
- [x] On-site status exists (`AssignedBookingController.php:236-255`, client confirmation `MyBookingsController::confirmPhotographerOnSite` `:622`).
- [x] Timeline: create `tbl_booking_events` (`booking_id`, `type`, `title`, `meta` JSON, `occurred_at`, `actor_id`). Record payment settlement, photographer assignment/confirmation, equipment assignment, contract signatures, arrival, progress, completion, and gallery publication. Stable event keys prevent duplicate milestones; no backfill.
- [x] Client timeline: show milestones and preparation steps after payment in `client/view-my-bookings.blade.php`. As agreed, retain `client/booking-details.blade.php` as the pre-booking provider/package page and link it to My Bookings.
- [x] Equipment catalog: add `tbl_studio_equipment` with owner CRUD. Link booking equipment to catalog items while preserving free-text assignments and assignment snapshots.
- [x] Require a photographer before equipment assignment. Reject catalog conflicts across overlapping booking dates and times; adjacent time slots remain available.
- [x] Owner UI: equipment panel in `owner/view-bookings.blade.php`, with catalog CRUD, assignment, removal, feedback, and retry states.
- [x] Client preference: persist optional `preferred_camera_brand`, show it to owner, and show assigned equipment/brands to client.
- [x] Materials from package snapshots appear alongside client preparation.
- [x] On-site proof: require JPG, PNG, or WebP image up to 5 MB; store relative public-disk path and optional validated coordinates. Show photo to owner/client; preserve client arrival-confirmation gate. Failed persistence rolls back status and removes uploaded file.
- [x] Tests cover prerequisite, ownership, conflicts, snapshot preservation, proof validation/persistence/rollback, milestone recording/deduplication, paid-client visibility, and migration rollback.

## Done when
- [x] Client sees a timeline and equipment list with camera brands after paying.
- [x] Equipment assignment is blocked until a photographer is assigned.
- [x] A photographer cannot mark on-site without a photo, and the photo is visible to owner and client.

## Verification
- Focused booking, venue, subscription, and deposit-policy run: **126 passed, 730 assertions** (excluding the existing broken readiness fixture).
- Final full suite: **575 passed, 2 failed (2770 assertions)**. Existing failures: `FirstLoginPasswordChangeTest` lacks `tbl_studio_photographers`; `BookingReadinessEndpointsTest` references a missing migration. Neither was suppressed or changed.
- Playwright on disposable MySQL clone with documented seeded owner, photographer, and client accounts: prerequisite returns 422; catalog create/edit/delete succeeds; overlapping reservation returns 409; missing proof returns 422; valid upload succeeds; client sees milestones, Canon equipment, materials, and loaded proof image; owner sees loaded proof image.
- Browser defects fixed and rerun: Blade route-map parsing and mobile preparation overflow. Both preparation panels fit 390px viewport; booking modals close with Escape.
- Existing shared-template translation JSON console error remains outside Phase 8 changes.
- Phase 8 additive migration applied to local app database. Browser subscription fixtures changed only disposable clone. No commits made.

## UI decisions
- Reuse existing portal typography, colors, and controls to preserve project identity; no new visual assets or dependencies.
- Current preparation and dated milestones are separate so pending work is not mistaken for completed history.
- Catalog editor stays collapsed until needed; client equipment, materials, and proof remain in booking details.
- New owner controls have 44px targets; responsive columns avoid horizontal overflow; loading, empty, error, and retry states are explicit.

← [Previous: Phase 7](./phase-7.md) | [Next: Phase 9](./phase-9.md) →
