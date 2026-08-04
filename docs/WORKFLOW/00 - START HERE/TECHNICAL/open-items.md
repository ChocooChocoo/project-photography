# Open Items

> **In plain terms:** These are the assumptions and unanswered business questions that affect what the project can safely claim or build next. They stay open until evidence or an owner decision resolves them.

### QST-001 — Photographer cancellation policy

**Context.** Assignment cancellation is recorded, but the policy choices are not implemented as a complete remedy workflow. See [ISS-001](../../06%20-%20PROGRESS%20TRACKING/TECHNICAL/issues.md#iss-001--photographer-cancellation-has-no-approved-remedy-workflow).
**Needs an answer.** After an assigned photographer cancels, which remedy, communication deadline, and financial outcome are approved?
**Owner.** Unassigned.
**Blocks.** Any cancellation-remedy implementation.

### QST-002 — Subscription access policy

**Status.** Resolved on 2026-08-03 for roadmap items 10.4–10.6.
**Decision.** Trials remain card-free and there is no free tier. Billing is studio-scoped. Every activated trial or paid plan receives seven days of grace after its contractual deadline. Active and grace studios retain commercial access. Expired and never-subscribed studios are delisted and cannot accept new bookings or commercial writes, while their data, reports, history, profile, notifications, and subscription management remain available. Paid bookings already in progress may be fulfilled. Studio photographers retain only that paid-booking fulfillment access; HR and finance are read-only. `owner-super-admin` follows the owner restrictions.
**Still open elsewhere.** Renewal, card-on-file conversion, webhook-driven failed renewals, cancellation, and reactivation remain roadmap items 10.7–10.9 and do not reopen this access decision.

### ASM-001 — Historical delivery status

**Assumption.** Where an old task record describes code that is still present and exercised by tests, it is treated as completed; otherwise this documentation reports the current observable behavior only.
**Basis.** Current repository source and recorded automated test evidence.
**Consequence if wrong.** A historical task may be shown with an incorrect completion status.
**Verify by.** Compare the task acceptance criteria with current source and fresh test evidence.
