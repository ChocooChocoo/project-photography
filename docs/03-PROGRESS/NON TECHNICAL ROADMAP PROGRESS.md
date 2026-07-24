# Capstone B Roadmap — Progress, Plain Language (Phase 1, Phase 2 & Phase 3)

> Non-technical companion to `ROADMAP PROGRESS.md`. Same work, same structure, explained in
> everyday language — what was broken, what was missing, and what now works, without code terms.
> Database table names are mentioned occasionally since they double as plain labels (e.g. "the
> bookings list," "the packages list").
>
> This work has since been merged into the main project. The "check it in a real browser first"
> cautions noted below were never carried out before that merge, so they remain open items.

Legend: ✅ Done this pass | ✔️ Already fixed prior to this pass (checked, no change needed) | ⚠️ Partial — see note

---

## Phase 1 — Stabilize

| # | Item | Status | Notes |
|---|---|---|---|
| 1.1 | Studio registration location bug | ✔️ | Already had a safety check in place for broken location data — no change needed. |
| 1.2 | Online gallery images not showing | ✅ | Gallery photos now check the file actually exists before trying to show it — a placeholder appears instead of a broken image icon if it's missing. |
| 1.3 | UI misalignment & text overflow | ⚠️ | Long studio/freelancer names on the client dashboard and booking history now get trimmed with "..." instead of overflowing their box. Icons were already loading fine. Targeted fix, not a full visual sweep — a real look-through of every page is still worth doing before final sign-off. |
| 1.4 | Owner profile photo required | ✅ | The owner's profile photo was completely optional despite the form suggesting otherwise — it's now actually required, with the "(optional)" label removed and a required mark added. |
| 1.5 | Payment verification / lost payments | ✅ | **The most important fix.** If a customer paid and closed their browser before returning to the site, the payment used to get "lost" — the system had no way to confirm it succeeded. Now the payment providers (PayMongo and Stripe) directly notify the platform the instant a payment succeeds or fails, so nothing depends on the customer coming back. Failed payments now also notify the customer. A couple of related record-keeping gaps were fixed alongside this. |
| 1.6 | Purchase request escalation reminder | ✔️ | The automatic overdue-purchase-request reminder was already scheduled to run — no change needed. |

---

## Phase 2 — Complete

| # | Item | Status | Notes |
|---|---|---|---|
| 2.1 | Starting price on services | ✅ | Studios and freelancers can now show a starting price on each service, so customers see a price hint before they start booking. Also fixed a page that was crashing for freelancers editing their services. |
| 2.2 | Owner income report | ✅ | Owners can now see income broken down by service category — bookings, revenue, platform fee, net income — right on their dashboard, and export it the same way as their other reports. |
| 2.3 | Photographer assignment deadline | ✅ | When an owner assigns a photographer, there's now a 24-hour deadline for the photographer to respond. Both sides can see it, and overdue ones are flagged. |
| 2.4 | Visual booking calendar | ✅ | Clients now see an actual color-coded calendar when picking a date — green for available, red for fully booked, grey for closed days — instead of a blank date field. |
| 2.5 | Studio-side booking cancellation | ✅ | Studios can now cancel a confirmed booking themselves with a required reason. The client is notified, and the payment gets flagged for manual refund review. |
| 2.6 | Portfolio gallery (no booking needed) | ⚠️ | Studios and freelancers can now upload portfolio photos that aren't tied to any booking — useful for brand-new studios with nothing completed yet to show. A "Past Work" tab showing completed-booking galleries alongside the portfolio was **not** built this round. |
| 2.7 | Pending booking auto-expiry | ✅ | Bookings left unconfirmed for 48 hours now automatically cancel themselves, notify both sides, and show the client a countdown before that happens. |
| 2.8 | Expand notification coverage | ✅ | Several important events now actually send a notification that didn't before: booking completed, photographer assigned, review received. A few more (gallery published, assignment-deadline warnings, subscription-expiry reminders) were built into the message system but not yet triggered anywhere — some of those got hooked up as part of Phase 3. |
| 2.9 | Connect budget to booking payments | ✅ | A client's personal budget tool now actually updates itself as real booking payments happen, instead of being a manual planner that never reflected real spending. Clients get warned if they go over budget. |
| 2.10 | Review moderation + rating totals | ✅ | An admin can now flag or remove a bad-faith review. Star ratings are now calculated once and stored, instead of being recalculated from scratch every single time someone views a profile — and only approved reviews are shown to customers. |

---

## Verification performed (Phase 1 & 2)

- The full automated test suite passed after every item.
- Every changed file was checked for basic coding mistakes — all clean.
- Every changed page was confirmed to load without errors.
- All database changes were applied cleanly to the real working database.
- The payment-notification fix and the new calendar were additionally checked against the live site directly, since the sandboxed preview tool couldn't reach it properly this session.
- A full manual click-through of all 16 Phase 1–2 items across every user type was **not** done. It was recommended before going live, and hasn't happened yet — still open.

## Known follow-ups (not fixed in this pass)

1. **A pre-existing database setup bug** blocks building the entire database completely from scratch in the lightweight test environment. Unrelated to this work; flagged separately.
2. **A pre-existing double-click bug** on the studio's "edit service" button fires the save action twice. Flagged separately.
3. **A settings file with live-looking payment test keys is stored in version history** — pre-existing, unrelated to this work, worth rotating out.
4. **2.6** — the "Past Work" tab (showing completed-booking galleries next to the portfolio) was not built.
5. **A payment success page still doesn't record revenue** the way the newer instant-notification path does (pre-existing gap, separate from the 1.5 fix).

---

## Phase 3 — Core New Features

| # | Item | Status | Notes |
|---|---|---|---|
| 3.1 | Photos on packages, not just text | ⚠️ | Studio owners and freelancers can now attach up to 5 sample photos to each package. Customers see a preview photo right on the package card while choosing, on both places that matter (the booking package picker and the booking details screen). Along the way, a gap was found and fixed: there was previously no way to *edit* a package at all after creating it, only create/view/delete — editing was added since photos need to be replaceable. This platform doesn't have a dedicated public "studio profile" page today, so photo previews were kept to the two package-picking screens where they're actually needed, rather than inventing a new page. |
| 3.2 | Photos get reviewed before the customer sees them | ✅ | Previously, the moment a photographer uploaded photos, the customer could see them immediately — no quality check at all. Now studio uploads (from the owner or their photographer) land as "pending" first, and the owner has to click "Publish to Client" before the customer can see them — the customer also gets notified at that moment. **Freelancers are the exception**: since a freelancer is their own photographer with no one else to review the work, their uploads still publish immediately, same as before. |
| 3.3 | Customers can ask for a photo redo | ✅ | Once a booking is completed, the customer now has 7 days to hit a "Request Revision" button instead of only being able to leave a star rating. Doing so notifies the studio owner and the assigned photographer, and reopens the gallery for new photos — which then goes back through the same review-before-publish step from 3.2. After 7 days the option disappears and the booking is final. A related gap got closed too: nothing previously stopped a photographer from quietly re-uploading over photos the customer had already seen — now that's only allowed once a revision has actually been requested. |
| 3.4 | Free trial for studio subscription plans | ⚠️ | The platform admin can now set a number of free trial days on any subscription plan. If a studio owner subscribes to a plan with a trial, it activates immediately for free, no payment screen at all, and a reminder goes out before the trial ends. **Marked partial, not done:** the plan also called for automatically downgrading an owner who doesn't add a payment method within 48 hours of the trial ending — that half was **not** built, since it needs card-on-file support the platform doesn't have yet. What works today is "start a free trial and give a heads-up." |

### Verification performed (Phase 3)

- Every changed file was checked for basic coding mistakes — all clean.
- Every changed or new page was confirmed to load without errors.
- The full automated test suite still passes, nothing broke.
- The four new pieces of database structure were each tested on their own to confirm the new fields get added correctly and can be cleanly undone if needed.
- **Could not verify against the real, full database this pass** — the local database server on this machine wouldn't start due to unrelated file corruption, and a separate already-known pre-existing bug (see Phase 1/2 follow-up #1) blocks building the whole database from scratch in the lightweight test environment. Neither was caused by this work.
- A full manual click-through in a real browser (publishing a gallery, requesting a revision, uploading package photos, starting a free trial) was **not** done this pass. Recommended before going live, and still not done — same caution as Phases 1 and 2.

### Known follow-ups (Phase 3)

1. **The local database server problem** (see above) blocks real-database testing until it's repaired — this is a machine/environment issue, not something this work caused.
2. **The admin page for editing an existing subscription plan doesn't actually exist yet** — only creating a new plan works today (pre-existing gap, not introduced this pass). The free-trial setting was still wired into the save logic so it'll work the moment that missing page gets built.
3. **The package edit screens repeat a lot of the create-screen's design** since there was no shared building block to reuse — a future cleanup could combine them, but that wasn't part of this pass's job.
