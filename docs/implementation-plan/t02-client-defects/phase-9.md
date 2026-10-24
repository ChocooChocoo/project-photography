[← Previous: Phase 8](./phase-8.md)

**Status:** ✅ Done. The testers still have to mark the outside acceptance sheet.

# Phase 9: Acceptance re-run and documentation

**Goal:** The full test suite passes, the five failing acceptance tests pass, and the project record shows T02 as evidence-backed current behavior.

## Dependencies

- Phases 1–8 complete

## Tasks

- [x] Run `composer test` and fix any failure (systematic debugging first). It passed on the first run: 721 passed (3661 assertions).
- [x] Re-run the acceptance checklist tests in the browser (see "Browser check" below): Full payment, Preparation and equipment, Printed photos: pickup and delivery, Finishing a booking, Full walkthrough: freelancer booking; then the untested "Photos, videos and extras on packages". All six pass.
  - The steps come from T02 and Phases 1–8, because the checklist itself lives outside this repo.
  - The re-run found one bug, now fixed: freelancer package forms dropped the photo editing prices (see "Bug found in the browser and fixed").
- [x] Record the external item: the acceptance-test sheet is outside this repo. Testers need edit access and a notes column for "Doesn't work" results. No app code. Recorded under "External items" below and in `09 - TASK TRACKER.md`.
- [x] Freeze the T02 prompt as `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/MATERIAL/LEGACY DOCUMENTATION/tasks/13.md`, an exact copy of `docs/tasks/T02.md`. The tracker ID `T-02` already belongs to frozen prompt `02.md`, so the next free number was used.
- [x] Add T02 to `09 - TASK TRACKER.md` as row `T-13`, with source, scope and status.
- [x] Update parts `P-04 BOOKING AND PAYMENT`, `P-05 PHOTOGRAPHER ASSIGNMENT AND CANCELLATION`, `P-06 GALLERIES AND REVIEWS`, `P-09 PROCUREMENT AND EQUIPMENT`, `P-11 NOTIFICATIONS` with the shipped behavior and tests. Each has a new "T02 changes" section.
- [x] Record the T01/T02 downpayment reversal as a decision in `02 - DOCUMENT FINDINGS.md`, "Decisions already made".

## Done when

- [x] `composer test` exits 0: 721 passed (3661 assertions), before and after the fix.
- [ ] All five previously failing acceptance tests are marked pass in the checklist. The checklist is outside this repo, so testers must mark it. The browser re-run below is the evidence that all five pass.
- [x] `09 - TASK TRACKER.md` lists T02 as `T-13`, with a status that matches this plan.

## Browser check (2026-10-24, Playwright, dev database, one maximized window per role)

Each role had its own browser window with a separate login. A window was opened only when the scenario needed that role. Payments went through Stripe's test checkout with the test card 4242 4242 4242 4242.

**1. Full payment (owner Yolanda + client Beatriz).**

- The owner turned off "Offer a downpayment option" for Quarry Road Studio in Edit Studio. `requires_downpayment` was saved as 0. Screenshot: `.playwright-mcp/p9-w1-owner-downpayment-off.png`.
- The client's booking form showed only "Full Payment: The full amount is collected upon booking." The summary showed "Payment Type: Full Payment, Total Amount: ₱7,900.00". Screenshot: `p9-w2-client-full-payment-summary.png`.
- Stripe charged ₱7,900.00 (`p9-w2-client-stripe.png`). The success page read "Payment Type: Full Payment, Payment Status: Fully Paid" (`p9-w2-client-paid.png`).
- Booking 64 `BK-6ADCA27549033` was stored as `full_payment`, `paid`, with a remaining balance of 0. The studio setting was switched back on afterwards.

**2. Preparation and equipment (owner + photographer Eduardo + client).**

- The owner's equipment dropdown listed the studio inventory ("Camera MARK VI / NIKON"), and the preferred brand read NIKON.
- After the photographer was assigned, the equipment showed "Waiting for the photographer to confirm". Screenshot: `p9-w1-owner-equipment-assigned.png`.
- The photographer clicked "Confirm equipment" and saw "You confirmed this equipment." Then they accepted the assignment. Screenshot: `p9-w3-photographer-equipment-confirmed.png`.
- The client's booking showed "Camera MARK VI (DSLR) Brand: NIKON. Photographer confirmed" and no longer showed "Awaiting confirmation". Screenshot: `p9-w2-client-equipment-confirmed.png`.

**3. Printed photos: pickup and delivery (owner + client).**

- The owner created a ₱500 framed-print quote and suggested a third-party courier. Screenshot: `p9-w1-owner-hardcopy-quote.png`.
- The client accepted it with Studio pickup and saw "You chose Studio pickup (the studio suggested Third-party courier). Your choice is final." Screenshot: `p9-w2-client-hardcopy-accepted.png`.
- The client then paid the ₱500 balance through Stripe.
- Before the event, the owner panel read "Delivery can start after the event ends on Nov 18, 2026 1:00 PM." and showed no delivery button (`p9-w1-owner-delivery-gate.png`). Direct requests for `preparing` and `delivered` both returned 422 with the same message.
- After the event date was moved into the past (test data), the owner marked the order preparing, ready and delivered. Pickup stayed the method. Screenshot: `p9-w1-owner-hardcopy-delivered.png`.

**4. Finishing a booking (photographer + client + owner).**

