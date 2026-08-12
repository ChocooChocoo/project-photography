# 00 - START HERE

Next: [[01 - OVERVIEW]]

**What this is about:** Platinum Studio Platform
**Written:** 11 August 2026
**Last updated:** 11 August 2026

## What was handed over

| What | Kind | Where it came from | Read? |
|---|---|---|---|
| Legacy analysis trees | Documentation | docs/ANALYSIS - OLD, docs/ANALYSIS - PLAIN, and docs/ANALYSIS - TECHNICAL; frozen under MATERIAL/LEGACY DOCUMENTATION | Yes — in full |
| User task prompts | Documentation | docs/tasks; frozen under MATERIAL/LEGACY DOCUMENTATION/tasks | Yes — in full |
| Laravel application | Source code | app, routes, database, resources/views, config, and tests in this project | Main entrypoints and high-traffic workflows read; generated and dependency folders excluded |
| Workflow Version 3 | Methodology | prompt/WORKFLOW v3 | Yes — in full; unchanged |

The old record contains 109 files. Every one has an exact frozen copy and a row in [[MATERIAL/LEGACY COVERAGE|the coverage ledger]]. The current code is the evidence for what exists now; old notes remain evidence of what was previously promised, observed, or planned.

## The short version

Platinum is a Laravel web platform for photography studios, freelancers, clients, and studio staff. It covers discovery, bookings, payment, photographer assignment, galleries, reviews, studio operations, procurement, payroll, subscriptions, notifications, and a photography-focused assistant. The application already contains substantial working flows, but the old documentation mixes current behavior, historical status, proposed work, and unresolved policy decisions. This v3 analysis separates those categories, preserves the old text, and makes every roadmap and task traceable.

## Everything in this analysis

| File | What it holds |
|---|---|
| [[01 - OVERVIEW]] | What the platform is and who uses it |
| [[02 - DOCUMENT FINDINGS]] | What the supplied documentation says |
| [[03 - CODE FINDINGS]] | What the current working files do |
| [[04 - COMBINED FINDINGS]] | Where written intent and current behavior agree or differ |
| [[05 - SYSTEM ARCHITECTURE]] | The current parts and the proposed direction |
| [[06 - DIAGRAMS]] | Mermaid pictures of the current and proposed flows |
| [[07 - DEVELOPMENT ROADMAP]] | Six outcome-based development phases |
| [[08 - ROADMAP TRACKER]] | Status of every roadmap item |
| [[09 - TASK TRACKER]] | The twelve user-authored tasks and their origins |
| [[10 - WORD LIST]] | Plain meanings of unavoidable technical words |
| [[11 - PARTS IN DETAIL]] | The twelve detailed system-part pages |
| [[12 - SCREENS BY ROLE]] | Derived screen map for the seven portal roles |

## How to read this

Read [[04 - COMBINED FINDINGS]] for the most useful truth about promises versus the current application. Read [[07 - DEVELOPMENT ROADMAP]] for the build order and [[08 - ROADMAP TRACKER]] for status. Read [[05 - SYSTEM ARCHITECTURE]] and [[06 - DIAGRAMS]] for the arrangement. Open [[11 - PARTS IN DETAIL]] or [[12 - SCREENS BY ROLE]] when you need operational or role-specific detail.

## Status legend

| Emoji | Means |
|---|---|
| ✅ | Finished — built, checked, working |
| 🟨 | Being worked on — started, not finished |
| ⭕ | Not started — waiting its turn |
| ❌ | Blocked — something is stopping it |
| 🔵 | Already there — found in the supplied material, built before this plan |
| ⬜ | Dropped — decided against, kept for the record |
| ❓ | Unclear — the material does not say |

## Open questions

| # | Question | Why it matters | Who can answer |
|---|---|---|---|
| Q-01 | What approved remedy applies when a photographer cancels after payment? | The code records assignment changes, but the old contingency record leaves substitution, rescheduling, refund, credit, timing, and responsibility unresolved. | Product owner and finance |
| Q-02 | What is the approved renewal, failed-payment, cancellation, and reactivation policy? | Grace and expiry access are implemented, but card-on-file renewal and later lifecycle decisions remain planned. | Product owner and finance |
| Q-03 | Is the public landing page approved for implementation? | The old task is explicitly documentation-only and contains no approved implementation order. | Product owner |
| Q-04 | Which core studio-management requirements are approved for build, and in what order? | The requirements cover onboarding, permits, roles, attendance, pricing, and archive behavior, but do not establish one approved delivery sequence. | Product owner |
| Q-05 | When will the owner Services page defect be repaired? | The current record reports an HTTP 500 caused by decoding an already-cast array. | Engineering owner |
| Q-06 | What remains in the historical Phase 3 task? | Its prompt is still marked in progress while parts of the feature set already exist. | Product owner and engineering owner |
| Q-07 | Which payment-provider events and business outcomes are authoritative? | PayMongo and Stripe webhook routes exist, but provider behavior and operational policy must stay distinct. | Product owner and finance |
| Q-08 | Which gallery, review, and portfolio rules are final? | Code supports draft, publish, portfolio, and review surfaces; moderation and visibility rules vary across historical notes. | Product owner |
| Q-09 | Which roles may perform each cross-portal action? | Middleware and studio-scoped permissions exist, but the old notes contain broad and sometimes conflicting role claims. | Product owner and security owner |
| Q-10 | Which attendance, overtime, and payroll actions are automatic versus manually approved? | The application has routes and services, while the old roadmap proposes additional automation. | Studio operations owner |
| Q-11 | What financial state should a paid booking enter after cancellation? | This is the concrete business consequence behind the photographer-cancellation gap. | Product owner and finance |
| Q-12 | Which historical completion dates should be retained as official delivery history? | Old progress notes and current source do not always use the same status vocabulary or date. | Project owner |

## Evidence files

- [[MATERIAL/LEGACY COVERAGE|Legacy coverage ledger]] maps all 109 sources.
- [[MATERIAL/LEGACY DOCUMENTATION/SHA256SUMS|Frozen-source hashes]] proves the copies match their sources.

## What changed

| Date | What changed |
|---|---|
| 11 August 2026 | Created the Workflow v3 canonical analysis beside the legacy trees; froze all source documentation; added coverage, current-code findings, reconciliation, roadmap, trackers, parts, and role screens. Legacy deletion is intentionally not performed. |
