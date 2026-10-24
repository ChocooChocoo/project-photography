← [Previous: Phase 10](./phase-10.md) | [Next: Phase 12](./phase-12.md) →

# Phase 11: Freelancer Fallback with Auto-Expiring Access

**Status:** ✅ Complete, locally enabled and verified (2026-10-13). Full suite: 628 PHP tests / 3056 assertions; 5 JavaScript regressions passed. Authenticated MySQL clone acceptance passed. Implementation included in Phase 11-only commit; production deployment is unverified.

**Goal:** When no photographer is available, the owner can hire a freelancer for a booking. The freelancer gets a role and permissions in RBAC automatically, and access is removed right after the event.

## Dependencies
Phase 2 (staff limit rules for freelancer members), Phase 3 (permission model), Phase 4 (assignment rules).

## Investigation and proposed scope
- Phase 10 committed separately as `90a4d5dd`; precommit full suite passed **613 tests / 2993 assertions**. Unrelated changes were excluded.
- Approved membership already exists and invitation acceptance checks `StudioStaffLimit`. Assignment currently selects only active studio photographers. Reuse assignment counts, rejection guards and allocation locking; add approved freelancer candidates only when no eligible studio photographer remains.
- `UserModel::activeRoles()` does not filter expiry; permission results are statically cached. Temporary grants must be checked on each request and must not survive expiry through cached permissions.
- `tbl_user_roles` currently has unique `(user_id, role_id, studio_id)`. Booking-specific grants need uniqueness that allows concurrent bookings without deleting permanent grants or another booking's access.
- Existing freelancer booking/gallery routes serve the freelancer's own business. Prefer separate studio-assignment routes within the freelancer portal, guarded by temporary booking-scoped permissions, instead of opening the studio-photographer portal or changing the user's account role.
- Proposed expiry: final event date (`event_end_date`, else `event_date`) plus `end_time`, using the application's event timezone. Access denied at that instant independently of hourly cleanup. No post-event upload grace period unless explicitly approved.
- Proposed revocation: expiry, booking cancellation/completion, assignment removal/rejection. Keep assignment history and approved membership; remove only affected temporary grants. Notify freelancer and owner once per revocation.
- Proposed tests: approved membership, no-staff fallback gate, conflicts with studio assignments and freelancer's own bookings, grant scope, parallel booking isolation, expiry at exact boundary with a warmed permission cache, immediate revocation, idempotent scheduled cleanup and preserved permanent roles.
- Execution decision: retain strict event-end expiry from the plan. No post-event grace period or new account-role switching.

## Implementation checkpoints
- [x] Add migration and seeded freelancer role; prove rollback preserves permanent grants.
- [x] Add booking-scoped grant/expiry/revocation service and permission resolution; test warmed-cache expiry and parallel-booking isolation.
- [x] Extend owner candidate list and assignment transaction; test membership, staffing and direct-booking conflicts. Existing rejection tests remain green.
- [x] Add guarded freelancer assignment page/status/gallery routes using existing assignment/gallery behavior. Preserve direct freelancer business routes.
- [x] Add owner fallback list and freelancer sidebar link; verify keyboard, mobile, empty/error states.
- [x] Run focused/full PHP, JS regressions, build, scoped formatting, Blade cache and authenticated clone acceptance. Record evidence immediately after verified slices.

## Verified slices
- Red/green: missing schema/grants and absent fallback behavior reproduced before implementation. Migration rollback exposed SQLite index-drop failure; explicit index removal fixed it.
- `php artisan test --compact --filter="FreelancerAccessTest|AssignmentLifecycleTest|BookingAssignmentDomainTest"`: **22 passed / 137 assertions**. Covers temporary permission scope and expiry before cleanup, grant/revoke notifications, cancellation/rejection/deletion, another booking/permanent grant preservation, multi-day Manila expiry, approved fallback, staff and membership guards, direct freelancer booking conflicts, permanent-row rollback and route denials after expiry/wrong assignment.
- Final focused `FreelancerAccessTest`: **15 passed / 61 assertions**. Added invalid upload validation, automatic gallery review submission, inactive-account denial, wrong-role selection denial, fresh-seed role hook/idempotency and preserved permanent-grant uniqueness. Final full suite: **628 passed / 3056 assertions**, no failures. Node regression suite: **5 passed**.
- Authenticated MySQL clone: owner selected Lorena from separate fallback list and assigned booking 65 (200); freelancer accepted, submitted arrival proof, waited for real client confirmation, started work, uploaded photos, owner approved/published gallery, freelancer completed (200), and subsequent assignment access returned 403. Own freelancer booking page remained 200; another studio assignment returned 403.
- First browser upload revealed missing draft-to-review handoff. Regression reproduced both swallowed file validation (500 instead of 422) and missing review submission; new guarded upload wrapper validates first and submits uploaded draft via existing approval action. Retest showed Pending Approval, owner approve/publish and completion working. Owner publication remains required before completing a gallery-enabled booking.
- Browser second fixture: blank cancellation reason produced field-linked error and focused status summary. Expiring its stored grant made detail/write return 403 before any cleanup; scheduled command then revoked 1 grant, second run revoked 0. Exact event-end boundary is separately proven with time travel and warmed permission results.
- Mobile owner fallback selection worked at 390px with no document overflow. Freelancer 1280px/390px, light/dark: no overflow; controls are at least 44px; keyboard Tab/Shift+Tab produced visible 3px focus. Primary button contrast remains 5.33:1. Screenshots inspected in approved temp directory; no new visual assets introduced. Existing template translation/demo-asset network errors remain unrelated and were not suppressed.
- UI purpose: retain portal typography/colors, separate fallback candidates to explain their temporary scope, show event-end warning before actions, and use labeled native controls plus field-linked errors. No new theme, fonts or animation dependencies. Gallery uploads automatically enter owner review rather than adding a separate workflow.
- Initial nullable-booking unique index allowed duplicate permanent grants. Regression caught it; non-null `grant_scope` defaults to `permanent`, temporary rows use `booking:<id>`. Final MySQL migration down/up preserved **40 permanent role rows** and re-grant permission checks passed. The disposable clone's earlier development schema was explicitly reconciled before final migration verification; local database only received final schema.
- User approved local backup/enablement. Applied only `2026_10_13_150000_add_freelancer_booking_access.php` to `platinum`. Original **71 tables / 2454 rows** retained matching hashes of all original columns. Only restricted role/permission seed rows and migration ledger were added; no temporary assignments or booking grants seeded locally.
- Fresh backup: `C:\Users\marka\AppData\Local\Temp\opencode\phase9-before-migrations-20261013-165206.sql` (utility retains Phase 9 prefix), 854242 bytes; SHA-256 `2698e86135555bbe8d656a79f1c0abeeb538bf223cf7dea1b9c4338906192d99`.

