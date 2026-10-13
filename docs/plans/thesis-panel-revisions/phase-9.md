← [Previous: Phase 8](./phase-8.md) | [Next: Phase 10](./phase-10.md) →

# Phase 9: Deliverables, Drive Links, Downloads, and Hardcopy Orders

**Status:** Implemented, locally enabled, and verified (2026-10-13). Full suite: 597 passed. Authenticated MySQL acceptance covers softcopy, courier/hardcopy-only, pickup/both, notifications, timeline, download protection and real Stripe sandbox checkout/settlement.

## Real sandbox checkout acceptance (2026-10-13)
- Follow-up request closed the remaining external-payment verification gap. A read-only Stripe balance query accepted configured credentials and reported `livemode: false`; no live-mode request or charge was made.
- Found a real UI blocker: hardcopy acceptance creates a pending payment, while the balance modal disabled its Proceed button whenever a pending payment existed. Existing balance initializer already reuses pending payments, so the narrow fix enables checkout while a positive balance remains and explains that the pending payment will be continued.
- Regression `tests/Integration/HardcopyCheckout.test.cjs` failed with the button disabled before the fix and passed afterward. Changed `resources/views/client/view-my-bookings.blade.php`; added the regression and extended `tests/Integration/Phase9BrowserFixture.php` inspection to report sandbox settlement evidence.
- Authenticated client accepted a PHP 100 print quote on disposable booking 61, opened Pay Balance, and used the real existing initializer. Stripe hosted page explicitly showed Sandbox and a `cs_test_` session. Payment used Stripe's documented test card, not a real card.
- Real gateway payment returned to `client/payment/success/PHASE9-DRIVE-91a107`. Database inspection confirmed amount PHP 100, payment `succeeded`, Stripe status `paid`, `is_balance_payment: true`, and remaining balance zero. No fixture settlement command was used for this payment.
- After real settlement, owner advanced the order through preparing, ready and delivered (all HTTP 200), then completed the booking with delivery type `both` (HTTP 200). This proves acceptance -> existing checkout -> real sandbox payment -> callback -> fulfillment -> completion.
- Final verification: `php artisan test --compact` **597 passed, 2926 assertions**; Node regressions **4 passed**; build, Blade cache, scoped Pint and diff checks passed. Test server stopped afterward. No commits or live charges.

