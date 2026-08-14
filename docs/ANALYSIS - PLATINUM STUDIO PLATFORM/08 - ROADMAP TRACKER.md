# 08 - ROADMAP TRACKER

[Back to start](00%20-%20START%20HERE.md) · Previous: [07 - DEVELOPMENT ROADMAP](07%20-%20DEVELOPMENT%20ROADMAP.md) · Next: [09 - TASK TRACKER](09%20-%20TASK%20TRACKER.md)

**Last checked:** 14 August 2026

## Where everything stands

| Status | How many |
|---|---:|
| ✅ Finished | 18 |
| 🟨 Being worked on | 0 |
| ⭕ Not started | 0 |
| ❌ Blocked | 0 |
| 🔵 Already there | 2 |
| ⬜ Dropped | 0 |
| ❓ Unclear | 0 |
| **Total** | **20** |

The platform has eighteen finished items and two already present; nothing is currently blocked.

## Phase 1 — Make the foundations dependable

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-01 | Preserve seed, media-storage, and application contracts | 🔵 Already there | Existing tests and storage conventions are present. |
| R-02 | Complete payment callback verification and coverage | ✅ Finished | Both provider webhook paths are covered by signature, processing, idempotency, and status-transition tests. |
| R-03 | Repair the owner Services rendering defect | ✅ Finished | ISS-002 fixed: removed `json_decode` on already-cast arrays; page rendering verified by test. |
| R-04 | Expand route, role, studio-scope, and permission regression checks | ✅ Finished | Portal boundaries covered by 49 matrix checks and RBAC enforcement by 4 middleware tests; photographer middleware JSON parity restored. |

## Phase 2 — Complete booking and media journeys

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-05 | Align booking expiry and status transitions | ✅ Finished | `cancelled_by` accepts `system`, the expiry command records it and notifies freelancers, and the transition matrix and expiry command are covered by tests. |
| R-06 | Finish assignment lifecycle | ✅ Finished | Owner assignment-status route repaired, deadline warnings scheduled, owner notified on accept/cancel/complete, and cancellation cascades to open assignments; lifecycle covered by tests. |
| R-07 | Finish gallery and review delivery | ✅ Finished | Draft, upload, publish, portfolio, and review surfaces are exercised by gallery and review lifecycle tests. |
| R-08 | Add ordinary booking cancellation path | ✅ Finished | Client-initiated cancellation of pending and confirmed bookings with at least 24 hours notice, required reason (min 20 chars), `cancelled_by='client'`, assignment cascade, and notifications; paid bookings enter the existing Phase 6 manual refund queue with per-payment provider evidence; the `payment_status='cancelled'` collision is removed. Ordinary cancellation stays distinct from photographer cancellation and Q-11. Covered by 9 focused tests delivered 14 August 2026. |

## Phase 3 — Make each role’s daily work complete

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-09 | Complete approved studio-management requirements | ✅ Finished | Q-04 approved the six-group dependency order on 14 August 2026 and the gaps were built: registration/commercial fields (suffix, org role, max price, down-payment toggle, LinkedIn, Others category, owner discount rules), permit expiry capture with verification gate and re-verification resubmit flow, admin email-OTP login with in-app document review, standardized rejection reasons and resubmission counts, optional Next/Skip onboarding, forced first-login password change, combined user roles with category Select All, soft-delete/archive everywhere instead of hard delete, client favorites, and package-image visibility. Covered by 100+ focused tests delivered 14 August 2026. |
| R-10 | Complete attendance, leave, overtime, schedules, and payroll | ✅ Finished | Check-in/out with geolocation, leave approval, and payroll generation with finance approval are covered by lifecycle tests. |
| R-11 | Complete procurement lifecycle | ✅ Finished | The audit timeline action mismatch was repaired and the full request-to-completion state machine is covered by an integration test. |
| R-12 | Enforce and test scoped role permissions | ✅ Finished | Cross-studio isolation is proven by tests for HR employees, finance payroll, and photographer assignments. |

## Phase 4 — Expand discovery and advanced operations

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-13 | Build approved public landing page | ✅ Finished | A Bootstrap public landing page now serves the guest root with login and register entry points. |
| R-14 | Complete discovery improvements | ✅ Finished | Subscription rank transparency is live: studios on premium plans show a Featured badge with explanatory tooltip in the client marketplace. |
| R-15 | Add recurring bookings and equipment assignment | ✅ Finished | Recurring bookings generate child sessions at creation, and owners can assign equipment that assigned photographers confirm. |

## Phase 5 — Automate and protect ongoing work

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-16 | Complete notification, scheduler, expiry, and deadline automation | ✅ Finished | "View all" notifications page built on a shared cross-portal layout; `notifications:prune` removes read records older than 30 days (configurable); `bookings:send-reminders` warns at 1 and 3 days ahead with per-day deduplication; all seven schedules carry `withoutOverlapping` and production-only constraints; procurement escalation and schedule registration are covered by tests. |
| R-17 | Maintain assistant safety and operational controls | 🔵 Already there | Guardrails, ownership, throttle, and fallback behavior exist and are covered by 23 tests; the stale `fallback_message` fillable entry was removed after its column was dropped. |
| R-18 | Broaden security and regression coverage | ✅ Finished | Login and registration POST routes are rate limited; CSRF wiring, webhook exemption, gallery upload rejection, cross-role access, and schedule registration are covered by regression tests. |

## Phase 6 — Resolve cancellation and subscription lifecycles

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-19 | Complete renewal, failed billing, cancellation, and reactivation | ✅ Finished | Recurring Stripe Checkout/webhooks, card-free trials, fixed seven-day grace, period-end cancel/resume, previous-plan reactivation, idempotent invoice/revenue records, and focused/full regression evidence delivered 12 August 2026. |
| R-20 | Implement paid-booking photographer cancellation remedy | ✅ Finished | Same-studio replacement, client response, deadline escalation, manual admin full-refund evidence, payment/revenue reversal, role/studio isolation, UI routes, and focused/full regression evidence delivered 12 August 2026. |

## What is blocked

Nothing is currently blocked. R-08 was unblocked by the approved ordinary-cancellation rule and financial outcome, and R-09 by the Q-04-approved build order (both 14 August 2026).

## Status legend

| Emoji | Means |
|---|---|
| ✅ | Finished — built, checked, working |
| 🟨 | Being worked on — started, not finished |
| ⭕ | Not started — waiting its turn |
| ❌ | Blocked — something is stopping it |
| 🔵 | Already there — found in the supplied material |
| ⬜ | Dropped — decided against, kept for the record |
| ❓ | Unclear — the material does not say |
