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
- Not fixed: `app.js` `I18nManager` fetches `assets/data/translations/en.json` with a relative path. On `/auth/login` this becomes `/auth/assets/data/translations/en.json`, which redirects to the login page (a wasted request, a console error, no visible break).
- **Open:** the exact production cause of "loads, then clears the fields" is not reproduced. Retest login on the deployed site.

## Tasks
- [x] Login: reproduce with a real browser session (Playwright). Check that the session cookie survives the redirect after `AuthController@login`. Record the confirmed cause in this file before fixing. (Recorded above. Session cookie is set; exact cause unconfirmed.)
- [ ] Login: fix the confirmed cause. (Hardened the redirect and the role maps. Cause not confirmed, so left open.)
- [x] Login: add `tests/Feature/Auth/` test that follows the dashboard redirect after login and asserts the user stays signed in. (`LoginDashboardRedirectTest`)
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
- [ ] `php artisan test --compact` passes. (526 pass, 1 fail: `BookingReadinessEndpointsTest` requires the missing migration `2026_09_30_100000_add_booking_duration_and_contracts.php`. This failure is not from Phase 1.)

[Next: Phase 2](./phase-2.md) →
