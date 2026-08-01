# Templates

> Reference file. Every artifact format in one place — copy from here rather than reinventing.
> Part of the System Analysis Workflow — see `00 - START HERE.md`.

Each identifier is declared as a heading so it can be linked: `[REQ-004](../01-requirements/requirements.md#req-004)`

---

## Requirement — `01-requirements/requirements.md`

```markdown
### REQ-004 — Users authenticate with email and password
**Category** Functional · **Priority** Must · **Source** User request, 2026-07-31
**Statement.** The system shall authenticate users by email address and password.
**Why.** Every other access control depends on knowing who the user is.
**Acceptance.** A valid credential pair returns a session; an invalid pair returns a
generic failure and increments the throttle counter.
**Traces to** ANL-009 · GAP-005 · TASK-014 · TEST-011 · DEC-006
```

---

## Analysis finding — `02-analysis/`

```markdown
### ANL-009 — Session handling duplicated across three controllers
**Area.** Architecture / Authentication
**Observation.** Session creation logic appears in three controllers with divergent TTL handling.
**Evidence.** `LoginController.php:42-88`, `ApiTokenController.php:31-70`, `AdminController.php:110-140`
**Impact.** One policy change needs three edits; the copies have already diverged.
**Classification.** Technical debt, medium severity.
**Related** GAP-005 · TASK-014
```

No evidence means it isn't a finding — log it as an assumption instead.

---

## Gap — `02-analysis/gaps.md`

```markdown
### GAP-005 — No centralized authentication module
**Now** Logic duplicated in three controllers → **Target** One `src/auth/` module
**Severity** High · **Effort** Medium
**From** ANL-009 · **Satisfies** REQ-004 · **Resolved by** TASK-014
```

---

## Task record — `04-tasks/records/task-014.md`

