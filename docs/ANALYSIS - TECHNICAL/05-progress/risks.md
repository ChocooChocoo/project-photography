# Risks

> **In plain terms:** These are specific events that could cause harm if unresolved policy questions are encountered in operation. Each entry states what triggers it and how the project currently limits the exposure.

### RSK-001 — Subscription state and access can diverge

**Trigger.** A studio trial or paid plan ends before access, renewal, grace-period, and retention rules are approved.
**Probability.** Not assessed. · **Impact.** Subscription data may not match the access users are expected to have.
**Mitigation.** Do not implement enforcement until [QST-002](../00-overview/open-items.md#qst-002--subscription-access-policy) is answered.
**Owner.** Unassigned. · **Review.** When QST-002 is answered.

### RSK-002 — Cancellation can leave a paid booking unresolved

**Trigger.** An assigned photographer cancels after a client has paid.
**Probability.** Not assessed. · **Impact.** The booking can require operational and financial decisions that are not yet approved.
**Mitigation.** Do not describe or build a remedy until [QST-001](../00-overview/open-items.md#qst-001--photographer-cancellation-policy) is answered.
**Owner.** Unassigned. · **Review.** When QST-001 is answered.