## Follow-up acceptance pass (2026-10-13)
- User requested fixing and retrying until Phase 9 works. Verification now includes authenticated application routes on a disposable MySQL clone, not only mocked browser APIs.
- `FirstLoginPasswordChangeTest` fixture now includes the two staff tables queried by the existing staff-limit service; no auth production logic changed.
- Readiness test referenced nonexistent services and unsupported quote/contract-hash APIs. User approved aligning it to current app architecture. It now tests missing/partial/full signatures for both provider portals and unpaid completion blockers; no imaginary hash subsystem was added.
- Focused formerly failing fixtures: **10 passed, 56 assertions**. Final full-suite rerun: **597 passed, 2926 assertions**, no failures.
- Disposable clone: `photo_phase8_test_2473b27a3c9c63a9`; Phase 9 additive migrations applied only there. Phase 9 fixtures now copy package snapshots and completed assignments so real gallery/booking screens can exercise delivery.
- Follow-up found and fixed owner polling duplicates: off-page rows were mistaken for new bookings because pagination detaches them. Polling now uses cached rows, removes stale cache entries, and preserves active searches. Node regression failed before the fixes. Two real polling cycles retained **15 unique rows and one matching search result**, with no duplicates.
- Follow-up found and fixed hidden hardcopy completion: details API required a gallery even when a delivered hardcopy-only order satisfied completion. It now evaluates each delivery type without saving it; UI offers only eligible types. Feature regression failed before fix and passes afterward. New completion control has a 44px minimum height.
- Authenticated owner/client browser acceptance on the disposable clone, without mocked delivery APIs: invalid Drive host -> 422; valid Drive save -> 200; client access before publication -> 404; owner publication -> 200; approved Drive link visible to client. Pending and draft download routes -> 404.
- Actual photo download returns 68 bytes and an attachment filename. Actual ZIP returns 183 bytes with a valid archive/attachment response. Browser transport intercepted direct ZIP navigation with an empty 204 response; a pass-through relay using `route.fetch()` returned the **real application response** and browser downloaded `GAL-6ACD99C3540EA.zip` with no download failure. No dummy ZIP payload replaced the response.
- Courier acceptance: missing address -> 422; valid address/contact -> 200 and PHP 100 added once to the balance. Owner preparation before payment -> 422. Guarded fixture settlement simulated the payment model transition, then preparing/ready/shipped/delivered all persisted; tracking recorded. Owner completed this gallery-free booking as `hardcopy` -> 200.
- Pickup acceptance of an included zero-cost print -> 200; preparing/ready/delivered -> 200 without courier data; owner completed as `both` -> 200. Owner completed published Drive-only booking as `softcopy` -> 200.
- Database inspection confirmed all three completed delivery types, zero balances, both hardcopy orders delivered, **two ready, one shipped and two delivered notifications**, and **five corresponding timeline events**.
- New/updated files for this pass: `tests/Feature/Auth/FirstLoginPasswordChangeTest.php`, `tests/Feature/Booking/BookingReadinessEndpointsTest.php`, `tests/Feature/Booking/OwnerCompletionGateTest.php`, `tests/Integration/OwnerBookingRefresh.test.cjs`, `tests/Integration/Phase9BrowserFixture.php`, `tests/Integration/Phase9Server.py`, `app/Http/Controllers/StudioOwner/BookingController.php`, `resources/views/owner/view-bookings.blade.php`, and this file.
- Windows single-worker PHP development server stalled behind idle connections. Acceptance switched to a loopback-only threaded PHP-CGI test server guarded to disposable databases. This is test infrastructure, not a replacement production server.
- User subsequently approved backing up and applying both Phase 9 migrations to the actual local `platinum` database. Both completed successfully; no reset, reseed or unrelated migration ran.
- Backup: `C:\Users\marka\AppData\Local\Temp\opencode\phase9-before-migrations-20261013-113929.sql` (843580 bytes). SHA-256: `21857e575a204ff4c9c38f77ce7f1792ae791abda2c91456a907204619a0f375`. MySQL 8.4 mysqldump completed successfully; temporary credential options file was removed.
- Before/after preservation checks matched row counts and SHA-256 of all original columns: **55 bookings**, **0 studio galleries**, **0 freelancer galleries**. New nullable columns and both hardcopy tables verified present.
- Final checks: full PHP suite **597 passed**; Node regressions **3 passed**; build, scoped Pint, PHP syntax, Blade compilation and diff checks passed. No commits.
- Last focused rerun after local migration: **40 passed, 224 assertions**. Final preservation check still matches all original local data.
- Completion control accessibility: existing success teal measured **2.74:1** against white text; a darker shade of the same teal now measures **5.40:1** in light and dark. Resting target is 44px. Keyboard focus verified with a visible 3px theme-token outline; scoped `!important` overrides the template's outline removal.
- Owner booking detector reported one warning for the existing arrival-proof image built without a static `src`; its next chained call sets `src` from validated proof data. This is not a shipped empty image and was not rewritten to silence the detector.

**Goal:** At the end of a service the business delivers softcopy (upload or Drive link), hardcopy, or both. Clients can download only after the owner approves publishing. Hardcopy orders offer pickup or third-party courier.

## Dependencies
Phase 8 (timeline events for delivery).

## Execution notes (2026-10-13)
- Source path is `docs/plans/thesis-panel-revisions/phase-9.md`; all task tracking belongs here, not Phase 1.
- Phase 8 records implementation and verification. Its timeline recorder and booking panels exist.
- User authorized focused edits in the current `main` checkout, preserving existing uncommitted changes. No commits.
- Baseline focused run: 22 passed, 111 assertions; one unrelated readiness fixture fails because `2026_09_30_100000_add_booking_duration_and_contracts.php` is missing. User approved continuing without repairing that fixture.
- Existing Phase 9 controllers, migrations, panels and tests predate this session. Passing existing tests is not evidence of test-first development in this session.
- Prerequisite and acceptance checks are recorded below. The initial pass did not apply local application migrations; the follow-up applied only Phase 9 after explicit approval and backup.
- UI direction: preserve existing Platinum portal typography, Bootstrap-style controls and modal layout. ENERGY 1 / RHYTHM 1 / MOTION 1. No new assets or dependencies.

