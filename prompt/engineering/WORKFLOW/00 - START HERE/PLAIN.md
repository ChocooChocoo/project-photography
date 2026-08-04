# System Analysis and Documentation Workflow - v2

Use these prompts to take a project from the first look at what exists through requirements, planning, task tracking, a roadmap, progress reporting, testing, diagrams, and written records. The result is a connected set of simple text documents (Markdown) that explains both the work and the evidence behind it.

Each stage is a complete prompt: paste the whole file into your assistant and run it. You do not need to open this guide first because each stage repeats the rules it needs.

## Generated-document format

The prompt library is grouped by stage, and the project documents follow the same arrangement. Every generated file is saved under `docs/WORKFLOW/<stage>/PLAIN/` and has a matching file with the same name under `docs/WORKFLOW/<stage>/TECHNICAL/`. Both explain the same facts; only the intended reader and wording differ.

## Choose a stage

| # | Stage | Use it when | Creates |
|---|---|---|---|
| 01 | **SYSTEM ANALYSIS** | A system or project information already exists and you need to understand it | `docs/WORKFLOW/01 - SYSTEM ANALYSIS/{PLAIN,TECHNICAL}/` |
| 02 | **REQUIREMENTS** | You need to turn a request into clear, checkable needs | `docs/WORKFLOW/02 - REQUIREMENTS/{PLAIN,TECHNICAL}/` |
| 03 | **PLANNING** | You know what exists and what is needed, and must decide what to build | `docs/WORKFLOW/03 - PLANNING/{PLAIN,TECHNICAL}/` |
| 04 | **TASK TRACKING** | You have written task files and need them indexed and tracked | `docs/WORKFLOW/04 - TASK TRACKING/{PLAIN,TECHNICAL}/` |
| 05 | **ROADMAP** | Tasks exist and need grouping into phases and milestones | `docs/WORKFLOW/05 - ROADMAP/{PLAIN,TECHNICAL}/` |
| 06 | **PROGRESS TRACKING** | Work is underway and the reported status must stay honest | `docs/WORKFLOW/06 - PROGRESS TRACKING/{PLAIN,TECHNICAL}/` |
| 07 | **TESTING** | You need test cases that connect to requirements | `docs/WORKFLOW/07 - TESTING/{PLAIN,TECHNICAL}/` |
| 08 | **DIAGRAMS** | A process, structure, or data arrangement needs a picture | `docs/WORKFLOW/08 - DIAGRAMS/{PLAIN,TECHNICAL}/` |
| 09 | **TEMPLATES AND EXAMPLES** | You need a project-specific reference format not shown above | `docs/WORKFLOW/09 - TEMPLATES AND EXAMPLES/{PLAIN,TECHNICAL}/` when needed |

**Usual order:** 01 -> 02 -> 03 -> 04 -> 05 -> 08 -> 07 -> 06. Repeat 06 as work continues.

- If the repository is fresh but you have a specification or manuscript, still run 01: the documents are what needs to be examined.
- If there is truly nothing - no code and no documents - skip 01 and start at 02.
- If you only need the documents organized, stages 04 and 06 are enough.

## Rules that always apply

1. **Look before recommending.** Do not assume the project is new; inspect it first.
2. **The user writes the tasks.** The assistant may register and track task files, but must never invent, rename, or renumber them.
3. **No completion without evidence.** Use a commit, a passing test, a review, or other proof - never a claim alone.

## Pick the operating mode first

Choose one mode at the start and record it in both `docs/WORKFLOW/00 - START HERE/PLAIN/summary.md` and `docs/WORKFLOW/00 - START HERE/TECHNICAL/summary.md`:

| Mode | Meaning |
|---|---|
| **A - Existing** | Working code or a live database exists. |
| **B - New** | Nothing exists: no code and no documents. |
| **C - Partial** | There is half-built or abandoned code. |
| **D - Documents only** | There is no code, but there is a specification, manuscript, proposal, previous documentation, or client brief. |

A fresh repository with a manuscript beside it is Mode D, not Mode B. The documents are the material to examine.

## Labels used across the documents

Use three digits, in sequence, permanently. Never reuse or renumber a label. Make each one a heading so other documents can link to it, for example: `### REQ-001 - Users can reset their password`.