- The photographer marked on-site with an arrival photo, and the client confirmed the presence (`p9-w2-client-confirm-presence.png`). The photographer then marked the work in progress.
- "Mark as Completed" stayed locked until a gallery was uploaded.
- The photographer uploaded two images and clicked "Submit for approval". The owner's bell went from 14 to 15 and showed "Gallery Awaiting Approval: The gallery "Phase 9 wedding gallery" is waiting for your approval." Screenshot: `p9-w1-owner-gallery-bell.png`.
- The owner approved the gallery, the photographer published it, and the photographer marked the assignment completed.
- In the owner's "Update Booking Status" modal, choosing Completed returned HTTP 200 "Booking status updated successfully." with no CSRF error. Screenshot: `p9-w1-owner-status-modal.png`.
- The client's history shows the booking as COMPLETED / PAID ₱8,400.00, and the client's Online Gallery lists "Phase 9 wedding gallery". Screenshot: `p9-w2-client-completed-gallery.png`.
- The Employees page (`/owner/view/employee`) opened without a server error. Screenshot: `p9-w1-owner-employees.png`.

**5. Full walkthrough: freelancer booking (client + freelancer Lorena).**

- The form showed "Downpayment required: 30% downpayment now, and the remaining balance before the event."
- The summary read "₱4,200.00, Downpayment (30%): ₱1,260.00, Remaining Balance: ₱2,940.00" (`p9-w2-client-freelancer-summary.png`). Stripe charged ₱1,260.00, and the booking became "Partially Paid (Downpayment)" (`p9-w2-client-freelancer-paid.png`).
- The client asked to move the booking from Nov 25 to Nov 27 (`p9-w2-client-reschedule-sent.png`). The freelancer approved, and the panel read "Reschedule approved. The booking has been moved." (`p9-w3-freelancer-reschedule-approved.png`). The client received the unread notification "Reschedule approved".
- The client paid the ₱2,940.00 balance, and the booking became Fully Paid.
- After the date was moved into the past (test data), the freelancer marked the booking in progress and then completed. They uploaded a gallery, and the client sees it with the booking COMPLETED / PAID ₱4,200.00. Screenshot: `p9-w2-client-freelancer-completed.png`.

**6. Photos, videos and extras on packages (freelancer + client).**

- The freelancer set Color grading ₱400 and Brightness and contrast ₱250 on "Gathering Day Collection Half Day Session" (`p9-w3-freelancer-editing-prices.png`). The prices were kept after reload only after the fix below.
- The client's form listed "Color grading (+₱400.00)" and "Brightness and contrast (+₱250.00)" (`p9-w2-client-editing-options.png`).
- With both ticked, the summary read "Photo editing (Color grading, Brightness and contrast): ₱650.00, Downpayment (30%): ₱1,455.00, Remaining Balance: ₱3,395.00, Total Amount: ₱4,850.00". Screenshot: `p9-w2-client-editing-summary.png`.
- Phase 7 already checked the studio side of this scenario in the browser.

Test data left in the dev database:

- Booking 64 `BK-6ADCA27549033` (studio, completed) and booking 65 `BK-6ADCA83E85FF0` (freelancer, completed). Both event dates were moved to 23 Oct 2026 so they could be finished.
- Hardcopy order 3 is delivered.
- Freelancer package 3 has editing prices of ₱400 and ₱250.
- Quarry Road Studio's downpayment setting is back on.

## Bug found in the browser and fixed

Freelancer package forms silently dropped the photo editing prices. `freelancer/create-packages` and `freelancer/edit-packages` build their `FormData` one field at a time, and the shared helper `window.appendPackageExtras` in `resources/views/partials/package-extras.blade.php` did not add `editing_prices[...]`. The save returned success, but `editing_prices` stayed null. The owner forms were not affected, because they use `new FormData(form)`. Phase 7's tests posted the prices directly, so they never went through the form.

- Fix: the shared helper now sets every `editing_prices[...]` input on the `FormData`. This covers both freelancer forms, and the owner forms are unchanged because `set` replaces the value.
- Test: new `tests/Integration/PackageExtras.test.cjs`. It failed against the old helper and passes now.

## Observations (not changed; no approved requirement)

- **Freelancer completion has no delivery gate.** A freelancer booking can be marked completed once it is fully paid, with no gallery uploaded. Studio bookings must first have a published gallery and delivered printed photos. `BookingModel::requiresOnlineGalleryUpload()` is studio-only, and freelancers have no printed-photo routes. This predates T02 and T02 does not ask to change it, so it is an open question for the client.
- **A booking cannot be rescheduled once the photographer has accepted it.** Accepting moves a studio booking to In Progress, so the client panel says "This booking can no longer be rescheduled." This was already the behavior before T02.
- **"Event null" label.** The photographer's assignment modal shows "Event null" for bookings with no event name. This is a cosmetic issue from before T02.

## External items

- The acceptance-test sheet is outside this repo. Testers need edit access, because the page says "You can only view this page, so your answer wasn't saved". They also need a notes column for "Doesn't work" results. No app code is involved.

## Verification (2026-10-24)

- Full suite: `composer test` gave 721 passed (3661 assertions), before and after the fix.
- Node: `node --test tests/Integration/*.test.cjs` gave 21 passed (20 before, plus the new `PackageExtras` test).
- Build: `npm run build` succeeded.

## Files changed

- `resources/views/partials/package-extras.blade.php`
- `tests/Integration/PackageExtras.test.cjs` (new)
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/MATERIAL/LEGACY DOCUMENTATION/tasks/13.md` (new)
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/09 - TASK TRACKER.md`, `02 - DOCUMENT FINDINGS.md`
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/PARTS/P-04`, `P-05`, `P-06`, `P-09`, `P-11`

---

[← Previous: Phase 8](./phase-8.md)
