← [Previous: Phase 9](./phase-9.md) | [Next: Phase 11](./phase-11.md) →

# Phase 10: Feedback and Reporting

**Status:** ✅ Complete, locally enabled and verified (2026-10-13). Latest combined verification: 613 PHP tests and 4 JavaScript regressions passed. Authenticated feedback, report resolution and private evidence acceptance passed. Phase 10-only commit authorized. Production deployment remains unverified.

**Goal:** Clients give feedback on services, photographers and photos, and can report a studio or photographer. Admins review reports.

## Dependencies
Phase 8 (assigned photographer data) and Phase 9 (gallery photos).

## Execution design and checklist
- Phase 9 committed as `1c9813a3`; baseline: 597 PHP tests, 2926 assertions; four Node regressions passed. Unrelated deletions, plans, uploads and browser artifacts preserved unstaged.
- User approved minimal scope: one feedback per booked package, assigned studio photographer, and published/approved gallery. Gallery-level only, not per image. Existing Blade portal identity and moderation statuses (`published`, `flagged`, `removed`) remain.
- Feedback and reports require completed-booking ownership. Owner reviews page combines studio/package/photographer/photo feedback without leaking other studios' records.
- Reports target the booked studio or an assigned studio photographer. Reporter/admin can download private evidence; owner gets notifications, not evidence access. Accept JPG/PNG/WebP/PDF up to 5 MB. Throttle submissions; serialize per booking to prevent duplicate open/reviewing reports.
- Implementation order: schema/models and feedback tests; submission/owner queries and moderation; report schema/submission/status/evidence tests; client/owner/admin pages and navigation; focused/full tests, build, lint, browser verification.
- No application database migration, live payment, commit, dependency installation, or unrelated cleanup without approval. Update this file only after each task is verified.

## Verified slices
- Feedback submission: **7 tests passed** covering booked-package ownership, completed-booking/client gate, assigned photographer gate, approved published gallery feedback, duplicates, validation, rollback preservation and stored published-only aggregates. Added `BookingFeedbackController`, three feedback models, aggregate storage and additive feedback migration.
- Report backend: **4 tests passed** covering rate-limited route, booking/target ownership, duplicate active reports, required resolution notes, idempotent notifications and private evidence authorization. Added `BookingReportController`, `ReportModel` and additive reports migration. UI and final integration are not yet complete.
- Combined focused run: **11 passed, 44 assertions**. No Phase 10 local database migrations applied.
- Final focused run: **16 passed, 67 assertions**, including client/owner/admin pages, owner isolation, moderation aggregate refresh, real admin download middleware, failed storage rollback and report-migration rollback.
- Authenticated MySQL clone browser: client submitted package/photographer/gallery feedback (all persisted as published, rating 5); owner inbox displayed all three plus existing studio reviews; admin resolved a photographer report; reporter saw resolved status and resolution note.
- Private image evidence uploaded successfully. Reporter and admin retrieved identical 38806-byte attachment responses; owner was rejected by client-portal middleware. Existing admin middleware used Laravel-only `header()` on a Symfony streamed response and failed with 500; regression reproduced it, then header-bag setters fixed it.
- Duplicate active reports returned 409; sixth rapid request returned 429 through the actual submission route.
- Browser 1280px/390px, light/dark: no new-page horizontal overflow, 44px primary controls, theme-compatible focus indicators, primary contrast 5.33:1. Fresh mobile owner/admin loads use existing offcanvas sidebar correctly; resizing without reinitializing existing template temporarily retains desktop sidebar offset (existing shell behavior).
- Original full-suite pass after first page implementation: **611 passed, 2988 assertions**. Final `php artisan test --compact`: **613 passed, 2993 assertions**, no failures. Existing Node regressions: **4 passed**.
- Build and Blade cache passed. Impeccable detector on all new feedback/report pages and partials returned `[]`. New PHP files and feedback tests pass Pint.
- User approved a fresh backup and local enablement. Applied only `2026_10_13_130000_add_booking_feedback.php` and `2026_10_13_140000_add_booking_reports.php` to `platinum`. Original **66 tables / 2454 data rows** matched before/after row counts and SHA-256; migration ledger excluded from preservation comparison. No reseeding.
- Backup: `C:\Users\marka\AppData\Local\Temp\opencode\phase9-before-migrations-20261013-131953.sql` (utility retains Phase 9 filename prefix; this is the fresh Phase 10 backup), 847047 bytes; SHA-256 `b261e6d30fb3923b58e8dab278798ddd4ff22ed81824dff171d29dbbd390f38b`.

## Design decisions and boundaries
- Existing portal typography, body colors and form controls remain. Feedback uses dated, separated sections rather than decorative cards; report form stays collapsed until needed. No new visual assets/dependencies.
- Service/package type is stored explicitly so studio/freelancer numeric package IDs cannot collide. Stored `tbl_feedback_aggregates` follows published-only average/count semantics without overwriting existing provider aggregates. Comment-only photo feedback is excluded from numerical rating counts.
- Package/photographer/gallery feedback shares completed-booking ownership and duplicate guards. New feedback types participate in existing admin flag/remove/republish moderation. Owner sees moderation status; flagged/removed ratings do not count.
- Evidence intentionally uses private `local` disk, not public uploads. No symlink or public evidence URL. Reporter/admin endpoints authorize every download; owner status notifications contain no evidence path.
- Limits: five report requests/minute; reason 255 chars, details/note 5000 chars; image/PDF evidence <=5 MB. One active key per reporter/target/booking plus booking row lock prevents duplicate active reports. Closed reports are immutable and release active key.
- Portal links added to client history and completed-booking details, owner sidebar, admin sidebar. No new permission subsystem: owner inbox uses existing `owner.bookings.manage`; admin routes retain admin middleware.

