# 07 - DEVELOPMENT ROADMAP

[Back to start](00 - START HERE.md) · Previous: [06 - DIAGRAMS](06 - DIAGRAMS.md) · Next: [08 - ROADMAP TRACKER](08 - ROADMAP TRACKER.md)

## What this covers

This is a development order, not a launch calendar or a promise that every proposal is approved. It consolidates the historical twelve-phase roadmap into six outcomes while keeping the old records available as provenance.

## Where the plan came from

The items are drawn from [02 - DOCUMENT FINDINGS](02 - DOCUMENT FINDINGS.md), [03 - CODE FINDINGS](03 - CODE FINDINGS.md), [04 - COMBINED FINDINGS](04 - COMBINED FINDINGS.md), the frozen roadmap and progress records, and the twelve frozen user task prompts. Existing code is marked already there in [08 - ROADMAP TRACKER](08 - ROADMAP TRACKER.md); unsupported policy is blocked or unclear.

## The phases at a glance

| Phase | What it delivers | Waits on |
|---|---|---|
| Phase 1 — Make the foundations dependable | Current defects, payment evidence, and permission regression are visible and safe. | Nothing |
| Phase 2 — Complete booking and media journeys | Booking, assignment, expiry, gallery, and review paths agree. | Phase 1 |
| Phase 3 — Make each role’s daily work complete | Studio, people, attendance, payroll, procurement, and permissions are coherent. | Phase 2 |
| Phase 4 — Expand discovery and advanced operations | Public entry, discovery, recurring work, and equipment capabilities are approved and delivered. | Phase 3 |
| Phase 5 — Automate and protect ongoing work | Notifications, assistant operations, security, and regression coverage are durable. | Phases 1–4 |
| Phase 6 — Resolve cancellation and subscription lifecycles | Approved financial and access outcomes exist for exceptional and recurring cases. | Policy answers plus Phases 1–5 |

## Phase 1 — Make the foundations dependable

**The goal:** Current defects and boundary evidence are known and verified.

**Why it comes first:** Later claims depend on a stable understanding of routes, roles, storage, payment callbacks, and test evidence.

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-01 | Preserve the seed, media-storage, and existing application contracts while rebuilding the evidence baseline. | Prevent documentation or follow-on work from hiding current behavior. | Frozen technical testing and progress records |
| R-02 | Complete payment callback verification and regression coverage for the providers already wired. | Make booking confirmation evidence trustworthy. | Frozen roadmap Phase 1 and `routes/web.php`, lines 13–14 |
| R-03 | Repair the owner Services rendering defect. | Remove the recorded HTTP 500 caused by decoding an already-cast value. | Frozen ISS-002 and `resources/views/owner/view-services.blade.php` |
| R-04 | Expand route, role, studio-scope, and permission regression checks. | Prevent portal visibility from being mistaken for authority. | Frozen architecture, security, and requirements records |

**How you know the phase is finished:** The current defect is either fixed and verified or explicitly tracked; provider callback tests are current; and each portal boundary has evidence.

**What could hold it up:** The defect may require a separately approved application fix; runtime provider behavior may not be reproducible locally.

## Phase 2 — Complete booking and media journeys

**The goal:** A client booking can be followed from request through payment, assignment, gallery, and review without undocumented state gaps.

**Why it comes here:** Assignment and gallery delivery depend on the shared booking and payment record being reliable.

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-05 | Keep booking expiry, status transitions, and provider confirmation aligned. | Prevent stale or contradictory booking states. | Frozen roadmap and current booking commands/controllers |
| R-06 | Finish the studio and freelancer assignment lifecycle. | Give owners and photographers a clear path from assignment to completion. | Frozen Phase 2/3 records and assignment controllers |
| R-07 | Finish gallery draft, upload, publish, portfolio, review, and moderation behavior. | Make delivery visibility agree with the booking result. | Frozen gallery process flows and current gallery routes |
| R-08 | Add the approved ordinary booking cancellation path. | Separate normal cancellation from the unresolved photographer-cancellation case. | Frozen roadmap and cancellation references |

## Phase 3 — Make each role’s daily work complete

**The goal:** Studio people can manage the business using coherent, scoped records.

**Why it comes here:** These workflows consume the booking, user, role, and media foundations.

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-09 | Complete approved studio onboarding, member, service, pricing, and archive requirements. | Turn the core-studio requirements into one ordered deliverable. | Frozen `tasks/10.md` and core-studio references |
| R-10 | Complete attendance, leave, overtime, schedule, and payroll workflows. | Make staff administration consistent across HR, finance, and owner portals. | Frozen technical analysis and current HR/finance routes |
| R-11 | Complete procurement review, ordering, delivery, return, replacement, and payment states. | Keep equipment and purchasing records auditable. | Frozen roadmap and `ProcurementWorkflowService.php` |
| R-12 | Enforce and test studio-scoped role and permission actions across all seven portals. | Keep responsibility boundaries explicit. | Frozen requirements and current RBAC code |

## Phase 4 — Expand discovery and advanced operations

**The goal:** Approved public and advanced product capabilities are delivered without mixing proposals into current behavior.

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-13 | Build the approved public landing page. | Provide the documented entry experience. | Frozen `tasks/09.md` and landing-page references |
| R-14 | Complete discovery improvements such as location, ranking, featured visibility, and service presentation. | Make provider discovery match the proposed product direction. | Frozen roadmap Phases 2–5 |
| R-15 | Add approved recurring bookings, equipment assignment, and other advanced booking support. | Support longer-running and operationally richer work. | Frozen roadmap Phase 5 |

## Phase 5 — Automate and protect ongoing work

**The goal:** Repeated work and safety controls are dependable.

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-16 | Complete notification, scheduler, expiry, and deadline automation. | Reduce manual follow-up and missed operational events. | Frozen roadmap Phase 6 and notification code |
| R-17 | Maintain the photography assistant’s scope, ownership, failure, rate, and credential controls as durable operational behavior. | Keep the assistant safe as its surfaces expand. | Frozen AI reference and Task 04 |
| R-18 | Broaden security and regression coverage across core workflows. | Prove changes do not reopen cross-role or cross-studio access gaps. | Frozen roadmap Phase 7 and current tests |

## Phase 6 — Resolve cancellation and subscription lifecycles

**The goal:** Exceptional and recurring financial states have approved, testable outcomes.

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-19 | Implement renewal, failed billing, cancellation, and reactivation after approval. | Complete the subscription lifecycle beyond current expiry and grace behavior. | Frozen subscription reference and Q-02 |
| R-20 | Implement the approved remedy when a photographer cancels a paid booking. | Protect the client, studio, photographer record, notifications, date, and money. | Frozen cancellation reference and Q-01/Q-11 |

## Deliberately left out

| What | Why it is not an active roadmap commitment |
|---|---|
| Unapproved refund, credit, substitution, or reschedule rules | The material presents options, not a final decision. |
| A guessed implementation order for every core-studio requirement | `tasks/10.md` requires documentation and refinement, not an implicit build authorization. |
| Deleting the old documentation trees | Removal is a separate approval gate after coverage and hash checks. |
