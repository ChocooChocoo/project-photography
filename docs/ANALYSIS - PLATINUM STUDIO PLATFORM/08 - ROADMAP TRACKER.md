# 08 - ROADMAP TRACKER

[Back to start](00 - START HERE.md) · Previous: [07 - DEVELOPMENT ROADMAP](07 - DEVELOPMENT ROADMAP.md) · Next: [09 - TASK TRACKER](09 - TASK TRACKER.md)

**Last checked:** 12 August 2026

## Where everything stands

| Status | How many |
|---|---:|
| ✅ Finished | 6 |
| 🟨 Being worked on | 2 |
| ⭕ Not started | 4 |
| ❌ Blocked | 4 |
| 🔵 Already there | 4 |
| ⬜ Dropped | 0 |
| ❓ Unclear | 0 |
| **Total** | **20** |

The platform has six finished items, four already present, two partly delivered, four waiting for their turn, and four blocked by policy or approval questions.

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
| R-08 | Add ordinary booking cancellation path | ❌ Blocked | Must be distinguished from photographer cancellation and Q-11. |

## Phase 3 — Make each role’s daily work complete

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-09 | Complete approved studio-management requirements | ❌ Blocked | Q-04 has no final build order. |
| R-10 | Complete attendance, leave, overtime, schedules, and payroll | 🔵 Already there | Current HR/finance/owner routes cover the delivered surface. |
| R-11 | Complete procurement lifecycle | 🔵 Already there | `ProcurementWorkflowService` contains the recorded state actions. |
| R-12 | Enforce and test scoped role permissions | 🟨 Being worked on | The mechanism exists; cross-portal proof remains a continuing requirement. |

## Phase 4 — Expand discovery and advanced operations

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-13 | Build approved public landing page | ⭕ Not started | Documentation-only task; Q-03. |
| R-14 | Complete discovery improvements | ⭕ Not started | Historical roadmap proposal. |
| R-15 | Add recurring bookings and equipment assignment | ⭕ Not started | Historical advanced-work proposal. |

## Phase 5 — Automate and protect ongoing work

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-16 | Complete notification, scheduler, expiry, and deadline automation | 🟨 Being worked on | Several commands and notification routes already exist. |
| R-17 | Maintain assistant safety and operational controls | 🔵 Already there | Guardrails, ownership, throttle, and fallback behavior exist. |
| R-18 | Broaden security and regression coverage | ⭕ Not started | Planned beyond the current focused tests. |

## Phase 6 — Resolve cancellation and subscription lifecycles

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-19 | Complete renewal, failed billing, cancellation, and reactivation | ❌ Blocked | Q-02. |
| R-20 | Implement paid-booking photographer cancellation remedy | ❌ Blocked | Q-01 and Q-11. |

## What is blocked

| # | What is stopping it | What would clear it |
|---|---|---|
| R-08 | Ordinary cancellation policy is not fully reconciled with historical options. | Approve the final rule and financial outcome. |
| R-09 | Core studio-management requirements lack one approved order. | Approve scope and sequence under Q-04. |
| R-19 | Renewal and reactivation policy is not approved. | Answer Q-02. |
| R-20 | Paid-booking cancellation remedy is not approved. | Answer Q-01 and Q-11. |

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
