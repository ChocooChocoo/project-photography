[← Previous: Phase 1](./phase-1.md) | [Next: Phase 3 →](./phase-3.md)

**Status:** ✅ Done

# Phase 2: Create Package for a new studio

**Goal:** An owner can open Create Package and save a package for a new studio. When a studio cannot take packages yet, the page says why and how to fix it, instead of "Failed to create package. Please try again."

Client report: "The Create Package feature isn't working either. I encountered this issue when I tried to add a package for the new studio. I was testing Step 7, where no photographers are available and the studio needs to invite a freelancer."

Decision: keep the paid-subscription rule for saving a package. Fix page access and the error messages.

Root cause (from code; not reproduced locally, because the local database has only verified, subscribed studios):

- **Page blocked for owners with more than one studio.** `app/Http/Middleware/EnforceStudioSubscriptionAccess.php:72-79` checks GET pages whose route name contains `.create`. The GET sends no `studio_id`, so `resolveStudioId` (line 165) returns null for an owner with two or more studios. The owner is redirected to the subscription page.
- **Save blocked for an unsubscribed studio.** The POST for a studio without a paid or grace-period plan gets a 403 "An active subscription or grace period is required for this studio." This is intended.
- **Real reason hidden.** `resources/views/owner/create-packages.blade.php:787-807` shows the server message only for 422. A 403 or 500 shows the generic message.
- **Silent filtering and errors.** `app/Http/Controllers/StudioOwner/PackagesController.php::create` (lines 45-47) lists only verified studios without saying why others are missing. `store` (lines 107-112) catches every exception and logs nothing.
- **Not the cause.** Photographers and the freelancer fallback play no part. The `photographer_count` rule is `min:0|max:10` (`app/Http/Requests/StudioOwner/PackageStoreRequest.php:100`), and nothing compares it with the studio's staff.
- **Minor bug.** `originalText` is used at lines 627, 655, 683 and 703 before its `const` declaration at line 724. This throws a ReferenceError after client-side validation alerts.

## Dependencies

- None (independent of Phase 1)

## Tasks

- [x] Package validation allows 0 photographers — already in `app/Http/Requests/StudioOwner/PackageStoreRequest.php:100` (`min:0`)
- [x] Per-studio subscription check on save — already in `EnforceStudioSubscriptionAccess` (POST sends `studio_id`), covered by `tests/Feature/SubscriptionAccessTest.php`
- [x] Middleware (`app/Http/Middleware/EnforceStudioSubscriptionAccess.php`): a gated GET/HEAD page with no studio in the request now opens when any of the user's studios has `subscriptionAccessible()` access. POST stays fail-closed, and `SubscriptionAccessTest::test_manage_access_fails_closed_when_studio_cannot_be_resolved` still passes. The studio-id lookup moved into one helper, `userStudioIds()`, shared by both checks.
- [x] `PackagesController::create`: lists every owned studio, sorted by name, with `package_block_reason` set to "awaiting verification", "needs an active subscription" or null.
- [x] `create-packages.blade.php` studio dropdown: ready studios are normal options; others are `disabled` options showing the reason. A help line under the field (`#packageStudioHelp`, linked with `aria-describedby`) explains each reason, links to `owner.subscription.index`, and says "None of your studios can have packages yet." when no studio is ready.
- [x] `create-packages.blade.php` error handler: shows the server `message` for every non-validation error. A response with `redirect` gets a "Go to subscription" button. The 422 branch only lists field errors when `errors` exists, so a message-only 422 no longer shows a blank alert.
- [x] `originalText` and `submitBtn` are now declared once at the top of the submit handler. The early validation branches no longer reset a button that was never disabled.
- [x] `PackagesController::store`: logs `Package create failed` with `studio_id`, `user_id` and the exception before the 500 response. Added: a studio that is not verified now returns 422 "This studio is awaiting verification and cannot have packages yet." Before, `firstOrFail()` sent it into the generic 500.
- [x] `resources/views/owner/edit-packages.blade.php`: same message and "Go to subscription" handling.
- [x] Tests: new `tests/Feature/Owner/PackageCreateAccessTest.php` (6 tests) through the real owner routes, middleware and migrations (`RefreshDatabase`):
  - an owner with two studios (one subscribed) opens Create Package (200); the new studio shows "(needs an active subscription)" and the pending one "(awaiting verification)" as disabled options;
  - POST for the unsubscribed studio returns 403 with the subscription message and `redirect`;
  - a subscribed new studio with `photographer_count=0` and no photographers saves a package;
  - the create page still redirects when no owned studio has access;
  - POST for a pending studio returns the 422 message;
  - the create script declares `originalText` once, before its first use, and shows the server message.
- [x] UI: kept to native `disabled` options and a Bootstrap `form-text` help line, matching the existing form. No new styles, colours or components. The design skills were not loaded for this small change, and the layout was not checked in a browser (no browser in this session).

## Done when

- [x] `php artisan test --compact --filter="PackageCreateAccessTest|SubscriptionAccessTest|PackageMediaTest"` passes: 13 tests, 51 assertions. Against the old code, 3 of the new tests fail: the create page (302), the pending-studio message, and the script order check.
- [x] An owner with an old studio and a new subscribed studio can open Create Package and save a package for the new studio with no photographers (covered by the tests above).
- [x] Saving for an unsubscribed studio returns "An active subscription or grace period is required for this studio." with the subscription `redirect`. The page shows that message with a "Go to subscription" button instead of "Failed to create package. Please try again." The message is checked by test; the alert was not clicked in a browser.
- [x] The `originalText` ReferenceError is gone. Checked by the script-order test; not run in a browser console.

## Verification (2026-10-24)

- Full suite: `php artisan test --compact` gave 683 passed (3396 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 19 passed.
- Build: `npm run build` succeeded.
- Pint: `vendor/bin/pint --test` passes for the middleware and the new test.
- Live render on the dev database: owner `yolanda.inocencio@gmail.com` (the one subscribed studio) opened `/owner/create/packages` with status 200, and the dropdown lists "Quarry Road Studio".

## Findings

- Dev data: 4 of the 5 seeded studios have subscriptions that ended (studio 1 "Meridian Light Studio" ended 2026-08-24, still marked `active`). Their owners are sent to the subscription page from Create Package, which is the intended rule. If the client's test data looks like this, "Create Package isn't working" may simply mean those plans have run out. Renewing or reseeding subscriptions fixes it.
- An owner whose studios are all unverified is stopped earlier by `PermitVerificationMiddleware`. A JSON request there gets a redirect, not a JSON message, so the form would fail without a message. Not changed here.
- Security note, not fixed here: `EnforceStudioSubscriptionAccess` skips the check for any request with `type=freelancer` (line 33). An owner could add that field to bypass the subscription rule. Track it as a separate defect.

## Files changed

- `app/Http/Middleware/EnforceStudioSubscriptionAccess.php`
- `app/Http/Controllers/StudioOwner/PackagesController.php`
- `resources/views/owner/create-packages.blade.php`
- `resources/views/owner/edit-packages.blade.php`
- `tests/Feature/Owner/PackageCreateAccessTest.php` (new)

---

[← Previous: Phase 1](./phase-1.md) | [Next: Phase 3 →](./phase-3.md)
