# Capstone B Roadmap — Progress (Phase 1 & Phase 2)

> Tracks completion of [`../02-PLANNING/CAPSTONE B IMPLEMENTATION ROADMAP.md`](../02-PLANNING/CAPSTONE%20B%20IMPLEMENTATION%20ROADMAP.md) Phase 1 ("Stabilize") and Phase 2 ("Complete"), per `prompt/tasks/01.md` (project repo). Generated 2026-07-13 on branch `capstone-b/phase-1-2`.

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

## Verification performed

- `php artisan test` (32/32) passes after every item.
- Every touched PHP file passed `php -l`.
- All Blade views compiled cleanly via `php artisan view:cache` after every item.
- All new migrations applied cleanly against the real dev MySQL database (`platinum`).
- 1.5's webhook routes and signature verification, and 2.4's calendar UI, were additionally verified against the running dev server via `curl` with a real session (the sandboxed browser preview resolves `localhost` to a different environment than the shell, so browser-based click-through wasn't usable this session — verified via direct HTTP requests instead).
- Full manual click-through in a real browser (all 13 features, all portals) was **not** performed and is recommended before merging.

## Known follow-ups (not fixed in this pass)

1. **Pre-existing migration bug** — `2026_01_31_162439_add_category_id_to_tbl_freelancer_services.php` unconditionally re-adds a column the table-creation migration already includes, breaking a from-scratch `migrate:fresh` on SQLite. Unrelated to this work; flagged separately.
2. **Pre-existing duplicate JS handler** — `resources/views/owner/view-services.blade.php` binds `.btn-edit-service` click twice, firing duplicate AJAX calls. Flagged separately.
3. **`.env` is tracked in git** with live-looking Stripe test keys committed to history — pre-existing, unrelated to this work, worth rotating/untracking.
4. **2.6** — the roadmap's "Past Work" tab (completed-booking galleries shown alongside Portfolio) was not built.
5. **PayMongo redirect callback** (`processSuccessfulPayment`) still doesn't create a `SystemRevenueModel` record (pre-existing gap, separate from the webhook path fixed in 1.5).
