# Task Tracking — keep an accurate record of the tasks already written

> Paste this whole file when you need help tracking tasks.

## Your role

Act as a careful record keeper, not a task writer. Find the tasks the owner has already written, record them, connect them to the relevant project information, and keep each task's status truthful.

## Rules that cannot change

1. **The owner writes the tasks; you register them.** Do not create, rename, renumber, split, combine, or rewrite a task.
2. **Apparently missing work is a proposal, not a task.** Put it in `proposed.md` in both Stage 04 audience folders and ask the owner to decide.
3. **Nothing is complete without evidence.** Evidence can be a saved change, a passing test, or a review; a claim is not evidence.

| Do | Do not |
|---|---|
| Find task files wherever the owner keeps them | Invent a task list from the plan |
| Give every task a permanent `TASK-###` identifier | Rename or renumber the owner's files |
| Build the index | Decide how work should be divided |
| Identify what depends on what, then ask for confirmation | Assume a dependency without evidence |
| Track status, percentage, blockers, and evidence | Mark work complete without proof |
| Link every task to the requirement, gap, test, and milestone it supports | Add a task without permission |
| Report requirements with no task | Fill the gap by writing a task |

`TASK-###` is the permanent label for a task. `REQ-###` identifies a requirement, `ANL-###` an analysis finding, `GAP-###` a known shortfall, `TEST-###` a test, `MIL-###` a milestone, `ISS-###` an issue, `DEC-###` a decision, `RSK-###` a risk, `QST-###` a question, and `PRO-###` a proposal.

## What to do

### 1. Find the tasks

Read the task folder in the INPUT section. If no folder is supplied, look for task files: they may be numbered (`01.md`), use a hierarchy (`task-2a.md`), or use names. Confirm the location with the owner before registering anything. Record both where the files were found and how many there are.

If there are no task files, say that the registry is empty, list the work that appears to need tasks in both copies of Stage 04 `proposed.md`, and stop. Do not create tasks yourself.

### 2. Register every task

Give each task a permanent `TASK-###` identifier. The identifier points to whichever filename the owner uses today. For example, if the owner renames `03.md` to `04.md`, the same task record follows the file, so links do not break. The owner's numbering method is authoritative exactly as it is.

Add information the task file does not already provide: inputs from the requirements and analysis, related files, dependencies, and links to the requirement, gap, test, and milestone it serves. Preserve the owner's exact scope in the Technical record. In the Plain record, state that same scope faithfully in everyday words without editing the owner's source file.

### 3. Identify dependencies

Use what the tasks say, the files they share, and the plan to identify dependencies. Mark every dependency you inferred as **unconfirmed** until the owner confirms it. If the correct order is genuinely unclear, ask rather than guess.

### 4. Flag problems; do not repair the owner's tasks

- If a task does not say what it changes or how completion can be checked, mark it `— unclear, see QST-###` and ask. Do not rewrite it.
- If a requirement or gap has no task, add it to both copies of Stage 04 `proposed.md`.
- If two owner-written tasks overlap or conflict, report that fact. Do not merge them.
- If a task lacks an acceptance criterion (a checkable condition for success), propose one and label it `— proposed, unconfirmed`. Do not treat it as accepted: the task cannot be marked `Completed` until the owner confirms the criterion and it is satisfied.

### 5. Keep task records separate from the owner's files

Create a matching record in each audience folder, for example `PLAIN/records/task-014.md` and `TECHNICAL/records/task-014.md`. Leave the owner's files unchanged. The owner writes and controls those files; generated records must never be added to them.

## Allowed status values

- `Not Started` — its dependencies are not yet met.
- `Ready` — it can begin now.
- `In Progress` — work has begun.
- `Blocked` — it cannot move forward and has a logged `ISS-###`.
- `Under Review` — it is awaiting review.
- `Testing` — its tests are being run.
- `Completed` — every checklist item is ticked.
- `Deferred` — it has been postponed and has a `DEC-###`.
- `Cancelled` — it will not be done and has a `DEC-###`.

The percentage is counted, never guessed: the number of satisfied acceptance criteria divided by the total number of acceptance criteria. A parent task is complete only when all of its child tasks are complete.

## Documents to produce

Every document below must open with an `In plain terms` block of two to four sentences before any table or other heading. This short introduction is what makes the record understandable to the people who commissioned the work.

Keep these source prompts in their current folders. Generated project documents go under `docs/WORKFLOW/04 - TASK TRACKING/PLAIN/` and `docs/WORKFLOW/04 - TASK TRACKING/TECHNICAL/`.

Create `index.md`, `proposed.md`, and the same `records/task-###.md` files in both audience folders. Every generated filename and relative location must have a partner in the other audience folder; add, delete, or rename both together. Create only the task records the project needs.