```markdown
# TASK-014 — 03.md — Implement the authentication module

| Field | Value |
|---|---|
| Source file | [`prompts/tasks/03.md`](../../../prompts/tasks/03.md) |
| Status | Ready · 0% · Priority High · Owner unassigned |
| Phase | Development · Milestone MIL-002 · Target 2026-08-05 |

**Objective** _(quoted from `03.md`)_
> Replace the three copies of session handling with one auth module.

**Inputs** — REQ-004 · ANL-009 · `src/auth/session.ts` · env `AUTH_SECRET`, `SESSION_TTL`
**Outputs** — new `src/auth/` module · `sessions` migration · TEST-011 passing
**Depends on** — TASK-011, TASK-012 (confirmed) · **Blocks** — TASK-015
**Files** — `src/auth/**` created · `src/middleware/session.ts` modified

**Analysis** — Why: three divergent copies (ANL-009). Approach: new module, not a
shared trait — DEC-006. Trade-off: three controllers must migrate. Risk: RSK-003.

**Acceptance criteria**
- [ ] AC-1 — Valid credentials create a session with the configured TTL _(from `03.md`)_
- [ ] AC-2 — Invalid credentials return a generic failure and increment the throttle
- [ ] AC-3 — All three controllers use the new module _— proposed, unconfirmed_

**Done when** — built · criteria met · tests pass · docs and diagrams updated ·
links updated · evidence recorded · no open blocker

**Evidence** — commit `—` · test run `—` · review `—`

**Notes** — 2026-07-31 Registered from `03.md`, matched to GAP-005.
```

---

## Task index — `04-tasks/index.md`

```markdown
# Task Index
_Source: `prompts/tasks/` · 18 registered · synced 2026-07-31_

| Task | ID | Title | Status | % | Depends on | File |
|---|---|---|---|---|---|---|
| 01 | TASK-011 | Scaffold the Laravel project | Completed | 100% | — | [`01.md`](../../prompts/tasks/01.md) |
| 03 | TASK-014 | Authentication module | Ready | 0% | TASK-011, TASK-012 | [`03.md`](../../prompts/tasks/03.md) |

**Next up:** TASK-014 — `03.md`, dependencies satisfied.
**Health:** 2 dependencies unconfirmed · 1 task with no linked requirement · 3 proposals awaiting review.
```

---

## Proposed task — `04-tasks/proposed.md`

```markdown
## P-01 — Add rate limiting to the request submission endpoint
**Implied by** REQ-019, GAP-008 · **Position** after TASK-015 · **Size** Small · **QST-007**

**Why it seems needed.** REQ-019 requires abuse protection on public submission.
No registered task covers it, and the endpoint is public in the target architecture.

**If you accept:** add it to `prompts/tasks/` under your own numbering and tell me.

**Status:** Awaiting review.
```

---

## Progress tracker — `05-progress/tracker.md`

```markdown
# Progress Tracker
_Synced 2026-07-31 · 34% complete (11 of 32) · Phase: Development_
**Next action:** begin TASK-014

| ID | Task | Owner | Status | % | Target | Blockers | Latest | Evidence |
|---|---|---|---|---|---|---|---|---|
| TASK-011 | Scaffold project | cyro | Completed | 100% | 07-22 | — | Done | commit `a3f21` |
| TASK-016 | Driver navigation | — | Blocked | 20% | 08-07 | ISS-002 | Test env down | — |

Not Started 8 · Ready 3 · In Progress 2 · Blocked 1 · Testing 1 · Completed 11
```

---

## Roadmap phase — `05-roadmap/roadmap.md`

```markdown
## Phase 6 — Development

| Field | Value |
|---|---|
| Milestone | MIL-002 · **Status** In Progress · 40% · **Target** 2026-08-15 |
| Entry | Architecture agreed; environment reproducible |
| Exit | All phase tasks Completed; module tests passing |
| Overlaps with | Phase 8 — testing starts as each module lands |

**Tasks** — TASK-014, TASK-015, TASK-016
**Requirements** — REQ-004, REQ-007, REQ-012
**Deliverables** — auth module · dispatch module · validation layer
**Depends on** — Phase 5 complete · DEC-006 settled · **Risks** — RSK-003
```

---

## Decision — `05-progress/decisions.md`

```markdown
### DEC-006 — Build a new auth module rather than extend the controllers
**Date** 2026-07-31 · **Status** Accepted
**Context.** ANL-009, GAP-005
**Options.** (1) Extend LoginController (2) Extract a shared trait (3) New `src/auth/` module
**Chose** 3, because options 1 and 2 preserve the divergent TTL handling REQ-004 forbids.
**Consequence.** Three controllers must be migrated — TASK-014.
```

---

## Risk — `05-progress/risks.md`

```markdown
### RSK-003 — Session migration may log out active users
**Likelihood** Medium · **Impact** High · **Owner** cyro · **Status** Open
**Trigger.** Deploying the TASK-014 migration.
**Mitigation.** Dual-read during a transition window; deploy at low traffic.
**Contingency.** `db/migrations/rollback_sessions.sql`
**Related** TASK-014 · DEC-006
```

---

## Issue — `05-progress/issues.md`

```markdown
### ISS-002 — Test environment database unavailable
**Opened** 2026-07-30 · **Severity** Blocker · **Blocks** TASK-016
**Impact.** Integration tests can't run; TASK-016 stuck at 20%.
**Next action.** Ask IT to restore the staging database.
**Resolution.** —
```

---

## Test case — `06-testing/test-cases.md`

```markdown
### TEST-011 — Valid credentials create a session
**Type** Integration · **Verifies** REQ-004 · **Covers** TASK-014
**Automated** `tests/auth/login.test.ts` · **Status** Not Run

**Preconditions.** A user exists with a known password.
**Steps.** 1. POST valid credentials to `/login`. 2. Inspect response and session store.
**Expected.** 200 response; session row created; TTL equals `SESSION_TTL`.
**Actual.** —
```

---

## Traceability matrix — `01-requirements/traceability.md`

```markdown
| REQ | Requirement | Analysis | Gap | Tasks | Tests | Status |
|---|---|---|---|---|---|---|
| REQ-004 | Email/password auth | ANL-009 | GAP-005 | TASK-014, TASK-017 | TEST-011 | In Progress |

**Coverage gaps:** requirements with no task — REQ-019 (proposed P-01) ·
requirements with no test — REQ-018 · tasks with no requirement — TASK-016
```

---

## Open items — `00-overview/open-items.md`

```markdown
### ASM-002 — Assuming MySQL 8, not 5.7
Based on `docker-compose.yml` specifying `mysql:8.0`. If production runs 5.7, the
JSON column in the target schema won't work. **Affects** TASK-012.

### QST-003 — REQ-004 conflicts with REQ-011
REQ-004 requires a 30-minute session TTL. REQ-011 requires drivers to stay logged
in for a full shift. Both cannot hold for the same user.
**Options.** (a) Role-based TTL (b) Refresh tokens for drivers (c) Drop the limit
**Blocks.** Any task touching session handling. **Needs.** Your decision.
```

---

## Plain-language status — `05-progress/status-plain.md`

```markdown
# Where the project stands
_Updated 31 July · about a third of the work is done_

Sign-in and the database groundwork are finished and tested. The authentication
rebuild starts next and should take about a week. Driver navigation is on hold
until the test server is back — nothing else is waiting on it. Everything else
is on track for 22 August.
```

Same numbers and dates as the technical version. Simpler words are fine; softer facts are not.

---

## Glossary — `08-references/glossary.md`

```markdown
| Technical term | In plain words | Why it matters |
|---|---|---|
| Session TTL | How long a login lasts before signing in again | Balances convenience against account safety |
| Migration | A scripted, repeatable database change | Lets the database be rebuilt identically anywhere |
| RBAC | Deciding what each kind of user is allowed to do | Keeps students, faculty, and admins out of each other's data |
```

Any technical term appearing in a non-technical document needs an entry here.
