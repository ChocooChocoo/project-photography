[← Previous: Phase 1](./phase-1.md) | [Next: Phase 3 →](./phase-3.md)

**Status:** 🔄 In Progress

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
- [ ] Middleware: for gated GET/HEAD pages where the studio cannot be resolved, let the request through when the owner has at least one `subscriptionAccessible()` studio. The page is the studio picker, and the POST still checks the chosen studio. POST stays fail-closed, so `SubscriptionAccessTest::test_manage_access_fails_closed_when_studio_cannot_be_resolved` must keep passing.
- [ ] `PackagesController::create`: pass every owned studio with a ready flag and a reason ("awaiting verification" or "needs an active subscription"), using `subscriptionAccessible()` from `app/Models/StudioOwner/StudiosModel.php:140-143`.
- [ ] `create-packages.blade.php` studio dropdown: list ready studios normally and not-ready studios as disabled options with the reason. If no studio is ready, show an empty-state message with a link to `owner.subscription.index`.
- [ ] `create-packages.blade.php` error handler: show `xhr.responseJSON.message` for any status. When the response has `redirect`, offer a "Go to subscription" button. Keep the generic text only when the server sends no message.
- [ ] Move the `originalText` declaration above its first use in `create-packages.blade.php`.
- [ ] `PackagesController::store`: log the studio id and exception with `Log::error` before the 500 response. Keep the generic message for the user.
- [ ] Check `resources/views/owner/edit-packages.blade.php` (or the matching edit view) for the same error handler and apply the same message fix if present.
- [ ] Tests: new `tests/Feature/Owner/PackageCreateAccessTest.php` through the real owner routes and middleware:
  - an owner with two studios, one subscribed, opens `owner.packages.create` and gets 200;
  - POST for the unsubscribed studio returns 403 with the subscription message;
  - POST for a subscribed new studio with `photographer_count=0` and no photographers returns 200 and creates the package;
  - a pending studio appears as a disabled option with its reason.
- [ ] UI: apply the antislop and impeccable design skills to the disabled options and empty state; match the existing Bootstrap form styles; keep it readable on a phone.

## Done when

- [ ] `php artisan test --compact --filter="PackageCreateAccessTest|SubscriptionAccessTest|PackageMediaTest"` passes.
- [ ] An owner with an old studio and a new subscribed studio can open Create Package and save a package for the new studio with no photographers.
- [ ] Saving for an unsubscribed studio shows "An active subscription or grace period is required for this studio." and a link to the subscription page, not "Failed to create package. Please try again."
- [ ] Submitting the form with a missing required field shows the validation alert with no ReferenceError in the console.

---

[← Previous: Phase 1](./phase-1.md) | [Next: Phase 3 →](./phase-3.md)
