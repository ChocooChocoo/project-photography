[Next: Phase 2 →](./phase-2.md)

**Status:** ✅ Done

# Phase 1: Quick defect fixes (employees page, CSRF, gallery notification)

**Goal:** Fix the three defects that need no schema change: the `/owner/view/employee` HTTP 500, the "CSRF token mismatch" error in the booking status modal, and the missing owner notification when a gallery waits for approval.

## Dependencies

- None

## Tasks

- [x] Employees page: in `resources/views/owner/view-employee.blade.php:15`, replace inline `@php($slots = $staffSlots[$studio->id])` with a block `@php $slots = $staffSlots[$studio->id]; @endphp`. Root cause: the inline `@php(` sits before the block `@php … @endphp` (lines 86–119), so Blade's block pattern swallows everything between them and leaves `@foreach`/`{{ }}` uncompiled (see compiled `storage/framework/views/1a6ea458203f395621b2f56098da3426.php:14`).
- [x] Apply the same fix to `resources/views/studio-hr/view-employee.blade.php:15`.
- [x] Run `php artisan view:clear` to drop the broken compiled views.
- [x] Add a regression test for both views. Changed from plan: `tests/Feature/StudioEmployee/EmployeeViewCompileTest.php` compiles both views and parses the result, because `EmployeeProvisioningTest` builds a small schema that cannot render the full layout. The full page was checked live instead (see Verification).
- [x] CSRF: fixed once in the shared helper `public/assets/js/pages/session-token.js`, which every portal layout loads (`layouts/partials/portal-base-scripts.blade.php:75`). Changed from plan: no per-modal edit.
  - Root cause: the helper already refreshed the token and retried after a 419. But the caller's own `error:` handler fired first and showed "CSRF token mismatch.", and a caller's own `beforeSend` replaced the helper's `ajaxSetup` header.
  - Fix: a `$.ajaxPrefilter` now sets `X-CSRF-TOKEN` on every non-GET request. On a 419 it holds the caller's `error`/`complete` handlers, fetches a fresh token from `/csrf-token`, and sends the request once more with the caller's handlers. The caller sees only the retry result.
- [x] Freelancer status modal (`resources/views/freelancer/view-bookings.blade.php:866-870`) is covered by the same shared fix. The retry replaces its render-time `_token` with the live one. No edit needed.
- [x] Server message: `bootstrap/app.php` now renders a JSON 419 as "Your session expired. Reload the page and try again." This message shows if the retry also fails. If the session has fully ended, the retry gets `OwnerMiddleware`'s 401 "Please login to access this page."
- [x] Added `notifyGalleryAwaitingApproval($gallery)` to `app/Traits/Notifiable.php`. It finds the owner through `StudiosModel.user_id`, uses type `gallery_awaiting_approval`, and routes to `owner.online-gallery.index`.
- [x] Called it from the three pending paths:
  - `app/Http/Controllers/StudioPhotographer/OnlineGalleryController.php` (`submitForApproval`)
  - `app/Http/Controllers/StudioHR/OnlineGalleryController.php` (`submitForApproval`)
  - `app/Http/Controllers/GalleryDeliveryController.php` (`saveDrive`), only when `approval_status` is `pending`. Owner and freelancer links are auto-approved, so they send no notification.
- [x] Tests in `tests/Feature/Gallery/PhotographerGalleryPublishTest.php` cover the photographer submit, HR submit, and photographer vs owner drive link. Each checks one unread `tbl_notifications` row for the owner and `notifications.unread-count` = 1.
  - The fixture now creates `tbl_notifications` from the real migration.
  - `GalleryDownloadTest.php` reuses that fixture, so its own duplicate migration call was removed.

## Done when

