# Objective
## Roadmap — group the work into phases with checkpoints

> Standalone prompt. Part of the System Analysis Workflow — see `00 - START HERE.md`.

---

## Role
You are a delivery planner. Arrange work that already exists into phases. You are not deciding what the work is.

---

## Non-negotiables
1. **Phases group tasks; they don't create them.** If a phase has no tasks, either the tasks aren't written yet or the phase doesn't belong.
2. **Say where phases overlap.** A strict sequence implied but not real is a lie that costs a week.
3. **Every phase needs an exit condition.** "Phase done" must be checkable, not felt.

---

## The phases
Use the ones the project actually has. Drop the rest.

| # | Phase | Ends when |
|---|---|---|
| 1 | Discovery | You know what exists and what's being asked for |
| 2 | Requirements | Every requirement is written and testable |
| 3 | Analysis | Current state documented, gaps identified |
| 4 | Design | Architecture and data model agreed |
| 5 | Setup | Anyone on the team can run it locally |
| 6 | Development | Core modules built and unit tested |
| 7 | Integration | Modules work together end to end |
| 8 | Testing | Test cases pass, coverage meets the strategy |
| 9 | Security | Security review done, findings closed or accepted |
| 10 | Documentation | Docs match what was actually built |
| 11 | Deployment | Running in the target environment |
| 12 | Validation | Stakeholders confirm it does what was asked |

---

## What to do

### 1. Assign every task to a phase
Pull from `04-tasks/index.md`. A task in no phase is an oversight; a phase with no tasks is noise.

### 2. Write each phase

```markdown
## Phase 6 — Development

| Field | Value |
|---|---|
| Milestone | MIL-002 |
| Status | In Progress · 40% |
| Entry | Architecture agreed; environment reproducible |
| Exit | All phase tasks Completed; module tests passing |
| Target | 2026-08-15 |
| Overlaps with | Phase 8 — testing starts as each module lands |

**Tasks** — TASK-014, TASK-015, TASK-016
**Requirements** — REQ-004, REQ-007, REQ-012
**Deliverables** — auth module · dispatch module · validation layer
**Depends on** — Phase 5 complete · DEC-006 settled
**Risks** — RSK-003
```

### 3. Set milestones
A milestone is a point where something is demonstrably true, not a date on a calendar. `MIL-002 — a user can sign in and submit a request end to end.` Each names the phase it closes and the evidence that proves it.

### 4. Map dependencies between phases
Which phase genuinely blocks which. Most don't. Write the real graph, not a waterfall.

### 5. Percentage
Phase percentage is completed tasks over total tasks in the phase. Project percentage is completed tasks over all tasks. Counted, never estimated.

---

## Output

```text
05-roadmap/roadmap.md      the phases
05-roadmap/milestones.md   MIL entries with evidence conditions
05-roadmap/dependencies.md which phase blocks which, and which don't
```

---

## Done when
- [ ] Every task in the index belongs to exactly one phase
- [ ] Every phase has entry and exit conditions someone could check
- [ ] Overlaps stated explicitly where they exist
- [ ] Every milestone names its evidence
- [ ] Phase dependencies reflect what's actually blocking, not a default waterfall
- [ ] Percentages counted from the task index, not estimated

---

## INPUT

**Task index:** `<path to 04-tasks/index.md>`
**Plan:** `<path to 03-planning/, or "none">`
**Deadline:** `<target date, or "none">`
**Docs go in:** `<path — default: repository root>`
