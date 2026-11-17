# T-15: Booking, gallery, navigation, inventory and refund fixes

**Status:** ✅ Locally verified, 17 November 2026; production unverified.
**Source:** [tasks/15.md](../../ANALYSIS%20-%20PLATINUM%20STUDIO%20PLATFORM/MATERIAL/LEGACY%20DOCUMENTATION/tasks/15.md)

## Answers to the open questions (confirmed by the user, 17 November 2026)

| Question | Answer |
|---|---|
| Priority booking 1 day / 2 days / 3+ days | It is the provider's minimum advance-booking notice. The client cannot pick an event date earlier than today plus that many days. "3+" takes a custom count from 3 to 30. |
| Inventory capabilities | A view-only Inventory page for the Owner and Finance. It lists consumable stock and equipment assets recorded when procurement deliveries are received. |
| Downpayment on a fully paid client cancellation | It stays non-refundable. The owner refunds from ₱0 up to the amount paid minus the downpayment. |
| Hardcopy completion (asked during planning) | The owner can complete the booking once every accepted print order is ready, shipped, or delivered. Delivery tracking continues after completion. |

## What changed

| Area | Behaviour now | Main files |
|---|---|---|
| Assignment after completion | Completed assignments still fill the package's photographer slots, so finishing work never reopens the "Assign Photographer" button. Assigning, changing an assignment, or proposing a replacement on a completed or cancelled booking returns 422. | `StudioOwner/BookingController.php`, `BookingModel::OCCUPYING_ASSIGNMENT_STATUSES` |
| Photographer status text | When the booking is completed, the photographer sees "Booking Completed" instead of "Waiting for owner to finalize booking". The text in the report ("Waiting for final approval from owner") does not exist in the code; this was the matching message. | `studio-photographer/view-assigned-booking.blade.php` |
| Advance notice (priority) | A shared select (1 day, 2 days, 3+ days) replaces the free number field in studio create/edit, studio schedule setup, and freelancer setup. Availability check, booking store, and the month calendar enforce it. The form's date picker starts at the earliest allowed date. | `partials/advance-booking-select.blade.php`, `BookingModel::minimumNoticeDays()`, `Client/BookingController.php` |
| Slot counter | Two defects fixed. (1) Counting moved the shared range date forward after the first booking, so the second same-day booking was missed. (2) Bookings in progress (a photographer accepted) did not hold their slot or block an overlapping time. The label now says "N of M slots left". | `Client/BookingController::bookingCountsByDay`, `BookingModel::CAPACITY_STATUSES` |
| Hardcopy completion | See the answer above. A quote the client has not accepted still blocks completion. | `BookingModel::getDeliveryCompletionBlockReason`, `HardcopyOrderController.php`, `partials/hardcopy-orders.blade.php` |
| Gallery deletion lock | Deleting a gallery or one of its photos returns 422 once a studio booking is completed. A freelancer gallery locks once it is published (freelancer galleries only exist after completion). Delete buttons are hidden. | four portal `OnlineGalleryController`s, `BookingModel::galleryDeletionLocked()` |
| Per-photo revisions | The client ticks photos in the gallery and sends one request with a note. The gallery stays published. The provider sees the flagged photos and the note, and replaces each photo in place; the before-and-after original moves with it. When the last flagged photo is replaced, the client is notified. One request per booking, within the existing 7-day window. | migration `2026_11_17_100000_…`, `MyBookingsController::requestRevision`, `GalleryRevisionController.php`, `public/assets/js/gallery-revision.js` |
| Profile navigation | The freelancer account profile page used the admin layout, so the topbar profile button landed in the "Admin Panel". It now uses the freelancer layout. "Setup Profile" opens the setup form pre-filled for editing instead of redirecting to "View Profile". The freelancer Dashboard link points to the freelancer dashboard. | `freelancer/view-user-profile.blade.php`, `Freelancer/ProfileController.php`, `freelancer/setup-profile.blade.php` |
| ✓ / ✕ overlap | Gallery action buttons sit in a no-wrap flex row with a gap, in the owner and studio photographer tables. Booking action buttons in the freelancer modal have a gap. | `owner/` and `studio-photographer/view-online-gallery.blade.php`, `freelancer/view-bookings.blade.php` |
| Procurement inventory | Procurement → Inventory for the Owner and Finance: summary cards, consumable stock with a low-stock flag, and equipment assets, with search and a studio filter. | `procurement/inventory-index.blade.php`, `ProcurementWorkflowService::getInventorySummary()` |
| Refund rules | Business-side cancellations (studio, photographer, system) refund the full amount paid; neither the owner nor the Admin can change it. Client cancellations: a downpayment-only booking refunds nothing (unchanged); a fully paid booking refunds what the owner sets, capped at paid minus downpayment, and defaults to that cap. | `BookingModel::nonRefundableDownpayment()`, `maxClientRefund()`, `BookingCancellationRecoveryService.php`, `Admin/BookingRefundController.php`, `admin/booking-refunds.blade.php`, `owner/cancellation-recovery.blade.php` |

