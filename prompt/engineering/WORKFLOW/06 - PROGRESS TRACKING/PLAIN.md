# Progress Tracking — keep the project status truthful

> Paste this whole file when you need to update project progress.

## Your role

Act as the project's bookkeeper. Report what is true, not what is hoped for. Run this process after every task that finishes or changes status.

## Rules that cannot change

1. **Nothing is complete without evidence.** A saved-change identifier, a passing check, or a review is evidence; a claim is not.
2. **The task record is the authority.** When the tracker and the task record disagree, the task record is right and the tracker must be corrected in the same edit.
3. **Update everything or update nothing.** A partial update is worse than an older one because it looks current while being inaccurate.

A **saved-change identifier** is the unique reference for a saved set of changes. A **check result** is the recorded result of running the required checks. An **acceptance criterion** is a confirmed, checkable condition that proves the task delivered what was required. **Required work** means the actual change that the task called for has been made.

In the tracker example, `Deps` is short for dependencies: work that had to be completed first. `Test env` means the separate test environment used for checks. A **diagram** is a visual map of a process; **downstream tasks** are later tasks that were waiting on this task; and the traceability record shows which requirement each task supports.

## What to do

### 1. Update the tracker

```markdown
# Progress Tracker
_Synced 2026-07-31 · 34% complete (11 of 32) · Phase: Development_
**Next action:** begin TASK-014

| ID | Task | Owner | Status | % | Target | Blockers | Latest | Evidence |
|---|---|---|---|---|---|---|---|---|
| TASK-011 | Set up the project | cyro | Completed | 100% | 07-22 | — | Done | saved change `a3f21` |
| TASK-014 | Auth module (login section) | — | Ready | 0% | 08-05 | — | Deps cleared | — |
| TASK-016 | Driver navigation | — | Blocked | 20% | 08-07 | [ISS-002](issues.md#iss-002) | Test env down | — |

Not Started 8 · Ready 3 · In Progress 2 · Blocked 1 · Testing 1 · Completed 11
```

### 2. Make the same change everywhere

When a task's status changes, update all ten of these in the same edit:

1. The matching task records in `../../04 - TASK TRACKING/PLAIN/records/` and `../../04 - TASK TRACKING/TECHNICAL/records/`: status, percentage, checkboxes, and evidence.
2. Both Stage 04 `index.md` files: status and next-up line.
3. Both Stage 06 `tracker.md` files: row, totals, and next action.
4. Both Stage 06 `change-log.md` files: a dated line.
5. Both Stage 06 `status.md` files: the same facts, expressed for each audience.
6. Both Stage 05 `roadmap.md` files, if phase completion changed.
7. Both Stage 02 `traceability.md` files, if a requirement is now satisfied or unblocked.
8. Both Stage 06 `issues.md` files, when the task enters or leaves `Blocked`.
9. Matching Plain and Technical diagrams that show a process which changed, with links staying in their own audience tree.
10. Downstream tasks: change `Not Started` to `Ready` when their dependencies are cleared.

Updating fewer than these leaves the project documents inaccurate.

### 3. Write the status for both audiences

Use the same facts and numbers in both copies of `status.md`. Do not leave a task identifier standing alone: say what the task is as well. The Plain copy must use everyday words and stand alone; do not make bad news sound less serious. If something slipped, say that it slipped.

```markdown
# Where the project stands
_Updated 31 July · about a third of the work is done_

Sign-in and the database groundwork are finished and tested. The authentication
rebuild starts next and should take about a week. Driver navigation is on hold
until the test server is back — nothing else is waiting on it. Everything else
is on track for 22 August.
```

### 4. Record blockers properly

```markdown
### ISS-002 — Test environment database unavailable
**Opened** 2026-07-30 · **Severity** Blocker · **Blocks** TASK-016
**Impact.** Integration tests can't run; TASK-016 stuck at 20%.
**Next action.** Ask IT to restore the staging database.
**Resolution.** —
```

`ISS-###` is the identifier for a recorded issue. A task may be `Blocked` only if an issue like this exists. A `Blocked` task with no issue is simply `Not Started`.

`DEC-###` identifies a recorded decision and the reason for it. `RSK-###` identifies a recorded risk: something that could go wrong and affect the work.

An **integration test** checks that several parts work together. A **staging database** is the test copy of the database used before the live system is changed.

### 5. Check the completion gate

A task can move to `Completed` only when every item below is true:

- [ ] Required work finished.
- [ ] Every confirmed acceptance criterion is satisfied.
- [ ] Required tests are written and passing.
- [ ] Documentation and affected diagrams are updated.
- [ ] Related tasks and links are updated.
- [ ] Evidence is recorded.
- [ ] No unresolved critical blocker remains.

Any unticked item means the task stays `In Progress`. There are no exceptions for “basically done.”

## Documents to produce

Every document below must open with an `In plain terms` block of two to four sentences before any table or other heading. This short introduction is what makes the record understandable to the people who commissioned the work.

Keep these source prompts in their current folders. Generated project documents go under `docs/WORKFLOW/06 - PROGRESS TRACKING/PLAIN/` and `docs/WORKFLOW/06 - PROGRESS TRACKING/TECHNICAL/`.

Create the six files below in both audience folders. Every generated filename and relative location must have a partner in the other audience folder; add, delete, or rename both together. Use exactly the same filenames for both audiences.

The paired files must carry identical tasks, identifiers, facts, statuses, totals, percentages, dates, decisions, risks, issues, changes, and outcomes. Technical may include code, database structure, interfaces, paths, frameworks, configuration, and engineering detail. Plain must stand on its own and explain the same information without code, programming-language terms, implementation syntax, or any link or dependency on Technical. Links must remain inside the same audience tree.

```text
PLAIN/tracker.md and TECHNICAL/tracker.md        the table
PLAIN/status.md and TECHNICAL/status.md          audience-appropriate status
PLAIN/decisions.md and TECHNICAL/decisions.md    DEC entries
PLAIN/risks.md and TECHNICAL/risks.md            RSK entries
PLAIN/issues.md and TECHNICAL/issues.md          ISS entries
PLAIN/change-log.md and TECHNICAL/change-log.md  dated line per status change
```

## Done when

- [ ] The tracker matches every task record exactly.
- [ ] All ten propagation targets were updated in the same edit.
- [ ] The plain-language status has the same numbers and dates.
- [ ] Every `Blocked` task has a matching `ISS-###`.
- [ ] Every `Completed` task has recorded evidence.
- [ ] The next action names one specific task.
- [ ] Every produced document opens with an `In plain terms` block.
- [ ] `tracker.md`, `status.md`, `decisions.md`, `risks.md`, `issues.md`, and `change-log.md` exist in both audience folders with identical relative filenames.
- [ ] No audience-only filename exists.
- [ ] The paired files have identical facts and status, while Plain is self-contained and has no code, programming-language terms, implementation syntax, or Technical links.
- [ ] Cross-stage links use the matching audience tree, and all links resolve from the deeper Stage 06 folder.

## INPUT

**Task index:** `<path to docs/WORKFLOW/04 - TASK TRACKING/PLAIN/index.md>`
**What changed:** `<which tasks moved, or "check everything and reconcile">`
**Docs go in:** `<repository root — generated paths are docs/WORKFLOW/06 - PROGRESS TRACKING/{PLAIN,TECHNICAL}/>`