| Label | What it records | Home |
|---|---|---|
| `REQ-###` | Requirement | `02 - REQUIREMENTS/<AUDIENCE>/requirements.md` |
| `ANL-###` | Analysis finding | `01 - SYSTEM ANALYSIS/<AUDIENCE>/` |
| `GAP-###` | Difference between the current and intended state | `01 - SYSTEM ANALYSIS/<AUDIENCE>/gaps.md` |
| `TASK-###` | Registered task | `04 - TASK TRACKING/<AUDIENCE>/index.md` |
| `PRO-###` | Suggested task; not a task until accepted | `04 - TASK TRACKING/<AUDIENCE>/proposed.md` |
| `MIL-###` | Milestone: a point where something is demonstrably true | `05 - ROADMAP/<AUDIENCE>/milestones.md` |
| `TEST-###` | Test case | `07 - TESTING/<AUDIENCE>/test-cases.md` |
| `DGM-###` | Diagram | `08 - DIAGRAMS/<AUDIENCE>/` |
| `DEC-###` | Decision | `06 - PROGRESS TRACKING/<AUDIENCE>/decisions.md` |
| `RSK-###` | Risk | `06 - PROGRESS TRACKING/<AUDIENCE>/risks.md` |
| `ISS-###` | Issue or blocker | `06 - PROGRESS TRACKING/<AUDIENCE>/issues.md` |
| `ASM-###` | Assumption made | `00 - START HERE/<AUDIENCE>/open-items.md` |
| `QST-###` | Question awaiting an answer | `00 - START HERE/<AUDIENCE>/open-items.md` |

`<AUDIENCE>` means both `PLAIN` and `TECHNICAL`. Record every label in both matching files. Everything should connect to something else inside its own audience tree. A document that nothing points to, and that points to nothing, should not exist.

## Status words

Use these nine status words exactly and everywhere, without variation:

`Not Started` - `Ready` - `In Progress` - `Blocked` - `Under Review` - `Testing` - `Completed` - `Deferred` - `Cancelled`

The task-tracking stage sets the conditions for each status, including when an `ISS-###` or `DEC-###` must be logged, and how percentages are counted. Count percentages; never estimate them.

## Where work is saved

```text
docs/
`- WORKFLOW/
   |- 00 - START HERE/
   |  |- PLAIN/       summary.md - scope.md - open-items.md
   |  `- TECHNICAL/   summary.md - scope.md - open-items.md
   |- 01 - SYSTEM ANALYSIS/
   |  |- PLAIN/       existing-system.md - architecture.md - database.md -
   |  |                security.md - technical-debt.md - gaps.md - process-flows.md
   |  `- TECHNICAL/   identical filenames
   |- 02 - REQUIREMENTS/
   |  |- PLAIN/       requirements.md - traceability.md
   |  `- TECHNICAL/   identical filenames
   |- 03 - PLANNING/
   |  |- PLAIN/       plan.md - architecture.md - testing.md - deployment.md
   |  `- TECHNICAL/   identical filenames
   |- 04 - TASK TRACKING/
   |  |- PLAIN/       index.md - proposed.md - records/task-001.md ...
   |  `- TECHNICAL/   identical filenames
   |- 05 - ROADMAP/
   |  |- PLAIN/       roadmap.md - milestones.md - dependencies.md
   |  `- TECHNICAL/   identical filenames
   |- 06 - PROGRESS TRACKING/
   |  |- PLAIN/       tracker.md - status.md - decisions.md - risks.md - issues.md - change-log.md
   |  `- TECHNICAL/   identical filenames
   |- 07 - TESTING/
   |  |- PLAIN/       test-cases.md - results.md
   |  `- TECHNICAL/   identical filenames
   |- 08 - DIAGRAMS/
   |  |- PLAIN/       architecture.md - data-flow.md - erd.md - process-*.md - sequence-*.md
   |  `- TECHNICAL/   identical filenames
   `- 09 - TEMPLATES AND EXAMPLES/
      |- PLAIN/       optional project-specific templates and examples
      `- TECHNICAL/   identical filenames when materialized

prompts/tasks/         user-authored task files - never moved or renamed
```

Create only documents the project needs; do not create empty placeholder files. Within each stage, however, `PLAIN` and `TECHNICAL` must always contain the same relative filenames and subfolders. Add, delete, or rename both sides in the same operation.

Links between generated project documents stay inside the same audience tree. A Plain document may name shared source material in everyday words, while a Technical document may also link to shared source files, code, or external engineering evidence when useful. Neither audience links to the other. The two `summary.md` files in Stage 00 are the front doors for their audiences: project purpose, mode, current phase, next action, and links to that audience's task index, tracker, and key records.

## Write for two audiences

Every generated filename has two complete versions of the same factual record.

- **TECHNICAL** is for programmers and engineering agents. It may include code, repository paths, database structures, APIs, frameworks, configuration, versions, and implementation reasoning.
- **PLAIN** is for clients, panelists, advisers, and other non-programmers. It must cover the same requirements, findings, decisions, dates, statuses, risks, progress, and outcomes without code, programming-language syntax, database definitions, or unexplained specialist language.

Plain documents stand alone and never link to or depend on Technical documents. Links between generated documents remain inside the same audience tree. Matching files must report identical facts: simpler language is required, but softer or missing facts are not allowed.

## When to stop and ask

Use sensible defaults that are easy to undo and log each one as `ASM-###`.

Stop and create a `QST-###` for any change to the task list - adding, removing, splitting, merging, or altering a task, with no exceptions - and for a change to the stored-information layout (schema) that loses data, deleting or replacing existing documentation, a technology choice the project's already chosen tools (stack) do not imply, or two conflicting requirements.

Continue with any work that does not depend on the answer.
