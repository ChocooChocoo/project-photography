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

## Follow-up fixes (2026-10-25)

Each fix was tested locally (full suite), pushed to `main`, and checked on live after Hostinger deployed it.

**3. Exception details in JSON errors (security)** — `60041bcb`

- Cause: 181 catch blocks in the controllers return `$e->getMessage()`, so a failed save could show SQL, the database name and the host. Live `APP_DEBUG` is already `false`, so debug mode is not the cause.
- Fix in one place: new web middleware `App\Http\Middleware\HideInternalErrorDetails`, registered in `bootstrap/app.php`. With debug off it:
  - removes `error`, `exception`, `file`, `line` and `trace` from 5xx JSON responses;
  - replaces any 5xx message that has an exception joined in (a colon) with "Something went wrong. Please try again.";
  - replaces any text that names SQL, a connection, a PHP file or an `App\`/`Illuminate\` class, in any status;
  - logs the original response.
  Plain user messages such as 403, 409 and 422 rule messages are unchanged.
- Test: `tests/Feature/Security/HideInternalErrorsTest.php` (5 tests; 3 failed before the fix).
- Live: `/owner/packages/99999999/edit` changed from `No query results for model [App\Models\StudioOwner\PackagesModel]` to "Something went wrong. Please try again." An empty package form still lists 12 field errors, and completing booking 76 still says "All assigned photographers must mark their assignments as completed…".

**4. Coverage shown as raw JSON to clients** — `c2d3eb87`

- Cause: `Client\BookingController::normalizeCoverageScope()` returned a JSON string unchanged, and both package lists sent `coverage_scope` raw.
- Fix: one helper, `StudioPackagesModel::coverageScopeLabel()`, used by `normalizeCoverageScope()` and both package lists. `BookingPackageModel` formats older snapshots that hold the raw list.
- Test: `PhotoEditingPricingTest::test_coverage_scope_reaches_the_client_as_plain_text` (failed with `["Cavite","Laguna"]`).
- Live: the client package list and cards for Quarry Road read "Coverage: Cavite, Laguna, Metro Manila".

**5. Edit Studio "The barangay field is required."** — `4ead60f7`, `d0495ec1`

- Cause: until the barangay list loaded, the dropdown was disabled and empty, and the ZIP code was "Loading...". Clicking Update sent neither.
- Fix in `owner/edit-studio.blade.php`: the page starts with the saved barangay selected and the location's ZIP code in the hidden `zip_code` input, and the first load keeps them. The old script that added a second hidden ZIP input was removed.
- Test: `tests/Feature/Owner/EditStudioFormTest.php`.
- Live: with the barangay request held back for 15 seconds, the form already matched the saved data, Update returned "Studio updated successfully!", and the studio's form data was unchanged afterwards.

**6. "Event null" and " Gallery"** — `69759fc9`

- Cause: the client booking flow never sets `event_name`, so pages printed `null` and five gallery paths named new galleries " Gallery".
- Fix: `BookingModel::getEventNameAttribute()` falls back to the service category, then "Booking <reference>". Two booking column lists gained `category_id` so the fallback can use the category.
- Test: `tests/Feature/Booking/BookingEventNameTest.php` (2 of 3 failed before the fix).
- Live: photographer Eduardo's assignment for BK-6ADCE2471C46E returns `event_name` "Wedding Photography"; named bookings keep their name. Galleries created before this fix keep their stored name.

Full suite after all fixes: 737 passed (3714 assertions).

## Open (not changed)

- Test package 50 ("T02 test on-location (delete me)", inactive) is still on live. Owners have no delete action for packages.
- S12 requests: a quantity per catalog item, the preferred brand listed first, and hiding reserved equipment from the dropdown. These need approval.

## Files changed

- `app/Models/StudioOwner/PackagesModel.php`
- `app/Http/Middleware/EnforceStudioSubscriptionAccess.php`
- `resources/views/owner/edit-packages.blade.php`
- `tests/Feature/Owner/PackageCreateAccessTest.php`
- Follow-up fixes: `app/Http/Middleware/HideInternalErrorDetails.php` (new), `bootstrap/app.php`, `app/Models/BookingModel.php`, `app/Models/BookingPackageModel.php`, `app/Http/Controllers/Client/BookingController.php`, `Client/MyBookingsController.php`, `StudioPhotographer/AssignedBookingController.php`, `resources/views/owner/edit-studio.blade.php`
- Follow-up tests: `tests/Feature/Security/HideInternalErrorsTest.php` (new), `tests/Feature/Owner/EditStudioFormTest.php` (new), `tests/Feature/Booking/BookingEventNameTest.php` (new), `tests/Feature/Booking/PhotoEditingPricingTest.php`

---

[← Previous: Phase 10](./phase-10.md)
