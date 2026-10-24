[← Previous: Phase 8](./phase-8.md)

**Status:** 🔲 Not Started

# Phase 9: Acceptance re-run and documentation

**Goal:** The full test suite passes, the five failing acceptance tests pass, and the project record shows T02 as evidence-backed current behavior.

## Dependencies

- Phases 1–8 complete

## Tasks

- [ ] Run `composer test` and fix any failure (systematic debugging first).
- [ ] Re-run the acceptance checklist tests: Full payment, Preparation and equipment, Printed photos: pickup and delivery, Finishing a booking, Full walkthrough: freelancer booking; then the untested "Photos, videos and extras on packages".
- [ ] Record the external item: the acceptance-test sheet is outside this repo; testers need edit access and a notes column for "Doesn't work" results. No app code.
- [ ] Freeze the T02 prompt in `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/MATERIAL/LEGACY DOCUMENTATION/tasks/`.
- [ ] Add T02 to `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/09 - TASK TRACKER.md` with source, scope and status.
- [ ] Update parts `P-04 BOOKING AND PAYMENT`, `P-05 PHOTOGRAPHER ASSIGNMENT AND CANCELLATION`, `P-06 GALLERIES AND REVIEWS`, `P-09 PROCUREMENT AND EQUIPMENT`, `P-11 NOTIFICATIONS` with the shipped behavior and tests.
- [ ] Record the T01/T02 downpayment reversal as a decision.

## Done when

- [ ] `composer test` exits 0.
- [ ] All five previously failing acceptance tests are marked pass in the checklist.
- [ ] `09 - TASK TRACKER.md` lists T02 with a status that matches this plan.

---

[← Previous: Phase 8](./phase-8.md)