## Tasks
- [x] Gallery upload, owner approval and client view exist (`StudioPhotographer/OnlineGalleryController.php:118`, `StudioOwner/OnlineGalleryController.php:484-508`, `Client/OnlineGalleryController.php:100`).
- [x] Approval columns exist (`2026_09_22_090100_...`), and the client only sees approved, published galleries.
- [x] Drive link: add `drive_link` (nullable URL) to `tbl_studio_online_gallery` and `tbl_freelancer_online_gallery`. Validate as `url` and restrict to expected hosts (Google Drive, plus others the team approves). Show it to the client when the gallery is published. (Migration round-trip and owner Drive-only publication tested.)
- [x] Download: add single-file and zip routes in `Client/OnlineGalleryController`. Authorize the booking owner and require `published` and approved status. Build zips with `ZipArchive` (the extension is loaded). Add the download button in `client/view-online-gallery.blade.php`. (Authorization, traversal, missing-file, ZIP size and browser downloads verified.)
- [x] Hardcopy tables: create `tbl_booking_hardcopy_orders` (`booking_id`, `fulfillment_method` `pickup`|`courier`, `courier_name`, `tracking_number`, `delivery_address`, `contact_number`, `amount`, `status` `pending`|`preparing`|`ready`|`shipped`|`delivered`|`cancelled`) and `tbl_booking_hardcopy_items` (item name such as print, frame or album, size, quantity, unit price). (Apply, rollback and reapply verified on SQLite.)
- [x] Owner side: define hardcopy items per package or booking, set status, enter courier and tracking, mark ready for pickup. Add routes, controller and a panel in `owner/view-bookings.blade.php`. (Per-booking quotes, ownership and pickup/courier transitions tested.)
- [x] Client side: choose pickup or courier (with address) at booking or from booking details; pay the amount through the existing payment flow (`BookingController@initializePayment`) or add it to the booking total; follow the status. (Booking-detail acceptance, charge-once, checkout conflict and existing checkout tested with mocked Stripe service.)
- [x] Final delivery step: the owner records the delivery type (softcopy, hardcopy or both) when completing (`StudioOwner/BookingController@completeBooking`, `:1215`). Completion rules in `BookingModel` (`:367, :465`) account for hardcopy. (Drive-only and hardcopy-only completion, outstanding-order guards and alternate status endpoint tested.)
- [x] Notifications for ready, shipped, delivered. (`HardcopyOrderTest` verifies three notifications and no duplicate notification on repeated delivery.)
- [x] Tests: download blocked before approval and for other users, drive link validation, hardcopy requires a fulfillment method, status transitions.

## Done when
- [x] Owner can share a Drive link instead of uploading.
- [x] Client sees a download button only for approved, published galleries.
- [x] Hardcopy orders require pickup or courier and can be tracked to delivered.

## Verified progress
- Final focused gallery, hardcopy, migration, completion, assignment, status and venue run: **41 passed, 282 assertions**.
- Client gallery details now enforce booking ownership, including mismatched legacy gallery client IDs. Regression failed with 200 before fix, then passed with 404.
- Owner gallery now renders a labelled Drive-only delivery form with saving, error and published-lock states. Missing-form regression failed before implementation; actual owner save/publish/client-details lifecycle passes in feature tests.
- Reopening booking details accumulated hardcopy submit handlers; late responses could also replace a newer booking panel. Both regressions failed before fixes. `node --test tests/Integration/HardcopyPanel.test.cjs`: **2 passed**.
- Full-suite initial audit: **583 passed, 5 failed**. Two known unrelated fixture failures remain; three Phase 9 compatibility failures were corrected and their focused tests pass (updated delivery message, status fixture schema, and avoiding writes to an absent legacy delivery-type column when no type was supplied).
- Final `php artisan test --compact`: **593 passed, 2 failed, 2888 assertions**. Both failures are the documented unrelated fixtures listed below.
- `npm run build`: passed. Scoped Pint: passed for Phase 9 controllers, models, rule, migrations and new/updated Phase 9 tests. PHP syntax checks: passed for modified legacy controllers/model and compatibility tests. `php artisan view:cache`: passed. No standalone typecheck or JavaScript lint script is configured; none was claimed.
- `git diff --check`: passed, with existing CRLF normalization warnings in booking views.
- Impeccable detector on owner gallery and hardcopy partials returned `[]`.