- [x] Employee views compile and parse: `php artisan test --compact --filter=EmployeeViewCompileTest` passes (1 test, 6 assertions, plus a `token_get_all(..., TOKEN_PARSE)` parse of each compiled view). It failed before the fix with raw `@foreach` in the output.
- [x] `GET /owner/view/employee` returns 200 in the running app after `php artisan view:clear`. Checked through the HTTP kernel against the dev database as owner `adrian.alcantara@gmail.com`: status 200, page title present.
- [x] A JSON request with a stale token returns 419 with the plain-language message. `CsrfTokenMismatchTest` passes (8 tests); the new message test failed before the fix with "CSRF token mismatch.".
- [x] The modal no longer sees the 419. `node --test tests/Integration/SessionTokenRetry.test.cjs` passes 4/4 (hold and retry with the caller's handlers, other errors pass through, failed refresh passes the 419 through, GET untouched). All 4 failed against the old helper.
- [x] The gallery notification tests pass for all three submit paths. All 3 failed before the fix with 0 notifications.

## Verification (2026-10-24)

- Related suites: `php artisan test --compact --filter="PhotographerGalleryPublishTest|GalleryDownloadTest|GalleryLifecycleTest|CsrfTokenMismatchTest|EmployeeViewCompileTest|EmployeeProvisioningTest|Notification"` gave 64 passed (319 assertions).
- Full suite: `php artisan test --compact` gave 677 passed (3377 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 19 passed, 0 failed.
- Build: `npm run build` succeeded.
- Pint: `vendor/bin/pint --test` flags `Notifiable.php` and both `OnlineGalleryController.php` files for style issues that were already there. The new lines follow Pint style. The whole files were not reformatted, to keep the diff small.
- Not measured: the browser was not available in this session, so the modal's 419 behaviour was checked by the Node test, not by clicking in a browser.
- No UI markup changed in this phase, so no design review was needed.

## Browser check (2026-10-24, Playwright, 1920×1080, dev database)

- **Employees page.** Owner `adrian.alcantara@gmail.com` opened `/owner/view/employee` with status 200. The staff badge and employee table render. Screenshot: `.playwright-mcp/p1-employees.png`.
- **Status modal, mocked server.** Owner `yolanda.inocencio@gmail.com`, booking FS-BKS-00022.
  - The page's token was set to a stale value. "Update Status" → In Progress → confirm.
  - The first PUT carried the stale token and got 419. The helper fetched `/csrf-token` once and retried with the live token, and the modal showed only the success message.
  - Both server responses were mocked, so the booking did not change. Screenshot: `.playwright-mcp/p1-csrf-retry.png`.
- **Same path against the real server.** A PUT with a stale token returned 419, and the retry passed the CSRF check and reached validation (422 "Invalid booking status selected." for a deliberately invalid status). The caller saw only the final result.
- **Gallery notification (follow-up check).** HR `zacarias.panganiban@gmail.com` uploaded a draft gallery for booking 21 and clicked "Submit for approval" (200, "Gallery submitted for owner approval."). The owner's unread count went from 3 to 4, and the bell lists "Gallery Awaiting Approval: The gallery \"T02 approval check\" is waiting for your approval." Screenshots: `.playwright-mcp/gap4-hr-submitted.png`, `gap4-owner-bell.png`.

## Files changed

- `resources/views/owner/view-employee.blade.php`
- `resources/views/studio-hr/view-employee.blade.php`
- `public/assets/js/pages/session-token.js`
- `bootstrap/app.php`
- `app/Traits/Notifiable.php`
- `app/Http/Controllers/StudioPhotographer/OnlineGalleryController.php`
- `app/Http/Controllers/StudioHR/OnlineGalleryController.php`
- `app/Http/Controllers/GalleryDeliveryController.php`
- `tests/Feature/StudioEmployee/EmployeeViewCompileTest.php` (new)
- `tests/Integration/SessionTokenRetry.test.cjs` (new)
- `tests/Feature/Security/CsrfTokenMismatchTest.php`
- `tests/Feature/Gallery/PhotographerGalleryPublishTest.php`
- `tests/Feature/Gallery/GalleryDownloadTest.php`

## Notes

- Deployment: run `php artisan view:clear` on each server, because the broken compiled views live in `storage/framework/views`.

---

[Next: Phase 2 →](./phase-2.md)