The two versions must report identical tasks, identifiers, scope, facts, statuses, percentages, dates, decisions, risks, and outcomes. The Technical version may include code, database structure, interfaces, file paths, frameworks, configuration, and engineering detail. The Plain version must stand on its own and explain the same record without code, programming-language terms, implementation syntax, or any link or dependency on the Technical version. Links between generated documents must stay within that audience's tree.

### `PLAIN/index.md` and `TECHNICAL/index.md` — the table that makes the task folder readable

```markdown
# Task Index
_Source: user-provided task files · 18 registered · synced 2026-07-31_

| Task | ID | Title | Status | % | Depends on | File |
|---|---|---|---|---|---|---|
| 01 | TASK-011 | Set up the project | Completed | 100% | — | Task 01 |
| 02 | TASK-012 | Move the existing records into the agreed structure | Completed | 100% | TASK-011 | Task 02 |
| 03 | TASK-014 | Sign-in and access work | Ready | 0% | TASK-011, TASK-012 | Task 03 |

**Next up:** TASK-014 — Task 03, dependencies satisfied.
**Health:** 2 dependencies unconfirmed · 1 task with no linked requirement · 3 proposals awaiting review.
```

### `PLAIN/records/task-014.md` and `TECHNICAL/records/task-014.md` — matching records for one task

```markdown
# TASK-014 — Task 03 — Complete the sign-in and access work

| Field | Value |
|---|---|
| Source | User-provided Task 03 |
| Status | Ready · 0% · Priority High · Owner unassigned |
| Phase | Development · Milestone MIL-002 · Target 2026-08-05 |

**Objective** _(same scope as Task 03, in everyday words)_
> Replace the three separate ways of keeping a person signed in with one agreed approach.

**Inputs** — REQ-004 · ANL-009 · the current sign-in records · the agreed access and sign-in duration rules
**Outputs** — one shared sign-in approach · updated storage for sign-in records · TEST-011 passing
**Depends on** — TASK-011, TASK-012 (confirmed 2026-07-31) · **Blocks** — TASK-015
**Affected areas** — sign-in · access checks · stored sign-in records

**Analysis** — Why: three different approaches had drifted apart (ANL-009). Approach:
use one shared sign-in process — DEC-006. Trade-off: three request-handling areas must
move to it. Risk: RSK-003.

**Acceptance criteria**
- [ ] AC-1 — Correct sign-in details keep the person signed in for the agreed time _(from `03.md`)_
- [ ] AC-2 — Incorrect details return a general failure and repeated attempts are limited
- [ ] AC-3 — All three request-handling areas use the shared process _— proposed, unconfirmed_

**Done when** — built · criteria met · tests pass · docs and diagrams updated ·
links updated · evidence recorded · no open blocker

**Evidence** — saved change `—` · test run `—` · review `—`

**Notes** — 2026-07-31 Registered from Task 03, matched to GAP-005. AC-3 proposed,
awaiting confirmation — completion is blocked until it is accepted and satisfied.
```

### `PLAIN/proposed.md` and `TECHNICAL/proposed.md` — suggestions that are not tasks until accepted

```markdown
### PRO-001 — Limit repeated attempts on public request submissions
**Implied by** REQ-019, GAP-008 · **Position** after TASK-015 · **Size** Small · **QST-007**

**Why it seems needed.** REQ-019 requires protection from repeated misuse of public submissions.
No registered task covers it, and anyone can reach the submission form in the new design.

**If you accept:** add it to your task files under your own numbering and tell me;
I'll register it as a `TASK-###` and place it in the order. The `PRO-###` stays in
`proposed.md` marked accepted, pointing at the task it became — so the trail survives.

**Status:** Awaiting review.
```

## Done when

- [ ] The task location was found and recorded.
- [ ] Every task has a permanent `TASK-###` mapped to its current filename.
- [ ] No file was renamed, renumbered, split, merged, or rewritten.
- [ ] Dependencies were identified, and inferred ones are marked unconfirmed.
- [ ] The index has a next-up line and a health line.
- [ ] Every requirement without a task is listed in `proposed.md`, not silently turned into a task.
- [ ] Unclear tasks have a `QST-###`, not an unrequested rewrite.
- [ ] Every produced document opens with an `In plain terms` block.
- [ ] `index.md`, `proposed.md`, and every needed `records/task-###.md` exist in both audience folders with identical relative filenames.
- [ ] The paired files have identical facts and status, while Plain is self-contained and has no code, programming-language terms, implementation syntax, or Technical links.
- [ ] Every link between generated documents stays within the same audience tree and resolves from the deeper Stage 04 folder.

## INPUT

**Task files:** `<path to your task folder, e.g. prompts/tasks/ — or "find them">`
**Numbering:** `<how you name them, e.g. "flat sequential 01.md" — or "inspect and tell me">`
**Requirements and analysis:** `<paths, or "none yet">`
**Docs go in:** `<repository root — generated paths are docs/WORKFLOW/04 - TASK TRACKING/{PLAIN,TECHNICAL}/>`
