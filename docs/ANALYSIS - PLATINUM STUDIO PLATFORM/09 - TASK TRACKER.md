# 09 - TASK TRACKER

[Back to start](00%20-%20START%20HERE.md) · Previous: [08 - ROADMAP TRACKER](08%20-%20ROADMAP%20TRACKER.md) · Next: [10 - WORD LIST](10%20-%20WORD%20LIST.md)

**Last checked:** 24 October 2026 (T-13 client-defects entry; historical task statuses retained)

Every task below is a piece of work from a frozen user-authored prompt. The prompt remains exact under `MATERIAL/LEGACY DOCUMENTATION/tasks/`.

| # | The task | Where it came from | What it refers to | Serves | Status |
|---|---|---|---|---|---|
| T-01 | Execute and track the initial roadmap phases. | `MATERIAL/LEGACY DOCUMENTATION/tasks/01.md` | Historical roadmap execution and progress record. | [R-01](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-1--make-the-foundations-dependable) | 🔵 |
| T-02 | Continue Phase 3 core feature implementation. | `MATERIAL/LEGACY DOCUMENTATION/tasks/02.md` | Historical feature work whose current scope is partly present. | [R-06](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-2--complete-booking-and-media-journeys) | 🟨 |
| T-03 | Reset and seed localized relational data. | `MATERIAL/LEGACY DOCUMENTATION/tasks/03.md` | Database seed and protected-record requirements. | [R-01](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-1--make-the-foundations-dependable) | 🔵 |
| T-04 | Secure and focus the AI assistant. | `MATERIAL/LEGACY DOCUMENTATION/tasks/04.md` | Guardrails, validation, scope, credentials, fallback, and limits. | [R-17](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-5--automate-and-protect-ongoing-work) | 🔵 |
| T-05 | Restore and verify media display. | `MATERIAL/LEGACY DOCUMENTATION/tasks/05.md` | Public media storage and read/write consistency. | [R-01](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-1--make-the-foundations-dependable) | 🔵 |
| T-06 | Reset fresh data while preserving users and locations. | `MATERIAL/LEGACY DOCUMENTATION/tasks/06.md` | Seed integrity and protected tables. | [R-01](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-1--make-the-foundations-dependable) | 🔵 |
| T-07 | Define photographer cancellation contingencies. | `MATERIAL/LEGACY DOCUMENTATION/tasks/07.md` | Options and unresolved policy for paid-booking cancellation. | [R-20](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-6--resolve-cancellation-and-subscription-lifecycles) | 🔵 |
| T-08 | Evaluate the subscription lifecycle. | `MATERIAL/LEGACY DOCUMENTATION/tasks/08.md` | Trial, expiry, grace, renewal, access, and reactivation. | [R-19](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-6--resolve-cancellation-and-subscription-lifecycles) | 🔵 |
| T-09 | Document the Bootstrap-based landing page. | `MATERIAL/LEGACY DOCUMENTATION/tasks/09.md` | Planned public entry page, explicitly documentation-only. | [R-13](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-4--expand-discovery-and-advanced-operations) | 🔵 |
| T-10 | Refine core studio-management requirements. | `MATERIAL/LEGACY DOCUMENTATION/tasks/10.md` | Onboarding, roles, records, attendance, booking, pricing, and approval requirements. | [R-09](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-3--make-each-roles-daily-work-complete) | ✅ |
| T-11 | Implement Phase 4 workflow improvements through subscription-rank transparency. | `MATERIAL/LEGACY DOCUMENTATION/tasks/11.md` | Historical discovery and workflow proposal. | [R-14](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-4--expand-discovery-and-advanced-operations) | ✅ |
| T-12 | Resolve municipality permission behavior and standardize task documentation. | `MATERIAL/LEGACY DOCUMENTATION/tasks/12.md` | Owner studio creation lookup and documentation consistency. | [R-04](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-1--make-the-foundations-dependable) | 🔵 |
| T-13 | Fix the client-reported T02 defects and build the requested enhancements. | `MATERIAL/LEGACY DOCUMENTATION/tasks/13.md` (frozen copy of `docs/tasks/T02.md`) | Employees page error, CSRF retry, gallery-approval notification, equipment inventory and confirmation, printed-photo delivery gate and fulfillment rule, downpayment settings, cancellation and refund rules, photo editing pricing, and rescheduling requests. | [R-05](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-2--complete-booking-and-media-journeys), [R-15](07%20-%20DEVELOPMENT%20ROADMAP.md#phase-4--expand-discovery-and-advanced-operations) | ✅ |

## Tasks that serve no roadmap item

Every task in this source set serves one of the roadmap items above. There is no unassigned task row.

## Thesis-panel revisions

| Phase | Scope | Source and verification | Status |
|---|---|---|---|
| 13 | First-login client/owner/freelancer tutorial with replay, password-change priority, client-only studio portfolio page, and confirmed long-name topbar overflow fix. | [Phase 13](../plans/thesis-panel-revisions/phase-13.md): 671 PHP tests / 3342 assertions, 9 JavaScript regressions, build and authenticated browser checks passed. Seven dashboards checked at desktop/phone widths in both themes. | ✅ Locally verified, 14 October 2026; production unverified. |

This entry does not close Phase 1's unreproduced production login issue. Portfolio pages exclude private booking galleries and unavailable studios; existing public image storage remains unchanged. Optional TourGuide and unconfirmed KPI/date-filter/chatbot changes were not added.

## T02 client defects

| Phases | Scope | Source and verification | Status |
|---|---|---|---|
| 1–9 | All seven reported defects and five requested enhancements from T-13. | [T02 plan](../implementation-plan/t02-client-defects/phase-9.md): 721 PHP tests / 3661 assertions and 21 JavaScript tests passed, and the build succeeded. A browser re-run on 24 October 2026 passed the five previously failing acceptance tests (Full payment, Preparation and equipment, Printed photos: pickup and delivery, Finishing a booking, Full walkthrough: freelancer booking) and the untested "Photos, videos and extras on packages". | ✅ Locally verified, 24 October 2026; production unverified. |
| 10 | Thesis-panel failures not covered by T02: S06 "Photos, videos and extras on packages", S19 "Freelancer filling in" and RC "Full walkthrough: freelancer booking". A second studio can now get its own plan and packages, owners can invite freelancers to any studio they own, fallback freelancer access lasts until completion, and package videos are hidden. | [Phase 10](../implementation-plan/t02-client-defects/phase-10.md): 725 PHP tests / 3676 assertions and 21 JavaScript tests passed, and the build succeeded. S06, S19 and RC passed a browser re-run on 24 October 2026. | ✅ Locally verified, 24 October 2026; production unverified. |

The acceptance-test sheet is outside this repo, and its testers must still mark the five tests as passing. They also need edit access and a notes column for "Doesn't work" results; this involves no app code. Open questions from the re-run: a freelancer booking can be completed without a delivered gallery, unlike a studio booking; and a booking cannot be rescheduled once the photographer has accepted it. T-13 reverses the T01 instruction to disable downpayments; see [decisions](02%20-%20DOCUMENT%20FINDINGS.md#decisions-already-made).

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
