# Capstone B Roadmap — Progress (Phase 1, Phase 2 & Phase 3)

> Tracks completion of [`../02-PLANNING/CAPSTONE B IMPLEMENTATION ROADMAP.md`](../02-PLANNING/CAPSTONE%20B%20IMPLEMENTATION%20ROADMAP.md) Phase 1 ("Stabilize"), Phase 2 ("Complete"), and Phase 3 ("Core New Features"), per `prompt/tasks/01.md` and `prompt/tasks/02.md` (project repo). Phase 1/2 generated 2026-07-13; Phase 3 generated 2026-07-14, both on branch `capstone-b/phase-1-2`.

Legend: ✅ Done this pass | ✔️ Already fixed prior to this pass (verified, no change needed) | ⚠️ Partial — see note

---

## Phase 1 — Stabilize

| # | Item | Status | Notes |
|---|---|---|---|
| 1.1 | Barangay JSON encoding bug | ✔️ | `StudioController::store()` already guards `json_last_error()` and null/array cases; `LocationModel::getBarangaysAttribute()` also guards. No code change. |
| 1.2 | Online gallery images not showing | ✅ | `getThumbnailAttribute()` on both gallery models now checks `Storage::disk('public')->exists()` before returning a path; returns `null` (placeholder already existed in the view) if the file is missing. |
| 1.3 | UI misalignment & text overflow | ✅ | Added `text-truncate` to studio/freelancer name fields in the client dashboard and booking-history views (previously untruncated). Icons already served locally, no CDN issue. This was a targeted fix, not an exhaustive visual audit — a live browser pass across every page is recommended before final sign-off. |
| 1.4 | Owner profile photo required | ✅ | `owner_profile_photo` (→ `UserModel.profile_photo`) was **completely absent** from `StudiosModel::rules()` — accepted as fully optional with no type/size validation, and the form labeled it "(optional)". Added `'owner_profile_photo' => 'required|image|mimes:jpg,jpeg,png|max:3072'` to the rules, removed the "(optional)" copy, and added the required asterisk/attribute to the form field. |
| 1.5 | Payment verification / webhook handlers | ✅ | Registered `POST /webhook/paymongo` and `/webhook/stripe` (CSRF-exempt via `bootstrap/app.php`). Added PayMongo signature verification (previously none), `payment_intent.payment_failed` / `payment.failed` handling with a new `notifyPaymentFailed()`, and `createRevenueRecord()` parity for PayMongo. Also fixed `PaymentModel::$fillable` missing `paymongo_source_id`/`paymongo_payment_id`, which silently broke the PayMongo webhook's payment lookup. Added `config/services.php` `paymongo` block (was completely missing). |
| 1.6 | Procurement escalation scheduler | ✔️ | `routes/console.php` already had `Schedule::command('procurement:escalate-overdue')->hourly()`. No code change. |

---

## Phase 2 — Complete