## Verification

- `php artisan test --compact`: 822 passed, 4280 assertions.
- `npm run build` succeeded.
- Local browser run (`php artisan serve`, seeded data): freelancer setup page pre-filled with "Update profile"; advance-notice select switches between 1, 2 and 3+; client booking form date minimum follows a 2-day notice; owner Inventory page; owner gallery manage modal hides delete on a completed booking; client selected one photo, the owner list showed "Revision Pending", the owner replaced the photo, and the badge returned to "Published".
- Not checked in a browser: the Admin refund queue (Admin login needs an emailed one-time code). Feature tests cover it.

## Known limits and follow-ups

- ~~A fully paid client cancellation keeps the booking at `refund_pending` after the refund is recorded.~~ Fixed in 14dfc515: the booking moves to `partially_refunded`. Older rows need `output/fix-completed-refund-status.sql`.
- The HR portal has no "Replace photo" button; HR users cannot manage revisions.
- Inventory is view-only. Stock is never reduced, asset status cannot change, and received equipment is not linked to the bookable equipment catalog. The panel's S12 request for inventory quantity on booking equipment remains open.
- Run `php artisan migrate` on production before deploying (one new migration).

## Follow-ups after testing (17 November 2026)

A local browser run of every testing-artifact section found these. All are fixed and pushed to `main`.

| Commit | Problem found | Fix |
|---|---|---|
| 14dfc515 | A finished partial refund looked the same as a pending one. | New booking payment status `partially_refunded`. `output/fix-completed-refund-status.sql` fixes older rows. |
| 68061f4f | Studio schedule setup showed the default notice, not the studio's saved advance notice. | The studio select carries the saved value and sets the 1/2/3+ choice when a studio is picked. |
| a9ceb9eb | Seeded studio subscriptions expired about 20 days after seeding. | The seeder starts them today. `output/fix-expired-seed-subscriptions.sql` renews existing seeded rows. |
| 45f24d26 | A partial refund was taken from the oldest payment first, so the downpayment payment was marked refunded and needed a reference. | Refunds are taken from the newest payment first. Only payments that return money need a provider reference; the Admin queue shows reference fields only for those. |
| (this commit) | The owner refund page was titled "Photographer Cancellation Recovery" for client cancellations, and the refund box started empty. On phones the chat button covered right-aligned table actions. | The page is titled "Booking refund" when the owner sets a refund. The box starts at the highest allowed refund. The chat button is 44 px and sits closer to the corner below 576 px. |

Local browser check of the hardcopy flow (booking 67, fixture data): the print order moved Pending, Preparing, Ready; completion was blocked while it was Preparing; Complete Booking then worked; Mark delivered still worked after completion.

Still open on production: run both SQL files above, and re-upload the studio logo whose stored file returns an HTML page.
