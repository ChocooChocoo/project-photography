# Open Items

> **In plain terms:** These are the assumptions and unanswered business questions that affect what the project can safely claim or build next. They stay open until evidence or an owner decision resolves them.

### QST-001 — Photographer cancellation policy

**Context.** Assignment cancellation is recorded, but the policy choices are not implemented as a complete remedy workflow. See [ISS-001](../05-progress/issues.md#iss-001--photographer-cancellation-has-no-approved-remedy-workflow).
**Needs an answer.** After an assigned photographer cancels, which remedy, communication deadline, and financial outcome are approved?
**Owner.** Unassigned.
**Blocks.** Any cancellation-remedy implementation.

### QST-002 — Subscription access policy

**Context.** Trial fields and subscription states exist, but a complete access-enforcement policy is not approved. See [RSK-001](../05-progress/risks.md#rsk-001--subscription-state-and-access-can-diverge).
**Needs an answer.** What access, renewal, grace-period, and data-retention rules apply when a studio trial or paid plan ends?
**Owner.** Unassigned.
**Blocks.** Any subscription access-enforcement implementation.

### ASM-001 — Historical delivery status

**Assumption.** Where an old task record describes code that is still present and exercised by tests, it is treated as completed; otherwise this documentation reports the current observable behavior only.
**Basis.** Current repository source and recorded automated test evidence.
**Consequence if wrong.** A historical task may be shown with an incorrect completion status.
**Verify by.** Compare the task acceptance criteria with current source and fresh test evidence.
