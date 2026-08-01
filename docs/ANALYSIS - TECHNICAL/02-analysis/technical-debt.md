# Technical Debt and Known Limits

> **In plain terms:** A few areas need a business decision before they can safely be finished; they are recorded as open work, not promised features.

### ANL-006 — Historical roadmap is not an executable contract

**Area:** Documentation quality.  
**Observation:** Earlier documents mixed completed work, recommendations, and policy proposals. This workflow replaces them with evidence-linked status and leaves unapproved items open.  
**Evidence:** Retired legacy documentation review; [change log](../05-progress/change-log.md).

### ANL-012 — Cancellation outcome is incomplete

**Area:** Workflow limit.  
**Observation:** An assigned photographer can cancel, but a complete approved remedy—reassignment, client communication, refunds or credits, and deadlines—is not established.  
**Evidence:** Assignment cancellation routes/controllers and [QST-001](../00-overview/open-items.md#qst-001--photographer-cancellation-policy).

### ANL-013 — Subscription access enforcement is unresolved

**Area:** Subscription lifecycle.  
**Observation:** Subscription plans and trial fields exist, but the business rules for expiry, grace periods, renewal, and restricted access are not fully approved or enforced as one lifecycle.  
**Evidence:** Subscription migrations/controllers and [QST-002](../00-overview/open-items.md#qst-002--subscription-access-policy).
