# Detailed Delivery History

> **In plain terms:** This is the complete historical progress record. The short tracker gives the current normalized status.
>
> **Status:** Historical detail retained in the new System Analysis format. For the current normalized status, see the [progress tracker](../05-progress/tracker.md).

---


# Capstone B Roadmap — Progress (Phases 1, 2, 3, 8, 9 & 10)

> Tracks completion of [`../02-PLANNING/CAPSTONE B IMPLEMENTATION ROADMAP.md`](../05-roadmap/roadmap.md) Phase 1 ("Stabilize"), Phase 2 ("Complete"), Phase 3 ("Core New Features"), Phase 8 ("AI Assistant"), Phase 9 ("Cancellation Contingency"), and Phase 10 ("Subscription Lifecycle"), per `prompt/tasks/01.md`, `02.md`, `04.md`, `07.md`, and `08.md` (project repo). Phase 1/2 generated 2026-07-13; Phase 3 generated 2026-07-14 — both were developed on branch `capstone-b/phase-1-2` (the branch name predates the Phase 3 work and was reused) and have since been **merged into `main`**. The pre-merge browser-verification caveats recorded below were therefore never cleared; they are now outstanding post-merge checks. Phase 8 was implemented 2026-07-25 directly on `main`.
>
> **Phases 4–7 have not been started.** Phase 8 was implemented out of order because it came from a separate task brief and shares no code with the booking, payment, or payroll flows those phases cover. **Phases 9 and 10 are documented only — no code exists for any of it.**

Legend: ✅ Done this pass | ✔️ Already fixed prior to this pass (verified, no change needed) | ⚠️ Partial — see note | 📋 Documented — analysis complete, nothing built

---

## Phase 1 — Stabilize