## Browser evidence and design decisions
- Rendered current Blade templates using `Phase9UiFixture.php` with an enforced in-memory SQLite connection. `Phase9UiRouter.php` serves fixtures and public assets only. API responses and download payloads are mocked: these are UI integration checks, not authenticated end-to-end or real gateway checks.
- Drive form: invalid host shows server validation feedback; valid link shows saved feedback and enables publication. Input -> Save button keyboard order works; Escape closes modal.
- Hardcopy quote: Add item creates second fieldset; Remove item returns to one; submit produces one POST and saved feedback. Client courier acceptance reports missing address, then accepts address/contact on retry.
- Client gallery: single-photo link downloads `fixture.png` without opening fullscreen; ZIP link downloads `fixture.zip`; Drive link has the expected HTTPS URL, `_blank`, and `noopener noreferrer`. Escape closes gallery modal.
- Checked 1280px and 390px, light and dark. New panels fit; hardcopy page has no horizontal overflow; new controls have 44px resting targets. Screenshots saved outside repo under the approved temporary directory.
- Original primary blue measured 4.46:1. New primary controls use a slightly darker blue for **5.33:1** contrast. Secondary delivery controls use existing theme body-color tokens for **8.41:1 light / 8.39:1 dark**. Existing typography, spacing and modal patterns remain unchanged.
- No new production assets, libraries or visual system. Browser fixtures use explicitly labelled sample data. Shared-template demo asset/translation/notification requests produced console errors on the isolated server; new controls produced no JavaScript runtime exceptions. These errors were not hidden or claimed fixed.

## Files changed this session
- Behavior/UI: `app/Http/Controllers/Client/OnlineGalleryController.php`, `app/Http/Controllers/StudioOwner/BookingController.php`, `resources/views/owner/view-online-gallery.blade.php`, `resources/views/client/view-online-gallery.blade.php`, `resources/views/partials/hardcopy-orders.blade.php`, `resources/views/partials/hardcopy-script.blade.php`.
- Tests: `tests/Feature/Gallery/GalleryDownloadTest.php`, `tests/Feature/Gallery/DeliveryMigrationTest.php`, `tests/Feature/Booking/HardcopyOrderTest.php`, `tests/Feature/Booking/BookingAssignmentDomainTest.php`, `tests/Feature/Booking/BookingStatusTest.php`, `tests/Feature/Venue/VenueConfirmationChainTest.php`, `tests/Integration/HardcopyPanel.test.cjs`, `tests/Integration/Phase9UiFixture.php`, `tests/Integration/Phase9UiRouter.php`.
- Formatting only on existing uncommitted work: `app/Http/Controllers/GalleryDeliveryController.php`, `app/Http/Controllers/HardcopyOrderController.php`, `app/Models/HardcopyOrderModel.php`, `app/Models/HardcopyItemModel.php`.
- Tracking: this file. Existing routes, delivery migrations and other uncommitted changes were preserved.

## Assumptions and remaining checks
- Sharing hosts are HTTPS `drive.google.com` and `docs.google.com` only. No non-Google provider is approved or added. Sharing permissions remain the provider's responsibility; no Drive API integration.
- Hardcopy quotes are per booking; clients choose fulfillment in booking details. Accepted price is added once to booking total and existing checkout balance, not a new payment subsystem. Accepted cancellation uses the existing cancellation/refund workflow.
- Legacy null delivery-type behavior remains backward compatible. Legacy galleries with null approval can still be viewed under existing policy, but receive no Drive/download links and cannot use new download routes.
- Initial full-suite blockers are resolved in the follow-up above, not skipped. Readiness now tests the current app's actual contract/payment APIs with user approval.
- Local Phase 9 schema is enabled, with original data preserved. Authenticated acceptance ran on the disposable clone. Initial settlement was simulated; the subsequent sandbox pass above completed the real Stripe test-mode checkout and callback. No live card charge was made.
- No remaining Phase 9 acceptance blocker found. Existing shared-template translation/demo asset errors remain outside Phase 9; delivery controls have no runtime exceptions. Direct ZIP browser navigation required the documented real-response relay due to browser transport behavior, not a fabricated download payload.
- Exact next step: use Phase 9 locally. Production deployment is separate from local/test acceptance. No commit without explicit instruction.

← [Previous: Phase 8](./phase-8.md) | [Next: Phase 10](./phase-10.md) →
