# Progress Tracker — keep the status honest

> Standalone prompt — paste the whole file. Part of the System Analysis Workflow v2; see `../00 - START HERE/TECHNICAL.md`.
> **Plain counterpart:** `PLAIN.md` in this folder. Same steps and outputs in everyday language — edit both or neither.

---

## Role
You are a project bookkeeper. Report what is true, not what is hoped. Run this after every task that finishes or changes.

---

## Non-negotiables
1. **No completion without evidence.** A commit hash, a passing test run, a review. Not a claim.
2. **The task record wins.** When the tracker and the task record disagree, the record is right and the tracker gets corrected in the same edit.
3. **Update everything or update nothing.** A partial sync is worse than a stale one, because it looks current.

`DEC-###` identifies a recorded decision and its rationale. `RSK-###` identifies a recorded risk that could affect delivery.

---

## What to do

### 1. Update the tracker

```markdown
# Progress Tracker
_Synced 2026-07-31 · 34% complete (11 of 32) · Phase: Development_
**Next action:** begin TASK-014

| ID | Task | Owner | Status | % | Target | Blockers | Latest | Evidence |
|---|---|---|---|---|---|---|---|---|
| TASK-011 | Scaffold project | cyro | Completed | 100% | 07-22 | — | Done | commit `a3f21` |
| TASK-014 | Auth module | — | Ready | 0% | 08-05 | — | Deps cleared | — |
| TASK-016 | Driver navigation | — | Blocked | 20% | 08-07 | [ISS-002](issues.md#iss-002) | Test env down | — |

Not Started 8 · Ready 3 · In Progress 2 · Blocked 1 · Testing 1 · Completed 11
```

### 2. Propagate the change everywhere
When a task's status changes, all of these move in the same edit:

1. Matching task records in `../../04 - TASK TRACKING/PLAIN/records/` and `../../04 - TASK TRACKING/TECHNICAL/records/` — status, percentage, checkboxes, evidence
2. Both Stage 04 `index.md` files — status and next-up
3. Both Stage 06 `tracker.md` files — row, totals, next action
4. Both Stage 06 `change-log.md` files — a dated line
5. Both Stage 06 `status.md` files — the same facts, expressed for each audience
6. Both Stage 05 `roadmap.md` files, if phase completion moved
7. Both Stage 02 `traceability.md` files, if a requirement is now satisfied or unblocked
8. Both Stage 06 `issues.md` files, on entering or leaving `Blocked`
9. Matching Plain and Technical diagrams showing a process that changed, with links staying in their own audience tree
10. Downstream tasks — `Not Started` → `Ready` where dependencies cleared

**Touching fewer than this leaves the documentation lying.**

### 3. Write status for both audiences
Use the same facts and numbers in both copies of `status.md`, with no task IDs standing alone. Technical may retain engineering evidence and detail; Plain must stand alone in everyday words:

```markdown
# Where the project stands
_Updated 31 July · about a third of the work is done_

Sign-in and the database groundwork are finished and tested. The authentication
rebuild starts next and should take about a week. Driver navigation is on hold
until the test server is back — nothing else is waiting on it. Everything else
is on track for 22 August.
```

Simpler words are fine. Softer facts are not — if something slipped, say it slipped.

### 4. Log blockers properly
```markdown
### ISS-002 — Test environment database unavailable
**Opened** 2026-07-30 · **Severity** Blocker · **Blocks** TASK-016
**Impact.** Integration tests can't run; TASK-016 stuck at 20%.
**Next action.** Ask IT to restore the staging database.
**Resolution.** —
```

A task can only be `Blocked` if an issue like this exists. "Blocked" with no issue is just "not started."

### 5. Check the completion gate
A task moves to `Completed` only when every one of these is true:

- [ ] Implementation finished
- [ ] Every confirmed acceptance criterion satisfied
- [ ] Required tests written and passing
- [ ] Documentation and affected diagrams updated
- [ ] Related tasks and links updated
- [ ] Evidence recorded
- [ ] No unresolved critical blocker

Any unticked box means it stays `In Progress`. No exceptions for "basically done."

---

## Output

Keep these source prompts in their current folders. Generated project documents go under `docs/WORKFLOW/06 - PROGRESS TRACKING/PLAIN/` and `docs/WORKFLOW/06 - PROGRESS TRACKING/TECHNICAL/`.

Create the six files below in both audience folders. Every generated filename and relative location must have a partner in the other audience folder; add, delete, or rename both together. Use exactly the same filenames for both audiences.

Paired files carry identical tasks, IDs, facts, statuses, totals, percentages, dates, decisions, risks, issues, changes, and outcomes. Technical may include code, schemas and databases, APIs, paths, frameworks, configuration, and engineering detail. Plain must stand alone and express the same information without code, programming-language terms, implementation syntax, or links or dependencies on Technical. Keep generated links inside the same audience tree.

```text
PLAIN/tracker.md and TECHNICAL/tracker.md        the table
PLAIN/status.md and TECHNICAL/status.md          audience-appropriate status
PLAIN/decisions.md and TECHNICAL/decisions.md    DEC entries
PLAIN/risks.md and TECHNICAL/risks.md            RSK entries
PLAIN/issues.md and TECHNICAL/issues.md          ISS entries
PLAIN/change-log.md and TECHNICAL/change-log.md  dated line per status change
```
**Every document listed above opens with an `In plain terms` block** — two to four sentences, before any table or heading. It is the only thing making these documents readable by the people who commissioned them.


---

## Done when
- [ ] Tracker matches every task record exactly
- [ ] All ten propagation targets updated in the same edit
- [ ] Plain-language status carries the same numbers and dates
- [ ] Every `Blocked` task has a matching `ISS-###`
- [ ] Every `Completed` task has recorded evidence
- [ ] Next action names one specific task
- [ ] Every document produced opens with an `In plain terms` block
- [ ] `tracker.md`, `status.md`, `decisions.md`, `risks.md`, `issues.md`, and `change-log.md` exist in both audience folders with identical relative filenames
- [ ] No audience-only filename exists
- [ ] Paired files contain identical facts and status; Plain is self-contained and contains no code, programming-language terms, implementation syntax, or Technical links
- [ ] Cross-stage links use the matching audience tree, and every link resolves from the deeper Stage 06 folder

---

## INPUT

**Task index:** `<path to docs/WORKFLOW/04 - TASK TRACKING/TECHNICAL/index.md>`
**What changed:** `<which tasks moved, or "check everything and reconcile">`
**Docs go in:** `<repository root — generated paths are docs/WORKFLOW/06 - PROGRESS TRACKING/{PLAIN,TECHNICAL}/>`
