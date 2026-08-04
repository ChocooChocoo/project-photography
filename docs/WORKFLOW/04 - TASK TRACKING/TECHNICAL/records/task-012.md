# TASK-012 — Owner Studio Creation Municipality Access

> **In plain terms:** This task records the authorization correction for the municipality lookup used during first-studio registration.

**Source:** [`prompt/tasks/12.md`](../../../../../prompt/tasks/12.md)
**Status:** Completed on 2026-08-04.
**Scope:** Removed the `owner.studios.manage` middleware requirement from the read-only `owner.studio.get-barangays` route. Authentication, owner-role validation, subscription access, and studio-registration limits remain enforced by the surrounding route group.
**Evidence:** [plain-language audit](../../../../../prompt/audits/2026-08-04/12-owner-studio-creation-audit.md), `StudioCreationTest`, complete automated test run, route listing, and final change review.
**Boundary:** Existing studio listing, editing, updating, and deletion routes retain their `owner.studios.manage` middleware. No roadmap document was changed.
