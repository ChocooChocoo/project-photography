# Make a Plan

> Standalone prompt: paste this whole file into your assistant.

## Your job

Turn the findings and requirements into a plan someone can build from. Every recommendation must say what it answers. Describe the work; do not write tasks, because the user owns the task list.

## Rules that never bend

1. **Do not rebuild what already works.** Reuse is the default. Replacing a working part needs a `DEC-###` that names the specific error (defect) or conflict with what is needed (incompatibility) making reuse impossible. “It is old” and “I would do it differently” are not enough.
2. **Every suggestion gives its reason.** Cite the `REQ-###` it serves and the `ANL-###` or `GAP-###` it answers. Without a citation, it is only an opinion.
3. **Record alternatives, not just the choice.** When more than one approach works, record what you considered and why you chose the winner so the decision can be revisited without starting the reasoning over.

### Generated-document contract

- Keep this source prompt where it is. Put generated project documents under `docs/WORKFLOW/<numbered stage>/<audience>/`, where the audience folders are `PLAIN` and `TECHNICAL`.
- Create every generated filename in both audience folders. Add, delete, or rename the pair together, and keep facts, statuses, dates, decisions, risks, and outcomes aligned.
- The plain version must stand alone and contain no code, programming-language names, implementation syntax, or link or dependency on the technical version.
- The technical version contains the matching engineering detail, including stored-information design, system connections, file locations, chosen tools, and setup settings.
- Create only the listed files the project needs; any file created must have its matching audience copy.

## What to do

### 1. Say what is included

State what is included, excluded, and deliberately deferred. Name every deferred item; silence will later look like an oversight.

### 2. Describe the structure

Describe the main parts and their boundaries, how information moves, and where the work areas (modules) begin and end and why. If the existing arrangement will be kept, say so explicitly and name which parts.

### 3. Decide only necessary technology choices

Make a technology choice only where one is actually needed. If the chosen set of languages and tools (stack) is already set, record it and move on. Every new outside library or service (dependency) needs a reason and a `DEC-###`.

### 4. Define the work areas

For each work area (module), state what it owns, what it needs from other parts, and what it makes available to them.

### 5. Plan the data

Cover the layout of stored information (schema), relationships, safe changes that move existing information (migrations), and what happens to existing data. Stop and ask before any change that loses data.

### 6. Plan the connections

Describe the program-to-program connections (APIs), their addresses or connection points (endpoints or interfaces), the agreed request-and-response rules (contracts), and who calls them.

### 7. Write all four strategies

- **Security:** the sign-in approach (authentication model), private values such as keys and passwords (secrets), checks on submitted information (validation), and the threats you are actually defending against.
- **Testing:** what is tested at each level and what enough coverage means for this project.
- **Deployment:** where it runs (environments), the release process, and how to undo a bad release (rollback).
- **Documentation:** what is written, by whom, and where it is kept.

### 8. Set the order and checkpoints

Describe the rough order work should happen in and the checkpoints (milestones) along the way. Do not write tasks. Describe the shape of the work and let the user break it into tasks.

### 9. Record each decision as it is made

Record every `DEC-###` entry in both Stage 06 `decisions.md` files. In each audience's `plan.md`, summarize the decision and link to the matching decision record in the same audience tree.

```markdown
### DEC-006 - Use one new shared sign-in approach instead of extending the three existing ones
**Date** 2026-07-31 · **Status** Accepted
**Context.** ANL-009, GAP-005
**Options.** (1) Keep adding to one existing sign-in area (2) Share only part of the existing process (3) Create one new shared sign-in approach
**Chose** 3, because options 1 and 2 keep the different sign-in time limits that REQ-004 does not allow.
**Consequence.** The three existing sign-in areas must move to the new approach.
```

## Required output

```text
docs/WORKFLOW/03 - PLANNING/
|- PLAIN/{plan.md, architecture.md, testing.md, deployment.md}
`- TECHNICAL/{plan.md, architecture.md, testing.md, deployment.md}

Update the paired decision records:
docs/WORKFLOW/06 - PROGRESS TRACKING/PLAIN/decisions.md
docs/WORKFLOW/06 - PROGRESS TRACKING/TECHNICAL/decisions.md
```

Every listed document starts with an **In plain terms** block of two to four sentences before any table or heading.

Put the intended-future-state diagrams inside `architecture.md`: the system arrangement, information flow, a picture of stored information and its relationships, and a flowchart for every process that will change. Do not create extra diagram files.

Generated links must stay in the same audience. For example, the plain `plan.md` links to `[REQ-004](../../02%20-%20REQUIREMENTS/PLAIN/requirements.md#req-004)`, while its technical counterpart links to the matching file under `TECHNICAL`.

## Done when

- [ ] Every recommendation cites a `REQ-###` and an `ANL-###` or `GAP-###`.
- [ ] Every existing part being replaced has a `DEC-###` explaining why reuse failed.
- [ ] Every part being kept is named so it is not rebuilt by accident.
- [ ] All four strategies are written, not just the system arrangement.
- [ ] Alternatives are recorded wherever a real choice was made.
- [ ] Intended-state diagrams are drawn for anything that changes.
- [ ] No tasks were written; the work's shape is described and decomposition is left to the user.
- [ ] Every produced document starts with an **In plain terms** block.
- [ ] The four Stage 03 filenames exist in both audience folders, and the paired facts, statuses, dates, decisions, risks, and outcomes agree.
- [ ] Every `DEC-###` is recorded in both Stage 06 `decisions.md` files and summarized with a same-audience link from each `plan.md`.
- [ ] Plain documents stand alone without code, programming-language names, implementation syntax, or links or dependencies to technical documents.

## Input

**Analysis:** `<paths to the paired docs/WORKFLOW/01 - SYSTEM ANALYSIS/ audience folders, or "none - new project">`

**Requirements:** `<paths to the paired docs/WORKFLOW/02 - REQUIREMENTS/ audience folders>`

**Constraints:** `<chosen tools (stack), deadlines, standards - or "none">`

**Docs go in:** `<project root - generated files use docs/WORKFLOW/>`
