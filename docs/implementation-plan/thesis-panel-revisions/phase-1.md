[Next: Phase 2](./phase-2.md) →

# Phase 1: Admin and Auth Defects

**Status:** 🟡 In Progress (4 of 5 defects fixed and tested; login cause not confirmed)

**Goal:** Fix the reported crashes and dead controls in the admin panel, the freelancer barangay loader, and login, so the base platform works before new features are added.

## Dependencies
None. First phase.

## Login investigation (recorded 2026-10-07)
- The login API works: `tests/Feature/Auth/LoginDashboardRedirectTest.php` logs in as owner, client, freelancer, studio-hr, studio-finance and studio-photographer, gets the right dashboard URL, and stays signed in.
- In a real browser (Playwright, seeded owner `adrian.alcantara@gmail.com`), login reaches `/owner/dashboard` when the page is allowed to finish loading. The local `php artisan serve` is very slow here (page load 20–35 s), so early clicks did not redirect.
- Found and fixed: the redirect ran only from the SweetAlert `didClose` callback (`resources/views/auth/login.blade.php`). A 2 s timer now also redirects.
- Found and fixed: the portal middleware `getUserDashboard` maps (`Admin`, `Owner`, `Client`, `Freelancer`, `StudioHR`) were missing roles, so a wrong-role user was sent to `login`. All maps now list every role.
- Fixed locally (2026-10-14): `app.js` `I18nManager` now defaults to `/assets/data/translations/`, avoiding the page-relative request that returned login HTML instead of JSON. Six translation regressions pass; browser and broader verification recorded below. This is not proof of the reported production login cause.
- **Open:** the exact production cause of "loads, then clears the fields" is not reproduced. Retest login on the deployed site.

## Local retest and translation-path fix (2026-10-14)
- Auth tests: `php artisan test --compact tests/Feature/Auth/LoginDashboardRedirectTest.php tests/Feature/Auth/OnboardingFlowTest.php tests/Feature/Auth/FirstLoginPasswordChangeTest.php`: **34 passed / 165 assertions**.
- Real browser, disposable local SQLite database: owner, client, freelancer, HR, finance and photographer login returned 200, reached the expected dashboard, survived reload, and redirected an authenticated login-page visit back to that dashboard. Wrong password returned 401 with visible feedback, retained fields and re-enabled Sign In. No production/business database modified.
- The translation error was independently reproduced: nested login/dashboard/profile URLs fetched a page-relative JSON path and received HTML. Test-first proof: five translation regressions failed, existing missing-file fallback test passed.
- Smallest fix: add one leading slash to the existing manager's default `langPath` in `public/assets/js/app.js`. Selected language, explicit path overrides, JSON parsing and error fallback remain unchanged. Normalized SHA-256 comparison confirms no other minified-script edits.
- `node --test tests/Integration/TranslationPaths.test.cjs`: **6 passed**. `node --check public/assets/js/app.js`: passed.
- Browser follow-up: `/auth/login`, `/admin/login`, owner dashboard and nested client studio profile requested `/assets/data/translations/en.json`, returned **200 / application/json**, and parsed the real dictionary. Saved German selection requested `/assets/data/translations/de.json` with 200. **Zero translation-load errors and zero JavaScript exceptions** during this check; owner/client sign-in still reached their dashboards. Unrelated template image paths were not changed.
- Final verification: `php artisan test --compact`: **671 passed / 3342 assertions**, 114.72 seconds. `node --test tests/Integration/*.test.cjs`: **15 passed** (six new translation checks). `npm run build`, JavaScript syntax checks on app/test and scoped `git diff --check`: passed. No configured npm lint/typecheck script; no new tooling introduced.
- Files changed: `public/assets/js/app.js`, new `tests/Integration/TranslationPaths.test.cjs`, and this file. No commit authorized for this follow-up. Disposable browser server stopped after checks.
- Deployment assumption: application assets served from site-root `/assets/`, matching current local routes/layout URLs. A subdirectory/CDN deployment needs its own asset-base configuration; not added speculatively here.

## Tasks
- [x] Login: reproduce with a real browser session (Playwright). Check that the session cookie survives the redirect after `AuthController@login`. Record the confirmed cause in this file before fixing. (Recorded above. Session cookie is set; exact cause unconfirmed.)
- [ ] Login: fix the confirmed cause. (Hardened the redirect and the role maps. Cause not confirmed, so left open.)
- [x] Login: add `tests/Feature/Auth/` test that follows the dashboard redirect after login and asserts the user stays signed in. (`LoginDashboardRedirectTest`)
- [x] Translation load: fix the confirmed nested-page URL error without changing authentication behavior; add default-path, saved-language, explicit-path and failure-fallback regression coverage. (Node and browser proof above.)
- [x] View Users: change the import in `app/Http/Controllers/Admin/UserController.php` to `App\Models\Admin\UserModel`. (`Admin\UserModel` now uses `SoftDeletes` so deletes stay soft.)
- [x] View Users: add a feature test that loads `admin.user.index` with users of several roles and statuses. (`tests/Feature/Admin/AdminPhase1Test.php`)
- [x] Subscription Update: create `resources/views/admin/edit-subscription-plans.blade.php`.
- [x] Subscription Update: add a test for `edit` (200) and `update` (saves, `plan_code` unique ignoring self).
- [x] View Freelancer: replace the dead `href="#"` links with real buttons and JS. Add update and delete routes and methods. Fix the routes that pointed to missing methods (`admin.freelancer.data` and `admin.freelancer.details` removed; `admin.freelancer.update` and `admin.freelancer.delete` added).
- [x] View Freelancer: add tests for update and delete.
- [x] Barangays: remove `json_decode` in `Freelancer/ProfileController.php` (`store` and `getBarangays`). Add a null guard and the `active()` scope.
- [x] Barangays: add a test modelled on `StudioBarangayLoadingTest`.

## Done when
- [ ] A user with valid credentials lands on their role dashboard and stays signed in. (Passes in tests and in the browser after full load; production cause open.)
- [x] "View Users" loads for all roles with no ErrorException.
- [x] Subscription plan "Update" opens an edit page and saves.
- [x] Admin freelancer Update and Delete work.
- [x] Freelancer profile setup loads barangays for a selected city. (Verified by test, not clicked in a browser.)
- [x] `php artisan test --compact` passes. (Fresh 2026-10-14 run: **671 passed / 3342 assertions**. Replaces the old 526-pass/missing-migration failure note; production login cause remains open.)

[Next: Phase 2](./phase-2.md) →
