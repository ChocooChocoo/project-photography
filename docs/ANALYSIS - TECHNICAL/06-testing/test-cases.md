# Test Cases and Coverage

> **In plain terms:** These checks cover important current workflows and safety rules. They do not settle business policies that still need an owner decision.

| ID | Area | Evidence |
| --- | --- | --- |
| TEST-001 | Fresh seed preserves protected records and avoids media references | `tests/Feature/FreshSeedContractTest.php` |
| TEST-002 | Seeded locations and identities remain valid | `tests/Feature/SeedIntegrityTest.php` |
| TEST-003 | Public media storage aligns write and read paths | `tests/Feature/MediaStorageTest.php` |
| TEST-004 | Photography assistant behavior and context | `tests/Feature/ChatbotFeatureTest.php` |
| TEST-005 | Assistant injection, secret, rate-limit, failure, and ownership guardrails | `tests/Feature/ChatbotAiGuardrailsTest.php` |
| TEST-006 | Payment webhooks are registered and reject invalid signatures | `tests/Feature/Payment/WebhookTest.php` |
| TEST-007 | Dashboard, payroll, procurement, and route registration coverage | `tests/Feature/`, `tests/Unit/` |

These tests show current automated coverage; they do not prove every business policy is approved.
