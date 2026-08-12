# Progress Tracker

> **In plain terms:** This is the current evidence-based project status. Plans remain separate from completed application behavior, and blocked work names the decision it needs.

**Evidence date:** 2026-08-03.
**Automated baseline:** `php artisan test --compact` — 84 passed, 440 assertions.

| Area | Status | Evidence |
| --- | --- | --- |
| Documentation reset | Completed | [MIL-001](../05-roadmap/milestones.md#mil-001--canonical-documentation-reset) |
| Seed integrity | Completed | `FreshSeedContractTest`, `SeedIntegrityTest` |
| Media storage contract | Completed | `MediaStorageTest` |
| Photography assistant | Completed | `ChatbotFeatureTest`, `ChatbotAiGuardrailsTest` |
| Phase 3 historical scope | In Progress | [TASK-002](../04-tasks/records/task-002.md) |
| Cancellation remedy policy | Blocked | [QST-001](../00-overview/open-items.md#qst-001--photographer-cancellation-policy) |
| Subscription expiry mechanics (10.1–10.3) | Completed | Exact trial dates, hourly expiry command, lifecycle regression tests, and browser verification |
| Subscription grace, notifications, and access (10.4–10.6) | Completed | Seven-day grace, lifecycle notices, studio-scoped enforcement, focused tests, and browser verification; [QST-002 resolved](../00-overview/open-items.md#qst-002--subscription-access-policy) |
| Subscription renewal and reactivation (10.7–10.9) | Planned | Card-on-file and Stripe webhook work remain outside the delivered slice |
| Future landing page | Planned | [Task 09 documentation](../03-planning/landing-page.md); no implementation approved |
| Core studio management requirements | Planned | [Task 10 documentation](../03-planning/core-studio-management.md); no implementation order approved |

See the full [delivery history](delivery-history.md), detailed [plain-language progress](status-plain.md), [risks](risks.md), [issues](issues.md), and [change log](change-log.md).
