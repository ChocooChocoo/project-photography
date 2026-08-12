# 04 - COMBINED FINDINGS

[[00 - START HERE|Back to start]] · Previous: [[03 - CODE FINDINGS]] · Next: [[05 - SYSTEM ARCHITECTURE]]

## The picture in one paragraph

The written record and the application agree on the broad platform: role-specific portals around a shared booking, payment, gallery, and studio-operations core. The current code is further along than some historical summaries suggest, especially for permissions, procurement, assistant guardrails, subscription grace, and access enforcement. It is also less complete than the broad product vision: paid-booking cancellation outcomes, renewal and reactivation, the public landing page, and selected defects or policy decisions remain open.

## Promised and built

| # | Promise | Written source | Current evidence | Match |
|---|---|---|---|---|
| M-01 | Separate portals for administrator, owner, client, freelancer, HR, finance, and photographer. | Frozen technical analysis, portal and role sections. | `routes/web.php` role groups and matching middleware/controllers. | Fully, subject to permission verification. |
| M-02 | Booking connects selection, payment, assignment, gallery delivery, and review. | Frozen architecture and roadmap records. | `BookingModel.php`, booking controllers, payment routes, assignment, gallery, and review records. | Mostly; cancellation outcomes remain incomplete. |
| M-03 | Payment-provider confirmation updates payment and booking state. | Frozen technical analysis and Task 04/roadmap records. | PayMongo and Stripe webhook routes plus payment services. | Built; fresh provider-level verification is limited to local tests. |
| M-04 | Galleries can remain draft until publication and can support portfolio work. | Frozen process flows and Phase 2/3 roadmap. | Owner gallery routes for draft, upload, update, publish, and portfolio. | Built in current code. |
| M-05 | Assistant uses photography scope and defensive validation. | Frozen AI Assistant Integration reference. | `ChatbotService.php`, request validation, throttle, and assistant tests. | Built; provider availability remains environment-dependent. |
| M-06 | Subscription expiry has grace and scoped access behavior. | Frozen QST-002 decision and Phase 10 progress. | Expiry and notification commands plus `EnforceStudioSubscriptionAccess.php`. | Built for the recorded slice; renewal is separate. |

## Promised but missing or incomplete

| # | Promise | Written source | What exists instead |
|---|---|---|---|
| G-01 | A complete paid-booking response after a photographer cancels. | Frozen `PHOTOGRAPHER CANCELLATION CONTINGENCY.md`, sections 2–8. | Assignment updates exist, but no approved end-to-end remedy and financial policy. |
| G-02 | Full subscription renewal, failed billing, reactivation, and later cancellation behavior. | Frozen `SUBSCRIPTION LIFECYCLE.md`, sections 5–9. | Trial expiry, grace, notices, and access controls exist; later lifecycle remains planned. |
| G-03 | A public landing page implemented from the documented plan. | Frozen `tasks/09.md`. | Documentation-only landing-page proposal. |
| G-04 | A single approved build order for core studio-management requirements. | Frozen `tasks/10.md` and core-studio reference. | Many routes and permissions exist, but the requirement set remains a planning source. |
| G-05 | An error-free owner Services screen. | Frozen progress issue ISS-002. | Current Blade view can fail on an already-cast array. |

## Built but never written down clearly

| # | Existing behavior | Evidence | Why it matters |
|---|---|---|---|
| X-01 | Notification read-state and unread-count routes are cross-portal infrastructure. | `routes/web.php`, lines 33–40. | Notifications affect every role and should not be mistaken for a future-only feature. |
| X-02 | Procurement has a multi-state service with finance-specific actions for review, ordering, delivery, returns, replacements, and payment. | `app/Services/ProcurementWorkflowService.php`, around lines 1184–1193. | The old plain summary barely exposes this operational surface. |
| X-03 | Role assignment is studio-scoped through roles, permissions, and the user-role pivot. | `app/Models/UserModel.php`, role and permission methods; RBAC migrations. | Visibility and authority need to be documented together. |
| X-04 | Subscription commands and middleware actively enforce part of the policy. | `app/Console/Commands/` and `app/Http/Middleware/EnforceStudioSubscriptionAccess.php`. | Historical “unresolved” wording is stale for the delivered slice. |
| X-05 | The assistant supports owner configuration and conversation history, not only a fixed chat reply. | `routes/web.php`, lines 53–61; chatbot controllers and service. | The system surface is broader than the early roadmap description. |

## Where they contradict each other

| # | Documents say | Working files or later evidence show | Resolution in this analysis |
|---|---|---|---|
| K-01 | Some old progress and risk notes say subscription access is unresolved. | The dated QST-002 decision and current expiry/access code describe the seven-day grace and restriction slice as delivered. | Treat QST-002 as resolved for that slice; retain renewal questions separately as Q-02. |
| K-02 | The old task index says ten user-authored prompts and an earlier record says eight. | The frozen source set contains twelve prompts. | Track all twelve; old counts are historical evidence, not a deletion instruction. |
| K-03 | The old README names paired technical/plain trees as canonical. | Workflow v3 specifies one linked plain-language run with optional detailed parts and screens. | The v3 run becomes canonical; old trees remain frozen until approval. |
| K-04 | Historical status labels use Completed, Planned, and In Progress without one stable meaning. | Workflow v3 distinguishes Already there, Finished, Being worked on, Blocked, and Unclear. | Use v3 statuses and retain historical wording only in provenance notes. |

## What this means for what happens next

The first useful work is not another feature proposal. It is a dependable evidence base: preserve the source, record the current behavior, make the role and booking boundaries legible, then order the remaining work around policy blockers and current defects. That is the purpose of [[07 - DEVELOPMENT ROADMAP]].
