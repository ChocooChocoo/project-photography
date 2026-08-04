# System Analysis and Documentation Workflow — v2

A set of prompts that carries a project from start to finish — analyze what exists, turn the request into requirements, plan the work, track the tasks you wrote, and document all of it as linked Markdown.

Each file below is a **standalone prompt**. Paste one into your agent and run it. You don't need to load this file first — each stage repeats the rules it needs to enforce.

> **Plain counterpart:** `PLAIN.md` in this folder. It covers the same steps, output files, and rules in everyday language. **Edit both or neither.**

**Generated-document format.** The prompt library is stage-first, and its output is stage-first too. Every generated document lives under `docs/WORKFLOW/<stage>/PLAIN/` or `docs/WORKFLOW/<stage>/TECHNICAL/`. The two audience folders must contain identical relative filenames and the same facts; only language and presentation differ.

---

## The files

| # | File | Run it when | Produces |
|---|---|---|---|
| 01 | **ANALYZER** | A system already exists and you need to know what's in it | `docs/WORKFLOW/01 - SYSTEM ANALYSIS/{PLAIN,TECHNICAL}/` |
| 02 | **REQUIREMENTS** | You have a request to turn into testable requirements | `docs/WORKFLOW/02 - REQUIREMENTS/{PLAIN,TECHNICAL}/` |
| 03 | **PLANNER** | Analysis and requirements are done, now decide what to build | `docs/WORKFLOW/03 - PLANNING/{PLAIN,TECHNICAL}/` |
| 04 | **TASK REGISTRY** | You've written task files and want them indexed and tracked | `docs/WORKFLOW/04 - TASK TRACKING/{PLAIN,TECHNICAL}/` |
| 05 | **ROADMAP** | Tasks exist and need grouping into phases with milestones | `docs/WORKFLOW/05 - ROADMAP/{PLAIN,TECHNICAL}/` |
| 06 | **PROGRESS TRACKER** | Work is underway and status needs to stay honest | `docs/WORKFLOW/06 - PROGRESS TRACKING/{PLAIN,TECHNICAL}/` |
| 07 | **TESTING** | You need test cases tied to requirements | `docs/WORKFLOW/07 - TESTING/{PLAIN,TECHNICAL}/` |
| 08 | **DIAGRAMS** | Any process, architecture, or schema needs a picture | `docs/WORKFLOW/08 - DIAGRAMS/{PLAIN,TECHNICAL}/` |
| 09 | **TEMPLATES** | Reference — materialize paired examples only when the project needs them | `docs/WORKFLOW/09 - TEMPLATES AND EXAMPLES/{PLAIN,TECHNICAL}/` |

**Typical order:** 01 → 02 → 03 → 04 → 05 → 08 → 07 → 06 (then 06 repeatedly as you build).

**Fresh repository, but you have a spec or manuscript:** still run 01 — Mode D reads the documents.
**Truly nothing — no code, no documents:** skip 01, start at 02.
**Just want the docs organized:** 04 and 06 alone will do it.

---

## Non-negotiables
Every stage obeys these. They're repeated in each file so you can't lose them.

1. **Look before you recommend.** Never assume the project is new — inspect it first.
2. **The user writes the tasks.** The agent registers and tracks them; it never invents, renames, or renumbers them.
3. **No completion without evidence.** A commit, a passing test, a review — not a claim.

---

## Operating modes
**A — Existing** · **B — New** · **C — Partial** · **D — Documents only**

Decide this once, at the start, and write it in both `docs/WORKFLOW/00 - START HERE/PLAIN/summary.md` and `docs/WORKFLOW/00 - START HERE/TECHNICAL/summary.md`. **`../01 - SYSTEM ANALYSIS/TECHNICAL.md` defines what each mode means and how to choose** — it's the prompt that acts on the choice, so the full table lives there and nowhere else.

The one thing worth knowing before you get there: a fresh repository with a manuscript beside it is Mode D, not Mode B. It's the most common way a project actually starts, and the documents are the thing to analyze.

---

## Identifiers
Three digits, sequential, permanent. Never reused, never renumbered. Declare each as a heading so it can be linked: `### REQ-001 — Users can reset their password`

| ID | Meaning | Home |
|---|---|---|
| `REQ-###` | Requirement | `02 - REQUIREMENTS/<AUDIENCE>/requirements.md` |
| `ANL-###` | Analysis finding | `01 - SYSTEM ANALYSIS/<AUDIENCE>/` |
| `GAP-###` | Gap between current and target | `01 - SYSTEM ANALYSIS/<AUDIENCE>/gaps.md` |
| `TASK-###` | Registered task | `04 - TASK TRACKING/<AUDIENCE>/index.md` |
| `PRO-###` | Proposed task — not a task until accepted | `04 - TASK TRACKING/<AUDIENCE>/proposed.md` |
| `MIL-###` | Milestone — a point where something is demonstrably true | `05 - ROADMAP/<AUDIENCE>/milestones.md` |
| `TEST-###` | Test case | `07 - TESTING/<AUDIENCE>/test-cases.md` |
| `DGM-###` | Diagram | `08 - DIAGRAMS/<AUDIENCE>/` |
| `DEC-###` | Decision | `06 - PROGRESS TRACKING/<AUDIENCE>/decisions.md` |
| `RSK-###` | Risk | `06 - PROGRESS TRACKING/<AUDIENCE>/risks.md` |
| `ISS-###` | Issue or blocker | `06 - PROGRESS TRACKING/<AUDIENCE>/issues.md` |
| `ASM-###` | Assumption you made | `00 - START HERE/<AUDIENCE>/open-items.md` |
| `QST-###` | Question needing an answer | `00 - START HERE/<AUDIENCE>/open-items.md` |