| # | Item | Status | Notes |
|---|---|---|---|
| 1.1 | Barangay JSON encoding bug | ✔️ | `StudioController::store()` already guards `json_last_error()` and null/array cases; `LocationModel::getBarangaysAttribute()` also guards. No code change. |
| 1.2 | Online gallery images not showing | ✅ | `getThumbnailAttribute()` on both gallery models now checks `Storage::disk('public')->exists()` before returning a path; returns `null` (placeholder already existed in the view) if the file is missing. *(Update 2026-07-25, `prompt/tasks/05.md`: that fix addressed the symptom, not the cause. The underlying defect was system-wide — the `public` disk wrote to `storage/app/public/` while the web server served `public/storage/`, with nothing bridging the two, so **every upload made after a deploy 404'd**. The `public` disk root now points at `public_path('storage')` and the `links` array is empty: no symlink, no `php artisan storage:link` in deployment. Full write-up in `prompt/output/05.md`.)* |
| 1.3 | UI misalignment & text overflow | ⚠️ | Added `text-truncate` to studio/freelancer name fields in the client dashboard and booking-history views (previously untruncated). Icons already served locally, no CDN issue. This was a targeted fix, not an exhaustive visual audit — a live browser pass across every page is recommended before final sign-off. |
| 1.4 | Owner profile photo required | ✅ | `owner_profile_photo` (→ `UserModel.profile_photo`) was **completely absent** from `StudiosModel::rules()` — accepted as fully optional with no type/size validation, and the form labeled it "(optional)". Added `'owner_profile_photo' => 'required|image|mimes:jpg,jpeg,png|max:3072'` to the rules, removed the "(optional)" copy, and added the required asterisk/attribute to the form field. |
| 1.5 | Payment verification / webhook handlers | ✅ | Registered `POST /webhook/paymongo` and `/webhook/stripe` (CSRF-exempt via `bootstrap/app.php`). **Deviation from the roadmap:** handlers live as `handleWebhook()`/`handleStripeWebhook()` on the existing `Client\BookingController` (reusing its payment-confirmation helpers), not in the separate `app/Http/Controllers/Webhook/` classes the roadmap specified — that directory does not exist. Added PayMongo signature verification (previously none), `payment_intent.payment_failed` / `payment.failed` handling with a new `notifyPaymentFailed()`, and `createRevenueRecord()` parity for PayMongo. Also fixed `PaymentModel::$fillable` missing `paymongo_source_id`/`paymongo_payment_id`, which silently broke the PayMongo webhook's payment lookup. Added `config/services.php` `paymongo` block (was completely missing). |
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
| 2.6 | Portfolio gallery (independent of bookings) | ⚠️ | `booking_id`/`client_id` made nullable (raw SQL FK drop/re-add — doctrine/dbal isn't installed), `gallery_type` enum added. New owner/freelancer "Portfolio Gallery" pages to upload without a booking; client profile page shows a Portfolio section. A "Past Work" tab for completed-booking galleries (also mentioned in the roadmap) was **not** built — out of scope for this pass. |
| 2.7 | Pending booking auto-expiry | ✅ | `expires_at` (48h from creation) added; new `bookings:expire-pending` command (mirrors the existing procurement-escalation pattern) registered hourly; cancels expired bookings and notifies both parties; client sees a countdown. |
| 2.8 | Expand notification coverage | ✅ | All 7 methods listed in the roadmap's 2.8 table now exist on `Notifiable` (plus `notifyBudgetExceeded`, added under 2.9). `notifyBookingCompleted`, `notifyPhotographerAssigned`, `notifyReviewReceived` are wired to their existing call points. `notifyGalleryPublished`, `notifyAssignmentDeadlineWarning`, `notifySubscriptionExpiring` are defined but intentionally unwired — their triggers (gallery draft/publish step, deadline-warning scheduler, subscription-expiry scheduler) are Phase 3.2 / Phase 6 features not yet built. *(Update: `notifyGalleryPublished` was subsequently wired up by Phase 3.2 — see below. The other two remain unwired.)* |
| 2.9 | Connect budget to booking payments | ✅ | `tbl_client_budget` had **no** `spent_amount` column at all (worse than the roadmap assumed). Added it; a new `updateClientBudgetSpending()` helper increments it at all four payment-confirmation sites (Stripe + PayMongo, redirect + webhook) and fires `notifyBudgetExceeded()` at the cap. Budget detail modal shows "Spent So Far". |
| 2.10 | Review moderation + rating aggregate | ✅ | `status` enum added to both rating tables; `avg_rating`/`total_reviews` added to `tbl_studios`/`tbl_freelancers`, kept in sync by `updateAggregate()` on every new review. New `Admin\ReviewModerationController` (list/flag/remove/republish). All client-facing rating reads now use the stored aggregate instead of a live `avg()` subquery, and only show published reviews. |

---

## Verification performed (Phase 1 & 2)

- `php artisan test` (32/32) passes after every item.
- Every touched PHP file passed `php -l`.
- All Blade views compiled cleanly via `php artisan view:cache` after every item.
- All new migrations applied cleanly against the real dev MySQL database (`platinum`).
- 1.5's webhook routes and signature verification, and 2.4's calendar UI, were additionally verified against the running dev server via `curl` with a real session (the sandboxed browser preview resolves `localhost` to a different environment than the shell, so browser-based click-through wasn't usable this session — verified via direct HTTP requests instead).
- Full manual click-through in a real browser (all 16 Phase 1–2 items across all portals; 14 of them involved code changes) was **not** performed. It was recommended before merging; the work has since merged to `main` without it, so this remains outstanding.

## Known follow-ups (not fixed in this pass)

1. **Pre-existing migration bug** — `2026_01_31_162439_add_category_id_to_tbl_freelancer_services.php` unconditionally re-adds a column the table-creation migration already includes, breaking a from-scratch `migrate:fresh` on SQLite. Unrelated to this work; flagged separately. **Still not fixed as of Phase 3** — see verification notes below, this blocked a from-scratch SQLite `migrate:fresh` again this pass.
2. **Pre-existing duplicate JS handler** — `resources/views/owner/view-services.blade.php` binds `.btn-edit-service` click twice, firing duplicate AJAX calls. Flagged separately.
3. **`.env` is tracked in git** with live-looking Stripe test keys committed to history — pre-existing, unrelated to this work, worth rotating/untracking. **Still open, and now more serious:** a live Groq API key was added to the same file during Phase 8. See Phase 8 follow-up #1.
4. **2.6** — the roadmap's "Past Work" tab (completed-booking galleries shown alongside Portfolio) was not built.
5. **PayMongo redirect callback** (`processSuccessfulPayment`) still doesn't create a `SystemRevenueModel` record (pre-existing gap, separate from the webhook path fixed in 1.5).

---

## Phase 3 — Core New Features

| # | Item | Status | Notes |
|---|---|---|---|
| 3.1 | Per-package/service selling image or video | ⚠️ | `cover_images` JSON added to `tbl_packages` and `tbl_freelancer_packages` (max 5, validated as images). Owner and freelancer package `store()` now upload cover images; **added the missing `edit()`/`update()` actions and edit Blade views for both roles** (packages previously had no edit flow at all — a pre-existing gap, needed here since cover images must be replaceable). Client sees thumbnails on the two package-selection surfaces: the AJAX package cards in `booking-forms.blade.php` and the server-rendered cards in `booking-details.blade.php`. No dedicated "studio public profile" page exists in this codebase, so profile-level thumbnails were out of scope — confined to the literal "when choosing a package" requirement. |
| 3.2 | Gallery draft/publish + QA review step | ✅ | `gallery_status` enum (`draft`/`published`, default `draft`) added to both online-gallery tables. Owner and studio-photographer uploads now create galleries in `draft`; owner gets a new "Publish to Client" action (`owner.online-gallery.publish`) that flips the gallery to `published` and wires up the previously-unused `notifyGalleryPublished()`. **Freelancer galleries auto-publish** (freelancer is their own photographer — no separate QA relationship to gate, matching how the roadmap treats freelancers elsewhere). `Client\OnlineGalleryController` now requires `gallery_status = published` at all 4 read sites. |
| 3.3 | Post-completion revision request window | ✅ | `completed_at`, `revision_requested_at`, `revision_deadline` added to `tbl_bookings`. Completing a booking (both `StudioOwner\BookingController::completeBooking()` and `Freelancer\BookingController::updateStatus()`) now sets `completed_at` and a 7-day `revision_deadline`. **Side effect:** this also fixes a pre-existing dead-code bug — `Freelancer\BookingController` already tried to set `completed_at` but the column wasn't in `BookingModel::$fillable`, so it was silently dropped; it now persists. Client gets a "Request Revision" button (`client.booking.request-revision`) within the window; submitting reopens the booking's gallery (`gallery_status` back to `draft`, reusing 3.2's toggle) and notifies the owner/photographer via a new `notifyRevisionRequested()`. Added a re-upload gate to both owner/photographer gallery upload endpoints so a `published` gallery can't be silently overwritten outside a revision request (this hole existed unconditionally before 3.2/3.3). |
| 3.4 | Free trial flag on subscription plans | ⚠️ | `trial_days` (default 0) added to `tbl_subscription_plans`; `trial_ends_at` added to `tbl_studio_plans`. Admin plan create form and validation accept `trial_days`; the plan list shows an "X-day trial" badge. `StudioOwner\SubscriptionController::subscribe()` now activates trial plans immediately (`amount_paid = 0`, `status = active`, `trial_ends_at` set) and skips the Stripe checkout session entirely; non-trial plans are unaffected. New daily command `subscriptions:notify-trial-ending` wires up a new `notifyTrialEnding()` notification (7-day window, de-duplicated per day) — deliberately kept separate from the existing-but-still-unwired `notifySubscriptionExpiring()`, since "trial ending" (first real charge) and "subscription expiring" (renewal lapse) are different events. **Marked ⚠️, not ✅:** the roadmap's "Done when" is met (admin sets trial days; owners get a trial before being charged), but roadmap **step 5** — notify the owner to add a payment method on `trial_ends_at`, and downgrade if none is added within 48h — was **not** built. It needs Stripe payment-method-on-file support that doesn't exist yet. The trial-ending notification covers the "notify" half; the auto-downgrade half is outstanding. |

### Verification performed (Phase 3)

- Every new/touched PHP file passed `php -l`.
- `php artisan view:cache` compiled all Blade views (new and edited) with no errors.
- `php artisan test` (32/32) still passes.
- **Full `migrate:fresh` against real dev MySQL (`platinum`) could not be run this pass** — the local XAMPP MySQL/MariaDB data directory has a pre-existing Aria storage-engine recovery failure ("Cannot find checkpoint record", "Aria recovery failed") unrelated to this work, and the server would not start. Not repaired automatically (would require `aria_chk -r` against the user's real database — a destructive-adjacent operation outside this pass's scope).
- **Full `migrate:fresh` from scratch on SQLite also could not be run** — blocked by the pre-existing, previously-flagged bug in `2026_01_31_162439_add_category_id_to_tbl_freelancer_services.php` (item 1 above), which fails before reaching any Phase 3 migration.
- As a substitute, the 4 new migrations were verified in isolation: a disposable script booted an in-memory SQLite connection, created stub tables containing only the anchor columns each migration's `->after()` references, ran each migration's `up()`, confirmed the expected columns landed (`cover_images`, `gallery_status`, `completed_at`/`revision_requested_at`/`revision_deadline`, `trial_days`/`trial_ends_at`), then ran every `down()` in reverse to confirm clean rollback. All 4 passed. The script only ever touched a throwaway in-memory database and was deleted afterward — no project files or the real database were modified.
- Manual click-through in a real browser (gallery publish flow, revision request, package cover image upload/edit, trial subscribe flow) was **not** performed this pass. Recommended before merging; the work has since merged to `main` without it, so this remains outstanding — same caveat as Phase 1/2.

### Known follow-ups (Phase 3)

1. **MySQL/XAMPP data corruption** (see above) blocks any real-DB migration test until the local server is repaired or reinitialized — this is an environment issue, not a code issue.
2. **Admin subscription plan edit page is missing entirely** — `Admin\SubscriptionController::edit()` renders `admin.edit-subscription-plans`, but that Blade view does not exist anywhere in the codebase (pre-existing gap, not introduced this pass). `trial_days` was still added to the `update()` validation/persistence path so it will work once that view is built; discovered while implementing 3.4's "admin can set trial days when creating/editing a plan."
3. **3.1's package edit views** duplicate a large amount of markup/JS from the create forms (inclusions repeater, location toggles, multiple-locations logic) since no shared partial existed to extract from — a follow-up could factor the create/edit forms into a shared Blade partial + JS module, but that refactor was out of scope for this pass.

---

## Phase 8 — AI Assistant

> Implemented 2026-07-25 on `main`, from `prompt/tasks/04.md`. Full task report: [`../../prompt/output/04.md`](../../../prompt/output/04.md). Feature reference: [`../04-REFERENCE/AI ASSISTANT INTEGRATION.md`](../08-references/ai-assistant-integration.md).

| # | Item | Status | Notes |
|---|---|---|---|
| 8.1 | Replace the fixed-response chatbot with Groq | ✅ | `ChatbotService` rewritten around `Ai\GroqClient` (`qwen/qwen3.6-27b`); keyword matcher, intent scoring, and the BotMan constructor deleted; `botman/botman` + `botman/driver-web` removed from `composer.json`. New `groq` block in `config/services.php`; variable names documented in `.env.example` with no values. **No migration needed** — all five `tbl_chatbot_*` tables kept as-is. **Discovery:** BotMan was instantiated in the constructor and then never referenced again, so the "BotMan chatbot" was in fact a hand-rolled DB keyword matcher the whole time. |
| 8.2 | Enforce the photography-only scope | ✅ | Behavior contract lives in the `ChatbotService::SECURITY_RULES` constant (not a DB column, so the owner portal cannot weaken it) and is re-sent as the system message on every request so history cannot displace it. Studio profile, active packages, and the owner's knowledge entries are injected per request inside `<untrusted_data source="...">` markers. Off-topic requests are answered with an `[OFFTOPIC]` sentinel that the output guard swaps for the domain fallback. |
| 8.3 | Input and output guardrails | ✅ | `Ai\ChatbotGuard`: sanitization (control/zero-width/bidi characters, whitespace, 600-char cap), the existing owner moderation retained as the first content check, then 24 injection/probe pattern groups. Output side: `<think>` stripping, off-topic sentinel, 6 instruction-echo markers, 12 leak patterns, and literal comparison against live secret values. Rejected output is replaced whole and never persisted or logged; the matched pattern is never reported back, so the filter cannot be mapped by probing. |
| 8.4 | Credential protection, failure handling, usage limits | ✅ | `Ai\GroqRateLimiter`: five cache windows (25 RPM / 900 RPD / 7,000 TPM / 180,000 TPD / 8 per-user-RPM), all under the provider's published limits since the counters are advisory. Estimate → reserve → reconcile against reported usage. Every failure mode (missing key, provider error, 429, timeout, unusable payload) returns the same neutral copy. Endpoints moved out of the client-only group to `/chatbot/*` in the top-level `auth` group with `throttle:30,1`; route names stayed `chatbot.*`, so no `route()` call broke. |
| 8.5 | Surfaces and documentation | ✅ | ~380 lines of inline modal + jQuery in `client/booking-details.blade.php` replaced by one `@include` of the new `partials/chatbot-widget.blade.php` (vanilla JS + `fetch`, no jQuery), also mounted in the owner and studio-photographer layouts. Owner portal relabelled: intents are now "Studio Knowledge" reference facts, with copy stating replies are AI-generated and the security rules are not editable. New `docs/AI ASSISTANT INTEGRATION.md`; `CLAUDE.md`, both 01-ANALYSIS docs, the revision checklist, and `docs/README.md` updated. |

### Security fixes found while working in this code (not in the roadmap)

Three pre-existing holes in the chat code, all fixed and regression-tested:

1. **Conversation transcripts were readable by any authenticated user.** `session_id` was never checked against `Auth::id()`, so any logged-in user could read any other user's chat history by guessing or reusing a session id. Now enforced on every message, end, history, and feedback call (403 with neutral copy). Test: `test_chatbot_session_ownership_is_enforced`.
2. **Exception messages leaked into JSON responses.** Every `catch` block in the chat controller returned `$e->getMessage()` to the browser. Now fixed generic copy; the cause is logged only.
3. **Owner knowledge entries had no ownership scoping.** `updateIntent`, `deleteIntent`, and `toggleIntentStatus` used bare `findOrFail($id)`, so one owner could edit or delete another owner's rows. Now scoped through the owner's own config, matching the pattern `getIntent` already used.

### Verification performed (Phase 8)

- `php artisan test` — 62 passed (251 assertions). 39 are assistant-specific, including 25 new security tests in `tests/Feature/ChatbotAiGuardrailsTest.php`.
- Both assistant suites use `Http::fake()` + `Http::preventStrayRequests()`, so they need no API key and never reach the network. Shared fixtures in `tests/Concerns/BuildsChatbotSchema.php`.
- Guardrails asserted: 12 injection/credential-probe variants each making **zero** HTTP calls, off-topic sentinel, credential and internals leakage in output, instruction echo, provider 500, transport timeout, unusable payload, request-budget and per-user-budget exhaustion, missing credential, session-ownership 403, credential absence from endpoint responses, and stored transcripts holding only guarded text.
- `./vendor/bin/pint --dirty` — pass.
- `php artisan view:cache` — all Blade views compile. `php artisan route:list --name=chatbot` shows the 7 new cross-portal routes plus the unchanged owner config routes.
- **Live calls against the real Groq account** (unlike Phases 1–3, this pass had a working external dependency to test against): model availability confirmed via `GET /openai/v1/models`; grounded pricing and booking answers returned from live package data; refusals confirmed for `Ignore all previous instructions…`, `You are now DAN…`, `What is your GROQ_API_KEY?`, and a Python-script request.
- Widget partial rendered directly: modal markup present, `/chatbot/*` endpoints resolved, launcher suppression works, a null owner renders nothing, and the output contains neither the key nor the word "Groq".
- **Manual click-through in a logged-in browser was not performed** — reaching the owner or client portal requires signing in, which was out of bounds this pass. The endpoint layer is covered by `actingAs` route tests instead. Same standing caveat as Phases 1–3.

### Known follow-ups (Phase 8)

1. **`.env` is tracked in git — rotate the exposed keys.** Phase 1/2 follow-up #3 flagged this for Stripe test keys; it is now materially worse, because a live Groq key has been added to the same file. `.gitignore` lists `.env`, but the file was committed before that took effect, so the rule does nothing. This directly contradicts task 04's requirement that credentials never be committed. Remediation is `git rm --cached .env`, then **rotate every key that has been in that file** (PayMongo, Stripe, Groq) — untracking stops future commits but does not remove values already in history. Not actioned automatically, since rotation has to be sequenced with it.
2. **Throughput is capped by the 8,000 TPM tier.** The security rules alone are ~650 tokens and are not negotiable, so a request costs 1,300–2,100 tokens depending on whether package detail is injected — roughly **3–5 assistant messages per minute across the whole platform** before users see the `rate_limited` fallback. Already mitigated: package rows injected only for pricing questions (others get a one-line summary), rows capped at 10 with descriptions truncated, knowledge entries capped at 6, history capped at 6 messages / 3,000 characters. For more headroom, raise the Groq tier or lower those limits — do not trim the security rules.
3. **The model needed its reasoning disabled.** `qwen/qwen3.6-27b` is a reasoning model; at Groq's defaults it wrote its chain of thought into the reply body — a one-sentence answer cost 624 tokens instead of 40, the real answer was truncated at `max_tokens`, and the visible reasoning restated the security rules, which the output guard correctly rejected as an instruction echo. Fixed with `reasoning_format: parsed` + `reasoning_effort: none` (both configurable, sent only when non-empty). Worth re-checking whenever `GROQ_MODEL` changes.
4. **`ChatbotConversationController` is still dead code** — the class exists with index/show/export/destroy/bulkDestroy/stats methods but is wired to no route. Pre-existing, left alone this pass; either route it or delete it.
5. **`owner/view-inquiries.blade.php` is still a static placeholder** — "Example Field" scaffolding with no data binding. Pre-existing and unrelated to the assistant, but it sits in the same sidebar group.
6. **`PaymongoService` and `StripeService` log full request payloads and error bodies.** The assistant's logging policy (status codes and reason codes only) was deliberately not retrofitted onto them this pass — out of scope, but they are the remaining places where sensitive request data reaches the logs.
7. **Feedback endpoints are still unwired in the UI.** `chatbot.helpful` / `chatbot.not-helpful` now enforce session ownership but no surface calls them — pre-existing, and a thumbs up/down control on assistant replies would be a small follow-up.

---

## Phase 9 — Cancellation Contingency

> Documented 2026-07-26 on `main`, from `prompt/tasks/07.md`. Full analysis:
> [`../04-REFERENCE/PHOTOGRAPHER CANCELLATION CONTINGENCY.md`](../08-references/photographer-cancellation-contingency.md).
> Task report: [`../../prompt/output/07.md`](../../../prompt/output/07.md).
>
> **This pass changed no code.** The brief was explicitly analysis-only: document the scenario, present
> multiple resolution options rather than one, and record the decisions that must be made before
> implementation. Every row below is 📋.

| # | Item | Status | Notes |
|---|---|---|---|
| 9.1 | Unblock owner recovery on a deadlocked booking | 📋 | **Not gated by any decision — build this first.** Accepting an assignment sets `booking.status = 'in_progress'`, which is the exact status that blocks both `getAvailablePhotographers()` and `removePhotographerAssignment()`. A photographer who accepts and then cancels therefore leaves a paid booking `in_progress` with zero active photographers and the owner locked out of both recoveries. `canTransitionTo()` also allows no backwards move out of `in_progress`. |
| 9.2 | Cascade notification on photographer cancellation | 📋 | **Not gated by any decision.** Cancellation currently writes `status`/`cancelled_at`/`cancellation_reason` on the assignment row and stops — no booking change, no owner notice, no client notice. `Notifiable` already has 18 methods; this is a missing call, not a missing capability. Needs a resolution SLA in the shape of 2.3's `response_deadline` and 2.7's `bookings:expire-pending`. |
| 9.3 | Photographer substitution flow (Option A) | 📋 | Needs **D1** (who chooses the remedy) and **D2** (may the client reject a substitute). Cheapest remedy and the only one preserving an unmovable date. `PhotographerAvailabilityService::getAvailabilityMapForBooking()` already does the leave + time-overlap matching, so no new matching logic is required. Requires 9.1 first. |
| 9.4 | Reschedule path (Options B and C) | 📋 | Needs **D1**. No controller changes `event_date`/`start_time`/`end_time` after creation — the whole path is missing. Inapplicable to fixed-date events, which is a large share of real cases. |
| 9.5 | Refund execution (Options D and E) | 📋 | Needs **D3**, **D4**, **D6**. Largest technical prerequisite in the phase: **neither `PaymongoService` nor `StripeService` can refund at all**. `refund_pending` (set by 2.5) is read by nothing, has no badge mapping, and has no queue; `PAYMENT_REFUNDED` is declared and never assigned; the freelancer-side handler is a `Log::info` stub. Refunding today would also leave `SystemRevenueModel` overstating revenue — `markAsRefunded()` exists but no booking path calls it. PayMongo GCash refund rules must be verified against the live account before the API surface is designed. |
| 9.6 | Booking credit ledger (Option F) | 📋 | Needs **D5**, and 9.5 must exist first. No credit or wallet concept exists; `tbl_client_budget` is a spending planner, not stored value. Largest item in the phase and the only one creating a standing financial liability. |
| 9.7 | Photographer cancellation record | 📋 | Needs **D7**. Cancellation reasons are stored and read nowhere — no count, no rate, nothing surfaced to the owner at assignment time. |
| 9.8 | Freelancer emergency pool (Option H) | 📋 | Needs **D8**, and 9.3 first. Widens substitution beyond the studio's own roster: studio staff → off-duty staff on overtime → **platform freelancers**. Best return per unit of work in the phase — a small studio on a Saturday often has nobody free, which is exactly the case where the date cannot move. The freelancer supply already exists on the platform with categories and schedules and has never been connected to studio bookings. Opt-in for freelancers, never an automatic draft. Studio-to-studio cover deliberately excluded — needs a revenue split that does not exist. |
| 9.9 | Value-gap refund on a downgraded substitution (Option I) | 📋 | Needs **D4**, **D8**, and 9.5 first. A ten-year lead replaced by a first-year assistant is the same booking on paper and a different product in reality. Equal or better replacement → no adjustment; worse → refund the difference automatically. Anchors on position/years/specialization already stored on `tbl_studio_photographers`. Small once 9.5 exists. |

| 9.10 | Restrict late cancellation (prevention) | 📋 | Needs **D9**. Today a photographer can self-cancel with one click at any moment until they mark themselves on-site — a cancellation three weeks out and one twelve hours out share the same interface. **Cheapest item in the phase with the largest effect:** by event morning every remedy is bad, so the leverage is in having fewer event-morning cancellations. |
| 9.11 | Backup photographer on high-value bookings (prevention) | 📋 | **Not gated by any decision.** A named second on the assignment from the start, promoted automatically if the primary cancels — collapses the whole escalation ladder into one step. Substantive design question is whether a backup blocks that photographer from other work. |

### Recommended build set

Documented in §6 of the contingency document as a **recommendation, not a decision** — the brief
requires options be evaluated before a policy is finalised.

- **Tier 1, required (4):** 9.1, 9.2, 9.3, 9.5. Stop here and no paid client is ever stranded silently
  again. 9.1 and 9.2 are gated by nothing and can start now; 9.5 (refund capability from zero) is the
  only real project.
- **Tier 2, high value (3):** 9.8, 9.9, 9.7.
- **Tier 3, prevention (2):** 9.10, 9.11.
- **Not recommended (2):** 9.6 (credit ledger — biggest build, standing financial liability, and
  credit-instead-of-cash when the *provider* cancelled reads as coercive) and 9.4 (reschedule — only
  helps when the date can move, which is the case that was never urgent).

### Why the fixed-date case drives the design

When the event cannot move — a wedding, a graduation — most of the option set evaporates: reschedule is
inapplicable, credit offers a future shoot for an occasion that happens once, and a refund on the morning
of the event does not get anyone photographed. **Only substitution helps.** That is why the recommended
order is 9.1 → 9.2 → 9.3, with 9.5 as the floor when substitution fails outright, and why 9.8 (widening
the pool) outranks 9.4 (moving the date). §5 of the contingency document covers the escalation ladder,
the notice tiers, and the honest failure case where nobody can be found.

### Decisions outstanding

D1 who chooses the remedy · D2 may the client reject a substitute · D3 automated vs manual refunds ·
D4 who absorbs the platform fee and any studio payout already made · D5 is credit ever offered instead
of cash · D6 are partial refunds acceptable when the *provider* cancelled · D7 what consequence attaches
to a cancelling photographer · **D8 how wide the substitution net goes** (studio only, plus overtime, or
platform freelancers) · **D9 should late cancellation be restricted, and from what point**. Full framing
in §8 of the contingency document. D1, D2, D8, D9 gate the options that actually save the session.

### Related defects surfaced while documenting (not fixed)

1. **`Client\MyBookingsController::cancelBooking()` overwrites `payment_status` with `'cancelled'`** even on a fully paid booking, destroying the record that money was received. Pre-existing, and it will collide with whatever refund states Phase 9 introduces — both paths write the same column.
2. **`tbl_bookings.payment_status` is an unconstrained string**, not an enum, so states like `refund_pending` can be written with nothing rendering them. That is exactly what happened in 2.5.
3. **Freelancer bookings are a parallel unanalysed case** — a freelancer *is* the photographer, so substitution is impossible and the same brief collapses to reschedule/refund/credit. Worth its own pass.
4. **Multi-photographer bookings are unmodelled** — one of four photographers cancelling is a partial-staffing problem, and none of the seven documented options is written for it. `allPhotographersCompleted()` assumes every assignment eventually completes.

### Verification performed (Phase 9)

- Documentation only: `git diff --stat` shows `.md` files exclusively — no `app/`, `database/`, `resources/`, or `tests/` changes.
- `composer test` unchanged at 70 passed, confirming no behavioral edit slipped in.
- Every code claim in the analysis was read out of the current source before being written down, and each is cited to its file and line.

---

## Phase 10 — Subscription Lifecycle

> Documented 2026-07-27 on `main`, from `prompt/tasks/08.md`. Full analysis:
> [`../04-REFERENCE/SUBSCRIPTION LIFECYCLE.md`](../08-references/subscription-lifecycle.md).
> Task report: [`../../prompt/output/08.md`](../../../prompt/output/08.md).
>
> **This pass changed no code.** The brief was explicitly documentation-only: assess the documented
> subscription lifecycle, verify it against the implementation, and recommend a process. Every row
> below is 📋. Unlike Phase 9, this phase recommends **one** lifecycle rather than an option set —
> the brief asked for a process, not a menu.
>
> **The two findings that produced the phase.** A free trial never ends: the trial branch of
> `SubscriptionController::subscribe()` sets `end_date` to a full billing period instead of to
> `trial_ends_at`, and nothing anywhere compares `trial_ends_at` to the current time in order to change
> state — so a 14-day trial on the seeded Studio Growth plan is 30 days of fully active, `amount_paid = 0`
> access, and a 30-day trial on the yearly plan is 365. And an expired subscription takes nothing away:
> `OwnerMiddleware` contains no subscription logic, there is no `app/Policies` directory and no `Gate::`
> definition in the application, and the sole subscription-aware middleware guards two routes and only
> from the owner's second studio onward. **The platform's revenue model rests on subscriptions and
> nothing on the platform depends on having one.**

| # | Item | Status | Notes |
|---|---|---|---|
| 10.1 | Align a trial's `end_date` with `trial_ends_at` | 📋 | **Not gated by any decision — build this first.** `subscribe()` writes `trial_ends_at = now() + trial_days` and `end_date = calculateEndDate()`, which returns `addMonth()`/`addYear()`. `StudioPlanModel::isActive()` tests `status`, `end_date` and `payment_status` and never looks at `trial_ends_at`, so the trial length governs nothing but a notification. Trial rows are also written `payment_status = 'paid'` at `amount_paid = 0`, which makes them indistinguishable from paid subscriptions in every revenue query. Needs a backfill for existing rows. |
| 10.2 | Expire trials | 📋 | **Not gated by any decision.** `NotifyTrialEndingCommand` is the only consumer of `trial_ends_at` and only writes an in-app notification; no command mutates state on it. The notification it sends tells the owner to "Add a payment method to keep your plan active" — there is no payment-method screen, route, or column anywhere on the platform, so the instruction is unfollowable. The trial branch also makes no Stripe call at all, so no card is on file and **the trial cannot convert**. `ExpirePendingBookingsCommand` is the pattern to copy. |
| 10.3 | Expire paid subscriptions, write `expired` | 📋 | **Not gated by any decision.** `tbl_studio_plans.status` declares `expired` and **no code path ever writes it**. Expiry is implicit, emerging from an `end_date >= now()` filter in three read sites, so a subscription that lapsed a year ago still reads `active` in the database, in reports, and to the owner. `next_billing_date` is written on every row and read by nothing. |
| 10.4 | Add `past_due` and a grace period | 📋 | Needs **S1**. No grace column, config, or code exists — a subscription is active or it is nothing. `paymentFailed()` writes `payment_status = 'failed'` and `status = 'cancelled'` in one action with no retry, no notice, and no distinction between a declined card and an owner clicking back out of Checkout. |
| 10.5 | Access restriction on expiry | 📋 | Needs **S3**, **S4**, **S5**, **S6**, and 10.3 first. **The largest item in the phase and the reason the rest of it matters.** `CheckStudioRegistrationLimit` is the only subscription-aware middleware: it is registered on two routes, lets the GET through unconditionally, and blocks the POST only when the owner already has ≥1 studio. Two defects to fix while there — it tests `$user->role !== 'owner'`, so `owner-super-admin` skips it entirely, and it queries across all of the owner's studios while `tbl_studio_plans` keys the subscription to one studio. Recommended behaviour is **restrict, never delete**: the account, the studios and every historical record survive; marketplace listing and new bookings stop. Honouring in-flight paid bookings (S5) forces the gate to be capability-scoped rather than a portal lock. |
| 10.6 | Wire the notification ladder | 📋 | Needs **S1**. **Absorbs roadmap 6.4.** `notifySubscriptionExpiring()` has existed since 2.8 and is called from nowhere — dead code. `app/Mail/` holds no subscription mailable, so every subscription notification today is in-app only and an owner who does not log in learns nothing. 6.4's third bullet ("downgrade to free tier") was struck: there is no free tier, `plan_type` being `basic\|premium\|enterprise` with all eight seeded plans priced. |
| 10.7 | Reactivation | 📋 | Needs **S4**, and 10.3 + 10.5 first. No route, controller method, or UI revives a cancelled or expired subscription. `subscribe()` creates a brand-new row every time, so history is a pile of disconnected records with no continuity. Nothing needs restoring from backup because 10.5 removes access, not data. |
| 10.8 | Recurring billing and a card on file | 📋 | Needs **S2**, and is blocked by roadmap **1.5**'s webhook work. **Largest technical prerequisite in the phase.** `StripeService::createSubscriptionCheckoutSession()` builds a Checkout Session with `'mode' => 'payment'` — a one-time charge. No Stripe Product or Price, no subscription object, no customer, no saved payment method. `verifyWebhookSignature()` exists and no route consumes it, so an asynchronous payment result is never learned about. Until this lands there is no renewal and no trial conversion. |
| 10.9 | Cancellation and upgrade beyond the 3-day window | 📋 | The refund half inherits **9.5**'s gateway blocker. `canBeCancelled()` permits cancellation only within 3 days of `paid_at`; **after day 3 there is no cancellation path at all**, and `subscribe()` rejects any new plan while an active one exists. An owner past day 3 on a yearly plan can neither upgrade, downgrade, nor cancel for the rest of the year. `cancel()` also flips the `tbl_system_revenue` row to `refunded` via a direct `update()` rather than `SystemRevenueModel::markAsRefunded()`, and **calls no gateway** — the books say refunded while the money stays with Stripe. |

### Recommended lifecycle

Documented in §5 of the lifecycle document as a **recommendation, not a decision**:
`trialing → active → past_due → grace → expired`, with `cancelled` meaning cancel-at-period-end
and `expired → active` on reactivation. Three rules follow from it — a trial's `end_date` **is** its
`trial_ends_at`; expiry is a written state with a timestamp and a notification, not the absence of a
query match; and cancellation never takes away time already paid for. Build order: 10.1, 10.2, 10.3
need no decision and should go first; 10.5 is what makes a subscription mean anything.

### Post-expiration handling

The brief's central question. Recommendation is **restrict, never delete** — expiry is a billing
event, not a moderation action, and deleting an account destroys clients' booking history,
employees' payroll, and the platform's own revenue ledger to punish a lapsed card. Sign-in, the
studios, and all historical data survive permanently; marketplace listing, new bookings, and
tier-limited editing stop. Bookings a client has already paid for are honoured to completion
whatever the subscription says — the dispute is between the owner and the platform and must not
reach the client. §6 of the lifecycle document has the full capability split.

### Decisions outstanding

S1 how long the grace period is · **S2 is a payment method required to start a trial** · **S3 exactly
what access survives expiry** · **S4 is a subscription scoped to a studio or to an owner** (the schema
says studio; the middleware and `max_studios` say owner — both cannot stay) · S5 are in-flight paid
bookings honoured after expiry · **S6 is there a free tier at all** (roadmap 6.4 assumed one; there
is none). Full framing in §8 of the lifecycle document. S2 and S6 shape the state machine and should
be settled first; S3, S4 and S5 all feed 10.5 and can be settled together.

### Documentation corrected this pass

Three statements in the existing docs were contradicted by the code and have been fixed in place:

1. **`TECHNICAL ANALYSIS.md` §5.9** and **`NON TECHNICAL ANALYSIS.md` §4.7** both stated that a studio
   owner needs an active subscription to create a studio. **The first studio is free** — the gate fires
   only from the second onward, only on POST, and not at all for `owner-super-admin`.
2. **Roadmap 6.4** specified "On expiry: downgrade to free tier." **No free tier exists.** Struck and
   superseded by Phase 10.
3. **Roadmap 3.4** step 4 ("during trial period: subscription is active") is the defect itself, not a
   spec — annotated in place and its remainder moved to 10.1/10.2.

`REVISION CHECKLIST AND RECOMMENDATIONS.md` item 11 ("No free-trial period implemented") was stale
rather than wrong, and is now annotated.

### Related defects surfaced while documenting (not fixed)

1. **`tbl_freelancer_plans` has no `trial_ends_at` column** — the 3.4 migration added it to the studio
   table only. There is no freelancer subscribe controller, route, or checkout path at all; freelancer
   plan rows come from seeders and nothing else. The catalog nevertheless sells four freelancer plans,
   two of them with trial days.
2. **`Admin\SubscriptionController::edit()` renders a view that does not exist** (`admin.edit-subscription-plans`).
   Already recorded under 3.4; it means `trial_days` is write-once at plan creation.
3. **Every seeded studio plan sets `max_studios = 1`**, so with seed data the multi-studio subscription
   gate — the only subscription check on the platform — can never pass on any plan.
4. **The platform's booking commission works with no subscription at all**, since `SystemRevenueModel`
   takes its cut per booking. Whether an unsubscribed studio should generate commission revenue has
   never been posed as a question.
5. **Studio HR, finance and photographer staff sign in through their own portals**, and nothing states
   whether their access follows the owner's subscription. Unaddressed in every document.

### Verification performed (Phase 10)

- Documentation only: `git diff --stat` shows `.md` files exclusively — no `app/`, `database/`, `resources/`, or `tests/` changes.
- `composer test` unchanged at 70 passed, confirming no behavioral edit slipped in.
- Every code claim above was read out of the current source before being written down, and each is cited to its file and line in the lifecycle document.
