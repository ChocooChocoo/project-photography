[← Previous: Phase 9](./phase-9.md) | [Next: Phase 11 →](./phase-11.md)

**Status:** ✅ Done

# Phase 10: Remaining acceptance failures

**Goal:** Fix the three failures on the "Thesis Panel Acceptance Tests" sheet that T02 does not cover: S06 "Photos, videos and extras on packages", S19 "Freelancer filling in" and RC "Full walkthrough: freelancer booking". The testers' notes were read from the sheet on 24 October 2026.

S10, S12 and S15 also failed on the sheet, but T02 already fixed them and the Phase 9 re-run passed them. Testers must re-test those three on a build that includes T02. That needs no code.

## Dependencies

- Phase 9 complete (green suite and the acceptance re-run baseline)
- A client decision on removing video from packages (see task 3). Decided on 24 October 2026: hide the field and keep stored data.
- The dev server running for the browser checks

## Tasks

**1. Package creation on a newly created studio (S06, S19)**

Tester notes: "unable to create new package" and "cannot proceed due to being unable to create a package on a newly created studio".

- [x] Reproduce in the browser. Owner Yolanda has Quarry Road Studio (subscribed) and T02 Annex Studio (new). Create Package showed the Annex as "(needs an active subscription)", and the subscription page offered no way to subscribe the Annex. Screenshot: `p10-w1-create-package-annex-blocked.png`.
- [x] Root cause. `UserModel` has no `studio` relation, so `$user->studio` is always null. `StudioOwner\SubscriptionController::subscribe()` then fell back to the owner's first studio. A plan is stored per studio (all plans have `max_studios = 1`, and "Subscription access is studio-scoped" is a recorded decision). So the plan always landed on the first studio, which already had one, and the answer was "You already have an active subscription". A second studio could never get a plan, so it could never have packages. `index()`, `history()`, `getStatusData()`, `getSubscriptionDetails()`, `cancel()` and `resume()` read only the first studio for the same reason.
  - Owner-level access (one plan covering every studio) was tried first and reverted. It broke four tests that encode the studio-scoped decision.
- [x] Fix in `SubscriptionController`.
  - `targetStudio()` picks the studio the owner chose (`studio_id`), or else the first owned studio without an active plan.
  - `subscribe()` and the plans page use it. A `studio_id` the owner does not own returns 404.
  - History, status, details, cancel and resume read every owned studio. The unused `studioForUser()` helper was removed.
  - The plans page has a "Plan for studio" picker, and the confirm modal names the studio.
  - The Create Package help link goes to the plan page for the blocked studio (`?studio_id=`).
- [x] Tests:
  - `tests/Feature/Subscription/SubscriptionTrialHistoryTest.php`, 2 new tests:
    - With no `studio_id`, the plan goes to the owner's studio that has none.
    - A chosen studio gets the plan, and another owner's studio gets 404.
    - Both failed before the fix, with 400 "You already have an active subscription".
  - `tests/Feature/Owner/PackageCreateAccessTest.php`: the Create Package link points to the blocked studio's plan page.

**2. Freelancer fill-in end to end (S19, RC)**

Tester note on RC: when no photographer is available, the owner should invite a freelancer. Once the freelancer accepts the booking, they should get a temporary permission that ends automatically when the project is complete.