`<AUDIENCE>` means both `PLAIN` and `TECHNICAL`. Declare every identifier in both matching files. Links between generated documents stay inside the same audience tree. Technical documents may also link to shared source artifacts, code, or external engineering evidence; Plain documents name shared sources in everyday words without technical paths. Neither audience links to the other. A document nothing points at, and that points at nothing, shouldn't exist.

---

## Statuses
Nine, used everywhere without variation:

`Not Started` · `Ready` · `In Progress` · `Blocked` · `Under Review` · `Testing` · `Completed` · `Deferred` · `Cancelled`

**`../04 - TASK TRACKING/TECHNICAL.md` defines what each one requires** — which need a logged `ISS-###`, which need a `DEC-###`, and how percentage is counted. It's the prompt that assigns them, so the definitions live there and nowhere else. Percentage is always counted, never estimated.

---

## Where everything goes

```text
docs/
└── WORKFLOW/
    ├── 00 - START HERE/
    │   ├── PLAIN/       summary.md · scope.md · open-items.md
    │   └── TECHNICAL/   summary.md · scope.md · open-items.md
    ├── 01 - SYSTEM ANALYSIS/
    │   ├── PLAIN/       existing-system.md · architecture.md · database.md ·
    │   │                security.md · technical-debt.md · gaps.md · process-flows.md
    │   └── TECHNICAL/   identical filenames
    ├── 02 - REQUIREMENTS/
    │   ├── PLAIN/       requirements.md · traceability.md
    │   └── TECHNICAL/   identical filenames
    ├── 03 - PLANNING/
    │   ├── PLAIN/       plan.md · architecture.md · testing.md · deployment.md
    │   └── TECHNICAL/   identical filenames
    ├── 04 - TASK TRACKING/
    │   ├── PLAIN/       index.md · proposed.md · records/task-001.md …
    │   └── TECHNICAL/   identical filenames
    ├── 05 - ROADMAP/
    │   ├── PLAIN/       roadmap.md · milestones.md · dependencies.md
    │   └── TECHNICAL/   identical filenames
    ├── 06 - PROGRESS TRACKING/
    │   ├── PLAIN/       tracker.md · status.md · decisions.md · risks.md · issues.md · change-log.md
    │   └── TECHNICAL/   identical filenames
    ├── 07 - TESTING/
    │   ├── PLAIN/       test-cases.md · results.md
    │   └── TECHNICAL/   identical filenames
    ├── 08 - DIAGRAMS/
    │   ├── PLAIN/       architecture.md · data-flow.md · erd.md · process-*.md · sequence-*.md
    │   └── TECHNICAL/   identical filenames
    └── 09 - TEMPLATES AND EXAMPLES/
        ├── PLAIN/       optional project-specific templates and examples
        └── TECHNICAL/   identical filenames when materialized

prompts/tasks/          user-authored task files — never moved or renamed
```

Create only what the project needs; do not create empty placeholder documents. Within a stage, however, the `PLAIN` and `TECHNICAL` folders must always have the same relative file and subfolder names. Adding, deleting, or renaming one side means making the identical structural change on the other side in the same operation.

Links between generated project documents stay inside the same audience tree. Technical documents may also link to shared source artifacts, code, or external engineering evidence. Plain documents name shared sources in everyday words without technical paths. Neither audience links to the other. `docs/WORKFLOW/00 - START HERE/PLAIN/summary.md` and its Technical counterpart are their audience's front doors: project purpose, mode, current phase, next action, and links to that audience's task index, tracker, and other key records.

---

## Two audiences
Every generated document has two complete presentations of one factual record.

- **TECHNICAL** — programmers and AI engineering agents. Include relevant code, repository paths, database structures, APIs, frameworks, configuration, versions, and implementation reasoning.
- **PLAIN** — clients, panelists, advisers, and non-programmers. Cover the same requirements, findings, decisions, dates, statuses, risks, progress, and outcomes without code, programming-language syntax, schema definitions, or unexplained engineering jargon.

The relative filenames must match exactly. Plain documents are standalone: they never link to, cite, or require their Technical counterparts. Links between generated documents stay inside the same audience folder tree. Both audiences always report identical facts; simpler words are required, softer facts are forbidden.

---

## When to stop and ask
Proceed on sensible defaults where the risk is reversible, and log each as an `ASM-###`.

Stop and raise a `QST-###` for: anything that changes the shape of the task list (adding, removing, splitting, merging, or altering a task — no exceptions, however obvious it looks); schema changes that lose data; deleting or replacing existing documentation; a technology choice the current stack doesn't imply; two requirements that conflict.

Keep working on anything that doesn't depend on the answer.
