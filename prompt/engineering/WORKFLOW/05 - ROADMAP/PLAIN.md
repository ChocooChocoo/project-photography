# Roadmap — place existing work into checkable stages

> Paste this whole file when you need a clear project timeline.

## Your role

Act as a delivery planner. Arrange work that already exists into stages (called phases below). Do not decide what new work should exist.

## Rules that cannot change

1. **Phases group tasks; they do not create them.** If a phase has no tasks, either its tasks have not been written or the phase does not belong.
2. **State where phases overlap.** Pretending that all phases happen strictly one after another creates a misleading schedule.
3. **Every phase needs a checkable exit condition.** It must be possible to verify that a phase is finished; it cannot be a feeling.

## Available phases

Use only the phases that the project actually has. Leave out the rest.

| # | Phase | It ends when |
|---|---|---|
| 1 | Discovery | You know what exists and what is being asked for |
| 2 | Requirements | Every requirement is written and testable |
| 3 | Analysis | The current state is documented and gaps are identified |
| 4 | Design | How the parts fit together and how information is organised are agreed |
| 5 | Setup | Anyone on the team can run it locally |
| 6 | Development | The main parts of the system are built and each part is checked on its own |
| 7 | Integration | The parts work together from start to finish |
| 8 | Testing | Test cases pass and coverage meets the strategy |
| 9 | Security | The security review is done and findings are closed or accepted |
| 10 | Documentation | Documents match what was actually built |
| 11 | Deployment | It is running in the target environment |
| 12 | Validation | Stakeholders confirm it does what was asked |

## What to do

### 1. Put every task in one phase

Use `../../04 - TASK TRACKING/PLAIN/index.md` as the source. A task with no phase is an oversight. A phase with no tasks is unnecessary. Every task must belong to exactly one phase.

### 2. Describe each phase

```markdown
## Phase 6 — Development

| Field | Value |
|---|---|
| Milestone | MIL-002 |
| Status | In Progress · 40% |
| Entry | The design is agreed; another team member can prepare the same working setup |
| Exit | All phase tasks Completed; checks for each main part passing |
| Target | 2026-08-15 |
| Overlaps with | Phase 8 — testing starts as each module lands |

**Tasks** — TASK-014, TASK-015, TASK-016
**Requirements** — REQ-004, REQ-007, REQ-012
**Deliverables** — sign-in and access work · request routing · checks on submitted information
**Depends on** — Phase 5 complete · DEC-006 settled
**Risks** — RSK-003
```

An entry condition says what must be true before the phase begins. An exit condition says what must be true before it ends. `MIL-###` is a permanent milestone label; `REQ-###`, `DEC-###`, and `RSK-###` identify a requirement, decision, and risk.

In the example, the agreed design explains how the system's parts and information fit together. A setup is repeatable when another team member can prepare it and get the same working result. The named deliverables cover sign-in and access, sending work or requests to the right place, and checking information before it is used. The planned range of checks says what must be verified, and the target setting is where the finished system is meant to run.

### 3. Set milestones

A milestone is a point at which something can be demonstrated as true, not simply a date on a calendar. Write every milestone as a heading in `milestones.md`, link it from `roadmap.md` within the Plain folder, and give it a permanent `MIL-###` identifier.

```markdown
### MIL-002 — A user can sign in and submit a request end to end
**Closes** Phase 6 — Development · **Target** 2026-08-15 · **Status** Not met
**Evidence that proves it.** A recorded run through the live flow: sign in, submit,
see it appear in the dispatcher queue. TEST-011 and TEST-019 passing.
```

`MIL-002` can be checked in front of a room. “Backend complete” cannot.

### 4. Map genuine phase dependencies

Record which phases truly prevent other phases from starting. Most do not. Show the actual set of dependencies rather than a default straight-line sequence.

### 5. Count progress

The percentage for a phase is its completed tasks divided by all tasks in that phase. The project percentage is all completed tasks divided by all tasks in the project. Count both from the task list; never estimate them.

## Documents to produce

Every document below must open with an `In plain terms` block of two to four sentences before any table or other heading. This short introduction is what makes the record understandable to the people who commissioned the work.

Keep these source prompts in their current folders. Generated project documents go under `docs/WORKFLOW/05 - ROADMAP/PLAIN/` and `docs/WORKFLOW/05 - ROADMAP/TECHNICAL/`.

Create all three files in both audience folders. Every generated filename and relative location must have a partner in the other audience folder; add, delete, or rename both together. Create only these needed roadmap files.

The paired files must carry identical phases, tasks, identifiers, facts, statuses, percentages, dates, decisions, dependencies, risks, milestones, and outcomes. Technical may include code, database structure, interfaces, paths, frameworks, configuration, and engineering detail. Plain must stand on its own and explain the same information without code, programming-language terms, implementation syntax, or any link or dependency on Technical. Links must remain inside the same audience tree.

```text
PLAIN/roadmap.md and TECHNICAL/roadmap.md            the phases
PLAIN/milestones.md and TECHNICAL/milestones.md      MIL entries with evidence conditions
PLAIN/dependencies.md and TECHNICAL/dependencies.md  which phase blocks which, and which don't
```

## Done when

- [ ] Every task in the index belongs to exactly one phase.
- [ ] Every phase has entry and exit conditions that someone can check.
- [ ] Overlaps are stated explicitly wherever they exist.
- [ ] Every milestone names its evidence.
- [ ] Phase dependencies show what is actually blocking work, not a default waterfall sequence (a strict one-way sequence).
- [ ] Percentages are counted from the task index, not estimated.
- [ ] Every produced document opens with an `In plain terms` block.
- [ ] `roadmap.md`, `milestones.md`, and `dependencies.md` exist in both audience folders with identical relative filenames.
- [ ] The paired files have identical facts and status, while Plain is self-contained and has no code, programming-language terms, implementation syntax, or Technical links.
- [ ] Cross-stage links use the matching audience tree, and all links resolve from the deeper Stage 05 folder.

## INPUT

**Task index:** `<path to docs/WORKFLOW/04 - TASK TRACKING/PLAIN/index.md>`
**Plan:** `<path to docs/WORKFLOW/03 - PLANNING/PLAIN/, or "none">`
**Deadline:** `<target date, or "none">`
**Docs go in:** `<repository root — generated paths are docs/WORKFLOW/05 - ROADMAP/{PLAIN,TECHNICAL}/>`
