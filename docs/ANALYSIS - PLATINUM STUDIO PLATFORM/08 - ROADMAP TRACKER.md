# 08 - ROADMAP TRACKER

[[00 - START HERE|Back to start]] · Previous: [[07 - DEVELOPMENT ROADMAP]] · Next: [[09 - TASK TRACKER]]

**Last checked:** 11 August 2026

## Where everything stands

| Status | How many |
|---|---:|
| ✅ Finished | 0 |
| 🟨 Being worked on | 4 |
| ⭕ Not started | 5 |
| ❌ Blocked | 4 |
| 🔵 Already there | 7 |
| ⬜ Dropped | 0 |
| ❓ Unclear | 0 |
| **Total** | **20** |

The platform has seven roadmap items already present, four partly delivered, five waiting for their turn, and four blocked by policy or approval questions.

## Phase 1 — Make the foundations dependable

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-01 | Preserve seed, media-storage, and application contracts | 🔵 Already there | Existing tests and storage conventions are present. |
| R-02 | Complete payment callback verification and coverage | 🟨 Being worked on | Provider routes and tests exist; broader evidence remains. |
| R-03 | Repair the owner Services rendering defect | ⭕ Not started | Recorded as ISS-002 in the frozen progress record. |
| R-04 | Expand route, role, studio-scope, and permission regression checks | 🔵 Already there | Middleware, permissions, and focused tests exist; coverage can expand. |

## Phase 2 — Complete booking and media journeys

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-05 | Align booking expiry and status transitions | 🔵 Already there | Current commands and booking controllers implement the recorded slice. |
| R-06 | Finish assignment lifecycle | 🟨 Being worked on | Assignment paths exist; cancellation outcome is separate and blocked. |
| R-07 | Finish gallery and review delivery | 🔵 Already there | Draft, upload, publish, portfolio, and review surfaces exist. |
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