## Tasks
- [x] Studio and freelancer ratings exist, one per completed booking (`tbl_studio_ratings`, `tbl_freelancer_ratings`; `Client/StudioRatingController.php`, `Client/FreelancerRatingController.php`; routes `web.php:679-691`).
- [x] Admin moderation of reviews exists (`Admin/ReviewModerationController.php`, `web.php:95-99`).
- [x] Service feedback: add a rating per service or package at a completed booking. Table `tbl_service_ratings` (`booking_id`, `package_id`, `client_id`, `rating`, `review_text`, `status`). Reuse the status values and aggregates pattern from `StudioRatingModel`. (Per booked package, package type explicitly scoped; stored published-only aggregate tested.)
- [x] Photographer feedback: table `tbl_photographer_ratings` for studio photographers in `tbl_booking_assigned_photographers`. Add the form to `client/view-booking-history.blade.php` and a display for the owner. (History links to completed-booking form; owner inbox tested.)
- [x] Photo feedback: let the client rate or comment on the delivered photos of a booking (per gallery, optionally per image path). Show it to the owner. (Approved gallery-level scope; nullable rating requires comment.)
- [x] Owner reviews page: list studio, service, photographer and photo feedback (today the owner sees only aggregates, `Services/Dashboard/OwnerDashboardService.php:77`). (Owned-studio scope and actual inbox verified.)
- [x] Reports: table `tbl_reports` (`reporter_id`, `target_type` `studio`|`photographer`, `target_id`, `booking_id`, `reason`, `details`, `evidence` path, `status` `open`|`reviewing`|`resolved`|`dismissed`, `resolution_note`). Client form from booking history and booking details. (Private evidence, ownership and rollback tested.)
- [x] Admin: reports list, detail, status change and note, with a sidebar link. Notify the reporter and the target owner. (Authenticated resolution/report status display and idempotent notification tests.)
- [x] Rate-limit and one open report per reporter and target per booking. (Actual route returns 409 for duplicates and 429 under submission throttle.)
- [x] Tests: only completed-booking clients can submit, duplicates rejected, admin can resolve, reporter sees status.

## Done when
- [x] Client can leave feedback on service, photographer and photos after completion.
- [x] Client can report a studio or photographer, and an admin can act on it.
- [x] Owner can read all feedback.

## Files changed
- Controllers: `Client/BookingFeedbackController.php`, `StudioOwner/FeedbackController.php`, `BookingReportController.php`, `Admin/ReviewModerationController.php` under `app/Http/Controllers/`.
- Models: `ServiceRatingModel.php`, `PhotographerRatingModel.php`, `PhotoRatingModel.php`, `ReportModel.php` under `app/Models/`.
- Migrations: `database/migrations/2026_10_13_130000_add_booking_feedback.php`, `2026_10_13_140000_add_booking_reports.php`.
- Pages/partials: `resources/views/client/booking-feedback.blade.php`, `owner/view-feedback.blade.php`, `admin/view-reports.blade.php`, `admin/report-details.blade.php`, `partials/feedback-styles.blade.php`, `partials/feedback-form-script.blade.php`; client history/details links and existing admin review-moderation row labels.
- Navigation/routes: `resources/views/layouts/admin/sidebar.blade.php`, `resources/views/layouts/owner/sidebar.blade.php`, `routes/web.php`.
- Streamed-download compatibility fix: `app/Http/Middleware/AdminMiddleware.php`.
- Tests: `tests/Feature/Feedback/BookingFeedbackTest.php`, `BookingReportTest.php`. Tracking: this file.

## Final checks and remaining boundaries
- Focused PHP: **16 passed, 67 assertions**. Full PHP: **613 passed, 2993 assertions**. Existing JS regressions: **4 passed**. Build, Blade compilation, new-file Pint, modified legacy PHP syntax and diff checks passed. No standalone typecheck/JS lint script is configured.
- Authenticated acceptance used only disposable clone rows; local enablement applied schema only after backup. No real client reports/reviews were seeded into local `platinum`.
- Screenshots of client/owner/admin mobile pages saved outside repo in approved temp directory. New forms have 44px controls, explicit labels, saving/error/success states and verified 3px keyboard focus. Primary button contrast 5.33:1 in both themes; no new-page overflow at 390px after normal mobile page initialization. Browser recorded no new JavaScript exceptions.
- Existing relative template translation/demo asset requests still produce console/network errors; this unrelated shell issue was not suppressed or claimed fixed.
- No Phase 10 acceptance blocker remains in tested local/clone flows. Production deployment remains separate. User authorized committing Phase 10 only; unrelated files remain untouched.
- Precommit verification repeated: **613 passed, 2993 assertions**; staged diff check passed. Previous attempt timed out without a test failure.
- Exact next step: commit Phase 10, then proceed to Phase 11 as authorized by the user.

← [Previous: Phase 9](./phase-9.md) | [Next: Phase 11](./phase-11.md) →
