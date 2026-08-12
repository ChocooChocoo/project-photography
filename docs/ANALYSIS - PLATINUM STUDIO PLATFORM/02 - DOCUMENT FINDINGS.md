# 02 - DOCUMENT FINDINGS

[[00 - START HERE|Back to start]] · Previous: [[01 - OVERVIEW]] · Next: [[03 - CODE FINDINGS]]

## What was read

The complete frozen set under `MATERIAL/LEGACY DOCUMENTATION` was read: the old analysis brief, technical and plain-language analyses, requirements, roadmaps, progress records, testing records, diagrams, references, and twelve task prompts. The Workflow v3 example and references define the structure and language rules; they are methodology, not project evidence.

## What the documents say the system must do

| # | Requirement | Source |
|---|---|---|
| D-01 | Provide a photography marketplace for studios, freelancers, clients, and staff. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - OLD/01-ANALYSIS/NON TECHNICAL ANALYSIS.md`, sections 1–4 |
| D-02 | Support registration, verification, studio onboarding, and administrative approval. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/01-requirements/requirements.md` |
| D-03 | Let clients discover services, book, pay, follow the booking, receive gallery work, and review. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - OLD/02-PLANNING/CAPSTONE B IMPLEMENTATION ROADMAP.md`, Phases 2–4 |
| D-04 | Let owners assign photographers and manage booking and gallery delivery. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/02-analysis/process-flows.md` |
| D-05 | Provide role-specific studio HR, finance, and photographer work. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/03-planning/core-studio-management.md` |
| D-06 | Support attendance, leave, overtime, payroll, procurement, and equipment-related workflows. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - OLD/01-ANALYSIS/TECHNICAL ANALYSIS.md`, sections 5.5–5.8 |
| D-07 | Provide a photography-focused AI assistant with safety controls and a fallback. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - OLD/04-REFERENCE/AI ASSISTANT INTEGRATION.md` |
| D-08 | Manage subscription trials, expiry, grace access, notifications, and later renewal/reactivation work. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - OLD/04-REFERENCE/SUBSCRIPTION LIFECYCLE.md` |
| D-09 | Add a public landing page, but keep that task documentation-only until approved. | `MATERIAL/LEGACY DOCUMENTATION/tasks/09.md` |
| D-10 | Refine core studio-management requirements without silently implementing them. | `MATERIAL/LEGACY DOCUMENTATION/tasks/10.md` |

## Rules and limits

- Role and permission boundaries must be enforced by the application and its studio-scoped role records, not only by hiding navigation.
- Payment credentials and provider configuration must remain server-side configuration; secrets must not appear in documentation.
- Public media must use the documented storage contract and relative paths.
- A proposed feature remains proposed until its approval and implementation evidence are clear.
- A cancellation remedy must account for the client, studio, photographer, date, notifications, and money together.
- Expiry handling must distinguish studio access from retained records and paid bookings already in progress.

Sources: `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/03-planning/plan.md`, `08-references/ai-assistant-integration.md`, `08-references/subscription-lifecycle.md`, and `ANALYSIS - OLD/04-REFERENCE/PHOTOGRAPHER CANCELLATION CONTINGENCY.md`.

## Decisions already made

| Decision | Evidence |
|---|---|
| The platform is a Laravel server-rendered application rather than an SPA. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/00-overview/scope.md` |
| Subscription access is studio-scoped, with seven days of grace after the contractual deadline; expired studios lose new commercial access while retaining records and paid-booking fulfilment access. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/00-overview/open-items.md`, QST-002 |
| The assistant is photography-focused and must validate input, output, credentials, ownership, rate, and failure behavior. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - OLD/04-REFERENCE/AI ASSISTANT INTEGRATION.md`, sections 4–9 |
| Photographer cancellation requires a policy decision before a complete remedy is built. | `MATERIAL/LEGACY DOCUMENTATION/ANALYSIS - TECHNICAL/00-overview/open-items.md`, QST-001 |
| The documentation phase does not itself change application behavior. | `MATERIAL/LEGACY DOCUMENTATION/tasks/07.md`, `08.md`, `09.md`, and `10.md` |

## Where the documents disagree

| # | Written version one | Written version two | Consequence |
|---|---|---|---|
| C-01 | The old technical progress says subscription grace and access decisions were resolved on 3 August 2026. | Older gap and risk notes still describe subscription access as unresolved. | The current code and dated decision are separated from stale historical wording in [[04 - COMBINED FINDINGS]]. |
| C-02 | The old task registry lists ten prompts. | The current `docs/tasks` set contains twelve prompts. | All twelve are preserved and tracked in [[09 - TASK TRACKER]]. |
| C-03 | The old root index presents Workflow v2 and paired technical/plain trees as canonical. | Workflow v3 defines one plain-language analysis run with optional parts and screens. | The v3 front door is canonical after this migration; the old index remains only in the frozen evidence copy. |
| C-04 | Historical roadmap records use twelve numbered phases. | Workflow v3 requires outcome-based phases and fresh `R-` numbers. | The old phase names remain provenance; the new roadmap uses six dependency-based phases. |

## What the documents leave unsaid

The documents do not settle the questions listed in [[00 - START HERE#Open questions]]. In particular, they do not choose a paid-booking cancellation remedy, renewal and reactivation policy, landing-page approval, or one final execution order for all core studio-management requirements.
