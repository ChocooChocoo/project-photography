[← Previous: Phase 10](./phase-10.md)

**Status:** ✅ Done (deployed 2026-10-25; S06 waits for the testers)

# Phase 11: Panel re-test failures on the live site

**Goal:** Fix the code bugs behind the "Thesis Panel Re-test: T02 Fixes" results. The testers' answers were read from the sheet on 25 October 2026, and each failure was checked on https://lumoraphotography.online.

## Re-test results and what they mean

| Test | Sheet | Checked on live (2026-10-25) | Action |
|---|---|---|---|
| S06 Packages | Doesn't work: "error when creating a package, specifically when user chose on-location" | Reproduced. Server error 500 (see task 1). | Fixed here |
| S12 Equipment | Doesn't work: "everything here is working", but asks for inventory quantity and preferred-brand priority | Catalog dropdown, maintenance/retired items, overlap refusal and photographer confirmation all work. No quantity column; preferred brand is only labelled, not listed first. | New requests, not a defect. Not changed. |
| S15 Printed photos | Doesn't work: cannot schedule 11/20/2026, cannot complete the booking | Booking 11/20/2026 works by typing and in the calendar pop-up. Completion waits for delivery, and delivery waits for the event end (Phase 4 rule). | Expected behavior. The test needs a past-dated booking. |
| N06 Second studio | Doesn't work: "unnecessary since the administrator can change how many studios can be created in a plan" | Live plans allow 1 studio: "Your subscription plan only allows up to 1 studio(s). You have already reached this limit." | Plan setting. Not changed. |
| N01–N05, S10, S19, RC | Works | N02 status modal checked on live: a stale token gets 419, the page refreshes it, and the retry is sent as POST with `_method=PUT`. | — |

## Tasks

**1. On-location packages fail with a server error (S06)**

- [x] Reproduced on live as owner Yolanda: On-Location with coverage scope "Cavite" returned 500 `SQLSTATE[23000] ... CONSTRAINT tbl_packages.coverage_scope failed`. Reproduced on the local MySQL 8.4 database: `3140 Invalid JSON text`.
- [x] Root cause: `tbl_packages.coverage_scope` is a JSON column (`2026_01_26_055451_create_tbl_packages_table.php:23`). The seeded rows hold a list such as `["Cavite","Laguna","Metro Manila"]`, but the owner form sends typed text, which is not valid JSON. In-Studio packages leave it empty, so they worked. The tests use SQLite, which does not check JSON, so they missed it.
- [x] Fix in one place, `app/Models/StudioOwner/PackagesModel.php`: a `coverage_scope` setter stores the typed text as a JSON list ("Cavite, Laguna" becomes `["Cavite","Laguna"]`). A JSON list passed in, such as from the seeders, is kept. No migration, so no database change on live.
- [x] The edit form showed the raw JSON (`["Cavite","Laguna"]`). It now shows "Cavite, Laguna" through `coverageScopeText()`.
- [x] Test: `PackageCreateAccessTest::test_on_location_coverage_scope_is_stored_as_a_json_list` creates and edits an on-location package and reads the raw stored value. It failed before the fix (`Cavite, Laguna ,,` stored as text).
- [x] Checked on the local MySQL 8.4 database: the old raw write fails with `Invalid JSON text`, and saving "Cavite, Laguna" through the model stores `["Cavite", "Laguna"]`.

**2. Owners could skip the subscription check (security, from Phase 2)**

- [x] `EnforceStudioSubscriptionAccess` skipped the check for any request with `type=freelancer`. An owner could add that field to save a package for a studio with no plan.
- [x] Fix: the exemption applies only to `client.bookings.*` routes, where a client books a freelancer and there is no studio plan to check. No staff page sends `type=freelancer`.
- [x] Test: `PackageCreateAccessTest::test_owner_cannot_skip_the_subscription_check_by_sending_type_freelancer`. It failed before the fix (200, package saved) and now gets 403.

## Done when

- [x] `php artisan test --compact` passes: 727 passed (3688 assertions).
- [x] Deployed to live, and an on-location package saves there (see "Live check").
- [ ] Testers re-test S06.

## Verification (2026-10-25)

- Full suite: 727 passed (3688 assertions).
- Pint: the middleware and the test pass. `PackagesModel.php` has style issues that were already there (the same list before and after this change).
- No JavaScript changed, so the Node tests and the build were not re-run.
- Not clicked in a browser locally. The fix is in the model, and the test and the MySQL check cover it.

## Live check (2026-10-25, https://lumoraphotography.online)

`main` was fast-forwarded to `3c1f2c62` and pushed; Hostinger auto-deployed it. No migration was needed.

- Owner Yolanda created "T02 test on-location (delete me)" (package 50, inactive) through Create Package with On-Location and coverage scope "Cavite, Laguna". The response was 200 "Package created successfully!". Before the deploy, the same form returned 500.
- The package is stored with `coverage_scope` `["Cavite","Laguna"]`, and the edit form shows "Cavite, Laguna".
- Saving the edit form with "Cavite, Laguna, Batangas" returned "Package updated successfully!" and stored `["Cavite","Laguna","Batangas"]`.
- Not checked on live: the `type=freelancer` fix. Live has no studio without a plan to test it against. The feature test covers it.
- Test data left on live: package 50 on Quarry Road Studio, inactive, so clients do not see it. Owners have no delete action for packages, so remove it in phpMyAdmin if needed.

## Open (not changed)

- `APP_DEBUG` is still on in the live `.env`. The S06 error showed the SQL, the database name and the host. Set `APP_DEBUG=false` and clear the config cache.
- The client package card prints `coverage_scope` as raw JSON (`booking-forms.blade.php` around line 1219). This affects every seeded package and predates T02.
- S12 requests: a quantity per catalog item, the preferred brand listed first, and hiding reserved equipment from the dropdown. These need approval.

## Files changed

- `app/Models/StudioOwner/PackagesModel.php`
- `app/Http/Middleware/EnforceStudioSubscriptionAccess.php`
- `resources/views/owner/edit-packages.blade.php`
- `tests/Feature/Owner/PackageCreateAccessTest.php`

---

[← Previous: Phase 10](./phase-10.md)
