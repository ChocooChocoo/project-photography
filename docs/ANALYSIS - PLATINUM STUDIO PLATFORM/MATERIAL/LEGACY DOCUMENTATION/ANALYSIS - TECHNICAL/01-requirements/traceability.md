# Traceability Matrix

> **In plain terms:** This table connects each request to the evidence behind it, the task that records it, and the proof available. Blank gap cells mean no separate gap record is currently linked.

| Requirement | Analysis | Gap | Tasks | Tests or evidence | Status |
| --- | --- | --- | --- | --- | --- |
| [REQ-001](requirements.md#req-001--track-roadmap-execution) | [ANL-006](../02-analysis/technical-debt.md#anl-006--historical-roadmap-is-not-an-executable-contract) | — | [TASK-001](../04-tasks/records/task-001.md) | [change log](../05-progress/change-log.md) | Completed |
| [REQ-002](requirements.md#req-002--deliver-phase-3-scope) | [ANL-003](../02-analysis/architecture.md#anl-003--portal-boundaries) | — | [TASK-002](../04-tasks/records/task-002.md) | Current code inventory | In Progress |
| [REQ-003](requirements.md#req-003--provide-cavite-localized-fresh-seed-data) | [ANL-004](../02-analysis/database.md#anl-004--seed-data-contracts) | — | [TASK-003](../04-tasks/records/task-003.md) | `SeedIntegrityTest`, `FreshSeedContractTest` | Completed |
| [REQ-004](requirements.md#req-004--provide-a-secure-photography-assistant) | [ANL-005](../02-analysis/security.md#anl-005--chatbot-guardrails) | — | [TASK-004](../04-tasks/records/task-004.md) | `ChatbotFeatureTest`, `ChatbotAiGuardrailsTest` | Completed |
| [REQ-005](requirements.md#req-005--restore-media-delivery) | [ANL-007](../02-analysis/existing-system.md#anl-007--public-media-storage) | — | [TASK-005](../04-tasks/records/task-005.md) | `MediaStorageTest` | Completed |
| [REQ-006](requirements.md#req-006--refresh-seed-data-while-preserving-protected-tables) | [ANL-004](../02-analysis/database.md#anl-004--seed-data-contracts) | — | [TASK-006](../04-tasks/records/task-006.md) | `FreshSeedContractTest` | Completed |
| [REQ-007](requirements.md#req-007--analyze-photographer-cancellation-contingencies) | [ANL-012](../02-analysis/technical-debt.md#anl-012--cancellation-outcome-is-incomplete) | [GAP-001](../02-analysis/gaps.md#gap-001--no-approved-cancellation-remedy-policy) | [TASK-007](../04-tasks/records/task-007.md) | [open item](../00-overview/open-items.md#qst-001--photographer-cancellation-policy) | Completed |
| [REQ-008](requirements.md#req-008--analyze-subscription-lifecycle) | [ANL-013](../02-analysis/technical-debt.md#anl-013--subscription-access-enforcement-is-unresolved) | [GAP-002](../02-analysis/gaps.md#gap-002--subscription-access-policy-is-unresolved) | [TASK-008](../04-tasks/records/task-008.md) | [open item](../00-overview/open-items.md#qst-002--subscription-access-policy) | Completed |
| [REQ-009](requirements.md#req-009--document-a-future-bootstrap-landing-page) | [planned landing page](../03-planning/landing-page.md) | — | [TASK-009](../04-tasks/records/task-009.md) | [technical audit](../../../prompt/audits/09-landing-page-technical-audit.md) | Completed — documentation only |
| [REQ-010](requirements.md#req-010--refine-core-studio-management-requirements) | [planned requirements](../03-planning/core-studio-management.md) | — | [TASK-010](../04-tasks/records/task-010.md) | [technical audit](../../../prompt/audits/10-core-studio-management-technical-audit.md) | Completed — documentation only |

**Coverage gaps:** REQ-002 remains in progress. REQ-007 and REQ-008 have no approved implementation task because QST-001 and QST-002 are unresolved. REQ-009 and REQ-010 are documentation-only and do not claim application tests.