| # | Item | Status | Notes |
|---|---|---|---|
| 2.1 | Starting price on services | ✅ | `starting_from` decimal added to `tbl_services`/`tbl_freelancer_services`, wired through models, validation, forms, and client "Services Offered" display. Also created the missing `freelancer/edit-services.blade.php` view (the route existed but 500'd — no view file). |
| 2.2 | Owner income report | ✅ | New `income_by_service` table on the owner dashboard (Service Category / Bookings / Total Revenue / Platform Fee / Net Income), sourced from `SystemRevenueModel`. Renders automatically via the existing generic table partial and is included in the existing CSV export. |
| 2.3 | Photographer assignment deadline | ✅ | `response_deadline` (24h from assignment) added; `isPastDeadline()` helper; deadline/overdue shown in both the photographer's assignment list and the owner's booking view. |
| 2.4 | Visual booking calendar | ✅ | Wired the client booking form's pre-existing-but-empty calendar modal to the already-working `getCalendarAvailability` endpoint: color-coded month grid (green/red/grey), prev/next navigation, click-to-select. |
| 2.5 | Studio-side booking cancellation | ✅ | The owner cancel UI (status dropdown, reason textarea, confirm flow) already existed; `cancellation_reason` was validated then discarded by the backend. Now persists `cancellation_reason` + `cancelled_by`, notifies the client, and flags `payment_status = 'refund_pending'` when a paid booking is cancelled (manual refund review — auto-refund is Phase 6). |
| 2.6 | Portfolio gallery (independent of bookings) | ✅ | `booking_id`/`client_id` made nullable (raw SQL FK drop/re-add — doctrine/dbal isn't installed), `gallery_type` enum added. New owner/freelancer "Portfolio Gallery" pages to upload without a booking; client profile page shows a Portfolio section. A "Past Work" tab for completed-booking galleries (also mentioned in the roadmap) was **not** built — out of scope for this pass. |
| 2.7 | Pending booking auto-expiry | ✅ | `expires_at` (48h from creation) added; new `bookings:expire-pending` command (mirrors the existing procurement-escalation pattern) registered hourly; cancels expired bookings and notifies both parties; client sees a countdown. |
| 2.8 | Expand notification coverage | ✅ | All 9 roadmap-listed methods now exist on `Notifiable`. `notifyBookingCompleted`, `notifyPhotographerAssigned`, `notifyReviewReceived` are wired to their existing call points. `notifyGalleryPublished`, `notifyAssignmentDeadlineWarning`, `notifySubscriptionExpiring` are defined but intentionally unwired — their triggers (gallery draft/publish step, deadline-warning scheduler, subscription-expiry scheduler) are Phase 3.2 / Phase 6 features not yet built. |
| 2.9 | Connect budget to booking payments | ✅ | `tbl_client_budget` had **no** `spent_amount` column at all (worse than the roadmap assumed). Added it; a new `updateClientBudgetSpending()` helper increments it at all four payment-confirmation sites (Stripe + PayMongo, redirect + webhook) and fires `notifyBudgetExceeded()` at the cap. Budget detail modal shows "Spent So Far". |
| 2.10 | Review moderation + rating aggregate | ✅ | `status` enum added to both rating tables; `avg_rating`/`total_reviews` added to `tbl_studios`/`tbl_freelancers`, kept in sync by `updateAggregate()` on every new review. New `Admin\ReviewModerationController` (list/flag/remove/republish). All client-facing rating reads now use the stored aggregate instead of a live `avg()` subquery, and only show published reviews. |

---

## Verification performed (Phase 1 & 2)

- `php artisan test` (32/32) passes after every item.
- Every touched PHP file passed `php -l`.
- All Blade views compiled cleanly via `php artisan view:cache` after every item.
- All new migrations applied cleanly against the real dev MySQL database (`platinum`).
- 1.5's webhook routes and signature verification, and 2.4's calendar UI, were additionally verified against the running dev server via `curl` with a real session (the sandboxed browser preview resolves `localhost` to a different environment than the shell, so browser-based click-through wasn't usable this session — verified via direct HTTP requests instead).
- Full manual click-through in a real browser (all 13 features, all portals) was **not** performed and is recommended before merging.

## Known follow-ups (not fixed in this pass)

1. **Pre-existing migration bug** — `2026_01_31_162439_add_category_id_to_tbl_freelancer_services.php` unconditionally re-adds a column the table-creation migration already includes, breaking a from-scratch `migrate:fresh` on SQLite. Unrelated to this work; flagged separately. **Still not fixed as of Phase 3** — see verification notes below, this blocked a from-scratch SQLite `migrate:fresh` again this pass.
2. **Pre-existing duplicate JS handler** — `resources/views/owner/view-services.blade.php` binds `.btn-edit-service` click twice, firing duplicate AJAX calls. Flagged separately.
3. **`.env` is tracked in git** with live-looking Stripe test keys committed to history — pre-existing, unrelated to this work, worth rotating/untracking.
4. **2.6** — the roadmap's "Past Work" tab (completed-booking galleries shown alongside Portfolio) was not built.
5. **PayMongo redirect callback** (`processSuccessfulPayment`) still doesn't create a `SystemRevenueModel` record (pre-existing gap, separate from the webhook path fixed in 1.5).

---

## Phase 3 — Core New Features

| # | Item | Status | Notes |
|---|---|---|---|
| 3.1 | Per-package/service selling image or video | ✅ | `cover_images` JSON added to `tbl_packages` and `tbl_freelancer_packages` (max 5, validated as images). Owner and freelancer package `store()` now upload cover images; **added the missing `edit()`/`update()` actions and edit Blade views for both roles** (packages previously had no edit flow at all — a pre-existing gap, needed here since cover images must be replaceable). Client sees thumbnails on the two package-selection surfaces: the AJAX package cards in `booking-forms.blade.php` and the server-rendered cards in `booking-details.blade.php`. No dedicated "studio public profile" page exists in this codebase, so profile-level thumbnails were out of scope — confined to the literal "when choosing a package" requirement. |
| 3.2 | Gallery draft/publish + QA review step | ✅ | `gallery_status` enum (`draft`/`published`, default `draft`) added to both online-gallery tables. Owner and studio-photographer uploads now create galleries in `draft`; owner gets a new "Publish to Client" action (`owner.online-gallery.publish`) that flips the gallery to `published` and wires up the previously-unused `notifyGalleryPublished()`. **Freelancer galleries auto-publish** (freelancer is their own photographer — no separate QA relationship to gate, matching how the roadmap treats freelancers elsewhere). `Client\OnlineGalleryController` now requires `gallery_status = published` at all 4 read sites. |
| 3.3 | Post-completion revision request window | ✅ | `completed_at`, `revision_requested_at`, `revision_deadline` added to `tbl_bookings`. Completing a booking (both `StudioOwner\BookingController::completeBooking()` and `Freelancer\BookingController::updateStatus()`) now sets `completed_at` and a 7-day `revision_deadline`. **Side effect:** this also fixes a pre-existing dead-code bug — `Freelancer\BookingController` already tried to set `completed_at` but the column wasn't in `BookingModel::$fillable`, so it was silently dropped; it now persists. Client gets a "Request Revision" button (`client.booking.request-revision`) within the window; submitting reopens the booking's gallery (`gallery_status` back to `draft`, reusing 3.2's toggle) and notifies the owner/photographer via a new `notifyRevisionRequested()`. Added a re-upload gate to both owner/photographer gallery upload endpoints so a `published` gallery can't be silently overwritten outside a revision request (this hole existed unconditionally before 3.2/3.3). |
| 3.4 | Free trial flag on subscription plans | ✅ | `trial_days` (default 0) added to `tbl_subscription_plans`; `trial_ends_at` added to `tbl_studio_plans`. Admin plan create form and validation accept `trial_days`; the plan list shows an "X-day trial" badge. `StudioOwner\SubscriptionController::subscribe()` now activates trial plans immediately (`amount_paid = 0`, `status = active`, `trial_ends_at` set) and skips the Stripe checkout session entirely; non-trial plans are unaffected. New daily command `subscriptions:notify-trial-ending` wires up a new `notifyTrialEnding()` notification (7-day window, de-duplicated per day) — deliberately kept separate from the existing-but-still-unwired `notifySubscriptionExpiring()`, since "trial ending" (first real charge) and "subscription expiring" (renewal lapse) are different events. Scope was deliberately capped at what the roadmap's "Done when" asks for: no auto-charge-on-trial-end or auto-downgrade logic was built (would need Stripe payment-method-on-file support that doesn't exist yet). |

### Verification performed (Phase 3)

- Every new/touched PHP file passed `php -l`.
- `php artisan view:cache` compiled all Blade views (new and edited) with no errors.
- `php artisan test` (32/32) still passes.
- **Full `migrate:fresh` against real dev MySQL (`platinum`) could not be run this pass** — the local XAMPP MySQL/MariaDB data directory has a pre-existing Aria storage-engine recovery failure ("Cannot find checkpoint record", "Aria recovery failed") unrelated to this work, and the server would not start. Not repaired automatically (would require `aria_chk -r` against the user's real database — a destructive-adjacent operation outside this pass's scope).
- **Full `migrate:fresh` from scratch on SQLite also could not be run** — blocked by the pre-existing, previously-flagged bug in `2026_01_31_162439_add_category_id_to_tbl_freelancer_services.php` (item 1 above), which fails before reaching any Phase 3 migration.
- As a substitute, the 4 new migrations were verified in isolation: a disposable script booted an in-memory SQLite connection, created stub tables containing only the anchor columns each migration's `->after()` references, ran each migration's `up()`, confirmed the expected columns landed (`cover_images`, `gallery_status`, `completed_at`/`revision_requested_at`/`revision_deadline`, `trial_days`/`trial_ends_at`), then ran every `down()` in reverse to confirm clean rollback. All 4 passed. The script only ever touched a throwaway in-memory database and was deleted afterward — no project files or the real database were modified.
- Manual click-through in a real browser (gallery publish flow, revision request, package cover image upload/edit, trial subscribe flow) was **not** performed this pass and is recommended before merging, same caveat as Phase 1/2.

### Known follow-ups (Phase 3)

1. **MySQL/XAMPP data corruption** (see above) blocks any real-DB migration test until the local server is repaired or reinitialized — this is an environment issue, not a code issue.
2. **Admin subscription plan edit page is missing entirely** — `Admin\SubscriptionController::edit()` renders `admin.edit-subscription-plans`, but that Blade view does not exist anywhere in the codebase (pre-existing gap, not introduced this pass). `trial_days` was still added to the `update()` validation/persistence path so it will work once that view is built; discovered while implementing 3.4's "admin can set trial days when creating/editing a plan."
3. **3.1's package edit views** duplicate a large amount of markup/JS from the create forms (inclusions repeater, location toggles, multiple-locations logic) since no shared partial existed to extract from — a follow-up could factor the create/edit forms into a shared Blade partial + JS module, but that refactor was out of scope for this pass.