## Tasks
- [x] Freelancer invitation and membership exist (`tbl_studio_members`, `StudioMemberController.php:54-162`, `Freelancer/MemberInvitationController.php:72-101`).
- [x] Scheduler is in place (`routes/console.php:11-19`).
- [x] Assignment: approved member fallback only when no eligible studio photographer remains; separate owner list, active-account/type validation and `is_freelancer` flag. Existing exact package count and rejection rules retained; booking/person locks recheck eligibility before creation.
- [x] Role grant: nullable `expires_at`/`booking_id` plus uniqueness-preserving `grant_scope`; seeded `studio-freelancer` role has only `freelancer.studio-assignments.view`, `.update-status`, `.upload`. Grant expires at final event date/end time in Asia/Manila, stored in application timezone. Overnight single-day end times roll to next day.
- [x] Permission resolution: temporary roles resolve in freelancer portal with explicit booking/studio scope. Expired grants ignored, temporary grants excluded from unscoped role checks, cached permission results not reused for expiring grants. Work routes additionally verify assignment ownership, membership, account status and event window.
- [x] Revocation: `bookings:revoke-freelancer-access`, hourly production schedule; model events revoke on booking cancellation/completion/deletion or assignment cancellation/completion/removal. Only affected booking/user grants removed; membership, history and permanent/other-booking grants retained.
- [x] Notifications: grant to freelancer; revoke to freelancer and studio owner; repeated cleanup sends no duplicates.
- [x] Tests: completed/expired/foreign work denied, role scoped while active, time-travel expiry with warmed permissions, immediate cancel/reject/remove revocation, independent bookings, gallery handoff, rollback, permanent uniqueness and actual portal work flow.

## Done when
- [x] Owner can assign a freelancer when no studio photographer is free.
- [x] The freelancer can do their assigned work, and nothing else in the studio.
- [x] Access is gone after the event end with no manual step. Request-time denial does not depend on hourly scheduler; cleanup removes expired rows afterward.

## Files and remaining boundaries
- New: `app/Services/FreelancerBookingAccess.php`, `FreelancerFallbackCandidates.php`; `app/Http/Controllers/Freelancer/StudioAssignmentController.php`; `app/Console/Commands/RevokeFreelancerBookingAccess.php`; migration `2026_10_13_150000_add_freelancer_booking_access.php`; `database/seeders/StudioFreelancerRoleSeeder.php`; `resources/views/freelancer/view-studio-assignments.blade.php`, `partials/work-form-script.blade.php`; `tests/Feature/Booking/FreelancerAccessTest.php`, `tests/Integration/FreelancerFallback.test.cjs`.
- Modified: owner booking controller/request, photographer status request, permission middleware, user/booking/assignment models, availability service, fresh seed chain, owner booking view, freelancer sidebar, web/console routes.
- Build, Blade cache, new-file Pint, modified PHP syntax and diff checks passed. Impeccable detector on new UI returned `[]`. Fallback names/conflict messages HTML-escaped with Node regression proof.
- Strict event-end expiry retained; no editing/upload grace period. If owner publication occurs after expiry, owner must finish remaining assignment administration through existing owner controls. Freelancer's own business routes remain unchanged.
- Rollback refuses while booking-scoped grants exist; revoke those grants first. No existing grants are silently deleted to permit rollback.
- Production must run existing Laravel scheduler; production acceptance is separate. User subsequently authorized Phase 11-only commit and proceeding to Phase 12. Fresh precommit verification repeated: **628 PHP tests / 3056 assertions and 5 JavaScript regressions passed**.
- Adjacent owner equipment-catalog request returned one 500 during concurrent browser/full-suite execution. Log identified Windows file-cache `fopen(...storage/framework/cache/data...) Permission denied`, not assignment or grant logic. Three isolated authenticated retries afterward all returned 200 without any application-code change. Potential environment-level cache-lock contention remains outside Phase 11; do not interpret tested workflow success as an all-application/no-console-error claim. Test-server shutdown also logged aborted in-flight polling connections after test tabs were closed.

← [Previous: Phase 10](./phase-10.md) | [Next: Phase 12](./phase-12.md) →