- [x] Owner picks an approved freelancer member when no studio photographer is free. Already in `StudioOwner\BookingController` (around line 633) with `App\Services\FreelancerFallbackCandidates`.
- [x] Booking-scoped temporary role with `expires_at`. Already in `App\Services\FreelancerBookingAccess::grant()`.
- [x] Access ends when the booking is completed, cancelled or deleted, or when the assignment is rejected or completed. Already in `BookingModel` (lines 153 and 156), `BookingAssignedPhotographerModel` (line 80), and the `bookings:revoke-freelancer-access` command. Covered by `tests/Feature/Booking/FreelancerAccessTest.php`.
- [x] New bug found during the RC re-run: the owner could not invite a freelancer to a second studio. `StudioOwner\StudioMemberController::index()` and `invite()` used the owner's first studio only, so the invite list and the hidden `studio_id` always pointed at it.
  - Fix: `chosenStudio()` reads `?studio_id=`, with the first studio as the default, and both member pages have a studio picker.
  - `StudioMemberInviteRequest` now accepts only a studio the owner owns. Before, any existing studio id passed validation.
  - Test: new `tests/Feature/Owner/MemberInviteStudioTest.php` (invite to the second studio, and 422 for another owner's studio). It failed on the old controller.
- [x] Re-run RC in the browser (see "Browser check" below). Passed.
- [x] Grant timing: kept at assignment. The freelancer needs the access to see the assignment and accept it. Declining revokes it at once. So in practice "accepted" means access is used, and "declined" means none.
- [x] Expiry: changed. Access used to end at the event end, so a fallback freelancer could not upload the gallery afterwards. A studio booking cannot be completed without a published gallery.
  - New `FreelancerBookingAccess::accessEnd()` returns the event end plus `DELIVERY_DAYS` (30). This is only a safety cap. Access still ends at completion through the existing revoke.
  - `grant()`, `allows()`, the grant notification and the assignment page use it.
  - Test: `FreelancerAccessTest` checks that access works one hour after the event and ends at the cap. The expiry tests in `FreelancerAccessTest` and `tests/Feature/Chat/ChatTest.php` now travel to `accessEnd()`.
- [x] Wording: the owner's fallback note now says "until it is completed". The freelancer page says "Access ends when the booking is completed, at the latest …" and "Upload photos before your access ends".

**3. Video on packages (S06)**

Tester note: "we dont have videos since our focus is only photographic studios".

- [x] Decision (user, 24 October 2026): hide the field and keep stored data.
- [x] Removed the cover-video field and its script from `resources/views/partials/package-extras.blade.php`, which both owner and freelancer package forms share. Removed the video display from `client/booking-forms` and `client/booking-details`.
  - Stored videos stay: the form no longer sends `cover_videos[]` or `keep_cover_videos_sent`, and `HandlesMediaUploads::syncMedia` then keeps existing files.
  - Server validation and the `cover_videos` column are unchanged, so the field can come back without a migration.
- [x] `tests/Integration/PackageExtras.test.cjs` now also checks that the form has no video input and sends no video fields. The server video tests in `tests/Feature/Owner/PackageMediaTest.php` still pass unchanged.
- Service videos (`partials/service-media.blade.php`) are outside this decision and still shown. See "Open".

**4. Record**

- [x] This file, `09 - TASK TRACKER.md`, and parts `P-04`, `P-05` and `P-10`.

## Done when

- [x] A newly created studio can create a package, or the page shows the exact reason and next step, and a feature test covers it. The blocked studio links to its own plan page. After subscribing it, the package saves.
- [x] S06, S19 and RC pass in a live browser re-run, with screenshots.
- [x] The video decision is recorded, and the code matches it.
- [x] `composer test` exits 0 (725 passed, 3676 assertions), `node --test tests/Integration/*.test.cjs` passes (21), and `npm run build` succeeds.

## Browser check (2026-10-24, Playwright, dev database, one maximized window per role)

**S06 / S19: package for a new studio (owner Yolanda).**

- Create Package listed "T02 Annex Studio (needs an active subscription)". The help link pointed to `/owner/view/subscription?studio_id=6`, and the form had no video field. Screenshot: `p10-w1-create-package-annex-blocked.png`.
- The plan page opened with "Plan for studio: T02 Annex Studio" (`p10-w1-plans-annex-picker.png`). The confirm modal read "Studio Growth (monthly), ₱2,790.00/monthly, Studio: T02 Annex Studio" (`p10-w1-subscribe-confirm-annex.png`).
- The response was "Your 14-day free trial has started!", and plan 6 was stored on studio 6.
- Create Package then saved "Annex Portrait Session" (₱3,500, color grading ₱300) for the Annex studio as package 48, with `cover_videos` `[]`. Screenshot: `p10-w1-annex-package-saved.png`.

**RC: freelancer booking (owner Yolanda + freelancer Lorena + client Beatriz).**

1. The owner opened Invite Freelancers with "T02 Annex Studio" picked and invited Lorena. The invitation was stored on studio 6 (`p10-w1-invite-annex.png`). Lorena accepted and saw "You are now a member of this studio."
2. The client booked package 48 for 20 Nov 2026, 10:00–12:00, and paid ₱3,500 in full through Stripe. Booking 66 is `BK-6ADCCB449AC70` (`p10-w3-client-annex-paid.png`).
3. In Assign Photographer, the owner saw "Freelancer fallback: No studio photographer is available" with Lorena offered (`p10-w1-owner-freelancer-fallback.png`). The response was "1 photographer(s) assigned successfully".
   - A temporary role was created for user 52 on studio 6 and booking 66, with `expires_at` 2026-12-20 12:00 (event end plus 30 days).
4. Lorena's Studio assignments page showed the booking and "Access ends when the booking is completed, at the latest Dec 20, 2026 12:00 Asia/Manila." She chose "Accept assignment" (`p10-w2-freelancer-assignment.png`). The owner got "Photographer Accepted Assignment".
5. The event date was moved to 23 Oct 2026 (test data). Lorena still had the booking, now capped at Nov 22, with "Confirm arrival" available (`p10-w2-freelancer-after-event.png`).
6. Lorena confirmed arrival with a photo. The client confirmed her presence: "Photographer on-site presence confirmed successfully." (`p10-w3-client-confirm-freelancer.png`).
7. Lorena started work and uploaded 2 photos. The gallery went to owner review (`approval_status` pending), and the owner got "Gallery Awaiting Approval".
8. The owner approved the gallery (`p10-w1-owner-approve-freelancer-gallery.png`) and published it to the client.
9. Lorena chose "Complete work". Her temporary role was deleted, she got "Studio booking access ended", and her page showed "No active studio assignments" (`p10-w2-freelancer-completed.png`).
10. The owner marked the booking Completed: "Booking status updated successfully." (`p10-w1-owner-complete-booking.png`).
11. Opening `/freelancer/studio-assignments/28` now returns 403 "Temporary booking access is unavailable or has ended." (`p10-w2-freelancer-access-ended.png`).
12. The client's Online Gallery lists "T02 Annex Studio, BK-6ADCCB449AC70, 2 Photos" (`p10-w3-client-annex-completed.png`).

Test data left in the dev database:

- T02 Annex Studio (id 6), created directly in Phase 2, lacked what the app gives a new studio: the owner's `owner-super-admin` role for that studio, and a schedule. Both were added so the pages would open. The real path (`StudioOwner\StudioController`, line 282) assigns the role.
- The Annex now has a Studio Growth trial (plan 6), package 48, Lorena as an approved member (invitation 13), and completed booking 66 with its event date moved to 23 Oct 2026.

## Production check (2026-10-25, https://lumoraphotography.online, Stripe sandbox)

`main` was deployed at `9c05c3f8`. The 4 T02 migrations were applied through phpMyAdmin, because the host has no SSH. Before that, saving a studio failed with `Unknown column 'downpayment_type'`.

| Phase | Result | Evidence |
|---|---|---|
| 1 Employees page | Pass | `/owner/view/employee` returns 200 (it returned 500 before the deploy). |
| 1 Gallery notification | Pass | HR Zacarias submitted "Production T02 approval check" for FS-BKS-00020. The owner's bell lists "Gallery Awaiting Approval". `prod-p1-owner-bell.png`. |
| 1 Status change / token retry | **Fail, fixed below** | Every status change failed: 419, then 422 "Please select a booking status." |
| 3 Equipment | Pass | Empty-catalog message, catalog item "Camera EOS R6 / Canon", assignment, photographer "You confirmed this equipment.", and the client sees "Photographer confirmed". |
| 4 Printed photos | Pass | Courier suggested, the client chose pickup ("Your choice is final."), the balance went up by ₱500, and before the event: "Delivery can start after the event ends on Dec 04, 2026 5:00 PM." A forced request got 422. |
| 5 Downpayment setting | Pass | Off: the client sees "Full Payment" only. On: "30% downpayment / Full payment". |
| 6 Cancel and refund | Pass | The downpayment booking BK-6ADCE71B5F827 was kept as non-refundable. Fully paid BK-6ADCE75E53395: the owner entered ₱3,000, the admin recorded it, and the client sees "Target ₱3,000, Paid ₱7,900, Refunded ₱3,000, REFUNDED". |
| 7 Editing prices | Pass | Package 31 has color grading ₱400 and retouching ₱600. The client sees only those two. The summary shows ₱7,900 + ₱400 = ₱8,300 and a 30% downpayment of ₱2,490, which Stripe sandbox charged (BK-6ADCE2471C46E). |
| 8 Rescheduling | Pass | Monday refused ("The studio does not work on Mondays."), two dates offered, and the client chose Dec 4: "Your booking has been moved." |
| 9 Freelancer editing prices | Pass | Lorena's package 3 keeps ₱450 / ₱250 after reload. No video field. |
| 10 Videos hidden | Pass | No video field on owner or freelancer package forms. |
| 2/10 Second studio, S19/RC fallback | Not testable on production | Every plan has `max_studios = 1`, so a second studio is refused ("Your subscription plan only allows up to 1 studio(s)."). The fallback needs every Quarry photographer to be busy, and the after-event steps need a past date. Covered by the dev browser check above. |

**Bug found on production and fixed: PUT, PATCH and DELETE bodies are dropped by the host.**

- Symptom: the owner's status modal got 419 "Your session expired" on a fresh page. The token retry then got 422 "Please select a booking status."
- Cause: the production server does not pass form-encoded PUT bodies to PHP. Laravel saw neither `_token` nor `status`. JSON PUT and POST with `_method=PUT` both worked (`status=zzz` was read and refused as invalid). Dev reads PUT bodies, so the dev checks passed.
- Scope: 43 jQuery PUT/PATCH/DELETE calls in 34 views (booking status, galleries, employees, leave and overtime, packages, services and more).
- Fix: the shared prefilter in `public/assets/js/pages/session-token.js` now sends form-encoded and FormData PUT/PATCH/DELETE requests as POST with `_method`. JSON bodies are left unchanged. Every portal layout loads this script.
- Check: the same rule, injected into the live page, changed booking 76 to In Progress with one POST (200). `tests/Integration/SessionTokenRetry.test.cjs` has a new test for the method spoofing, and the retry test now expects POST with `_method=PUT`. Node: 22 passed.
- Deployed in `dfa08541` (Hostinger auto-deploy). Production now serves the script with `spoofMethod`.

**Second bug found on production and fixed: "Remove equipment" was refused with 403.**

- Symptom: removing equipment from booking 76 returned 403 "An active subscription or grace period is required for this studio." Quarry Road has an active plan.
- Cause: `EnforceStudioSubscriptionAccess::resolveBooking()` read the `{id}` of `owner.booking.equipment.destroy` (an equipment row, here 10) as a booking id, because the route name contains "booking". Booking 10 belongs to another studio. This is the same family as the Phase 3 equipment-confirm fix, which only covered `assignment.equipment.confirm`.
- Fix: both equipment routes now read the booking from the equipment row before the generic "booking" rule runs.
- Test: `SubscriptionAccessTest::test_equipment_confirm_is_checked_against_the_equipments_own_booking` now also deletes through a `booking.equipment.destroy` route where equipment #1 and booking #1 belong to different studios. It failed with 403 before the fix. Full suite: 725 passed (3678 assertions).
- Deployed in `765fcbd4`. Checked on production with `curl` as the owner:
  - The camera was assigned to booking 76 again (row 11) and then removed: "Equipment removed successfully." The booking's equipment list is now empty.
  - A PUT sent as POST with `_method=PUT` and `status=zzz` got "Invalid booking status selected.", so the server now reads the body.
  - A click-through of the status modal after the deploy is still to do (the browser tool disconnected).

Other notes from production:

- `APP_DEBUG` is still on. Error responses include `exception` and server paths.
- If the owner clicks Update on Edit Studio before the barangay list loads, the save fails with "The barangay field is required." This predates T02.

Test data left on production: bookings BK-6ADCE2471C46E (in progress, paid, print order #9, reschedule history), BK-6ADCE71B5F827 and BK-6ADCE75E53395 (cancelled), gallery 13 on FS-BKS-00020 (pending approval), catalog item "Camera EOS R6", and editing prices on owner package 31 and freelancer package 3.

## Open

- **Service videos.** Services still accept and show videos (`partials/service-media.blade.php`). The panel's note may cover them too. Ask before hiding them.
- **Gallery name for bookings with no event name.** The freelancer upload names the gallery " Gallery" (leading space). This is the same family as the "Event null" label from Phase 9 and is cosmetic.
- **Safety cap length.** `DELIVERY_DAYS = 30` is a default, not a client rule. Change the constant if the client wants another limit.

## Verification (2026-10-24)

- Full suite: `composer test` gave 725 passed (3676 assertions).
- Node: `node --test tests/Integration/*.test.cjs` gave 21 passed.
- Build: `npm run build` succeeded.

## Files changed

- `app/Http/Controllers/StudioOwner/SubscriptionController.php`
- `app/Http/Controllers/StudioOwner/StudioMemberController.php`
- `app/Http/Requests/StudioOwner/StudioMemberInviteRequest.php`
- `app/Services/FreelancerBookingAccess.php`
- `resources/views/partials/studio-picker.blade.php` (new)
- `resources/views/partials/package-extras.blade.php`
- `resources/views/owner/view-subscription-plans.blade.php`, `invite-members.blade.php`, `view-members.blade.php`, `create-packages.blade.php`, `view-bookings.blade.php`
- `resources/views/freelancer/view-studio-assignments.blade.php`
- `resources/views/client/booking-forms.blade.php`, `booking-details.blade.php`
- Tests:
  - `tests/Feature/Owner/MemberInviteStudioTest.php` (new)
  - `tests/Feature/Subscription/SubscriptionTrialHistoryTest.php`
  - `tests/Feature/Owner/PackageCreateAccessTest.php`
  - `tests/Feature/Booking/FreelancerAccessTest.php`
  - `tests/Feature/Chat/ChatTest.php`
  - `tests/Integration/PackageExtras.test.cjs`

---

[← Previous: Phase 9](./phase-9.md) | [Next: Phase 11 →](./phase-11.md)
