# System Analysis and Documentation Workflow

A set of prompts that carries a project from start to finish — analyze what exists, turn the request into requirements, plan the work, track the tasks you wrote, and document all of it as linked Markdown.

Each file below is a **standalone prompt**. Paste one into your agent and run it. You don't need to load this file first — each stage repeats what it needs.

---

## The files

| # | File | Run it when | Produces |
|---|---|---|---|
| 01 | **ANALYZER** | A system already exists and you need to know what's in it | `02-analysis/` |
| 02 | **REQUIREMENTS** | You have a request to turn into testable requirements | `01-requirements/` |
| 03 | **PLANNER** | Analysis and requirements are done, now decide what to build | `03-planning/` |
| 04 | **TASK REGISTRY** | You've written task files and want them indexed and tracked | `04-tasks/` |
| 05 | **ROADMAP** | Tasks exist and need grouping into phases with milestones | `05-roadmap/` |
| 06 | **PROGRESS TRACKER** | Work is underway and status needs to stay honest | `05-progress/` |
| 07 | **TESTING** | You need test cases tied to requirements | `06-testing/` |
| 08 | **DIAGRAMS** | Any process, architecture, or schema needs a picture | `07-diagrams/` |
| 09 | **TEMPLATES** | Reference — every artifact format in one place | — |

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
Decide this once, at the start, and write it in `00-overview/summary.md`.

| Mode | When | What it means |
|---|---|---|
| **A — Existing** | Working code or a live database exists | Analyze fully before planning anything |
| **B — New** | Nothing exists — no code, no documents | Say so, list what you inspected to be sure, then plan |
| **C — Partial** | Half-built or abandoned code | Mark each part usable / salvageable / replaceable, log why |
| **D — Documents only** | No code, but a spec, manuscript, proposal, or client brief exists | Analyze the documents: intent, rules, entities, processes — and the contradictions, omissions, and ambiguities in them. Findings are claims, not observations. |

A fresh repository with a manuscript beside it is Mode D, not Mode B. It's the most common way a project actually starts, and the documents are the thing to analyze.

---

## Identifiers
Three digits, sequential, permanent. Never reused, never renumbered. Declare each as a heading so it can be linked: `### REQ-001 — Users can reset their password`

| ID | Meaning | Home |
|---|---|---|
| `REQ-###` | Requirement | `01-requirements/requirements.md` |
| `ANL-###` | Analysis finding | `02-analysis/` |
| `GAP-###` | Gap between current and target | `02-analysis/gaps.md` |
| `TASK-###` | Registered task | `04-tasks/index.md` |
| `TEST-###` | Test case | `06-testing/test-cases.md` |
| `DGM-###` | Diagram | `07-diagrams/` |
| `DEC-###` | Decision | `05-progress/decisions.md` |
| `RSK-###` | Risk | `05-progress/risks.md` |
| `ISS-###` | Issue or blocker | `05-progress/issues.md` |
| `ASM-###` | Assumption you made | `00-overview/open-items.md` |
| `QST-###` | Question needing an answer | `00-overview/open-items.md` |

Everything links to something. A document nothing points at, and that points at nothing, shouldn't exist.

---

## Statuses
Use only these, everywhere.

`Not Started` (dependencies unmet) · `Ready` (can begin now) · `In Progress` · `Blocked` (needs a logged `ISS-###`) · `Under Review` · `Testing` · `Completed` (every checklist item ticked) · `Deferred` (needs a `DEC-###`) · `Cancelled` (needs a `DEC-###`)

Percentage is counted, not felt: satisfied acceptance criteria over total.

---

## Where everything goes

```text
docs/
├── 00-overview/       summary.md · scope.md · plain-summary.md · open-items.md
├── 01-requirements/   requirements.md · traceability.md
├── 02-analysis/       existing-system.md · architecture.md · database.md ·
│                      security.md · technical-debt.md · gaps.md · process-flows.md
├── 03-planning/       plan.md · architecture.md · testing.md · deployment.md
├── 04-tasks/          index.md · proposed.md · records/task-001.md …
├── 05-progress/       tracker.md · status-plain.md · decisions.md · risks.md ·
│                      issues.md · change-log.md
├── 06-testing/        test-cases.md · results.md
├── 07-diagrams/       architecture.md · data-flow.md · erd.md · process-*.md
├── 08-references/     glossary.md · external.md
└── README.md

prompts/tasks/         your own task files — never moved, never renamed
```

Create only what the project needs. Adjust names to fit the repository; keep the separation.

`README.md` is the front door: what the project is in plain words, the mode, the current phase, the next action, and links to the task index, tracker, and plain summary.

---

## Two audiences
Technical documents are the source of truth. Non-technical ones are derived views of the same facts, in plainer words — never separate narratives that can drift.

- **Technical** (`01-` to `04-`, `06-`, `07-`) — engineers and AI agents. Precise terms, paths, schema names, versions.
- **Non-technical** (`00-overview/plain-summary.md`, `05-progress/status-plain.md`) — clients, panelists, advisers. Outcomes, no jargon.

Every technical document opens with a short **In plain terms** block, two to four sentences. Any technical term used in a non-technical document needs a glossary entry pairing it with its plain equivalent. Both carry the same statuses, percentages, and dates — simpler words are fine, softer facts are not.

---

## When to stop and ask
Proceed on sensible defaults where the risk is reversible, and log each as an `ASM-###`.

Stop and raise a `QST-###` for: anything that changes the shape of the task list (adding, removing, splitting, merging, or altering a task — no exceptions, however obvious it looks); schema changes that lose data; deleting or replacing existing documentation; a technology choice the current stack doesn't imply; two requirements that conflict.

Keep working on anything that doesn't depend on the answer.
