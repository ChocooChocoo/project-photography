# System Analysis and Documentation

Four ways to run the same workflow — analyze a system, turn a request into requirements, plan the work, track the tasks you wrote, and document all of it as linked Markdown.

---

## Which one

| | Use it when |
|---|---|
| **`WORKFLOW - TECHNICAL/`** | Default for developers. Ten standalone prompts, ~100 lines each. Engineering vocabulary. Run one stage at a time. Start at its `00 - START HERE.md`. |
| **`WORKFLOW - PLAIN/`** | Same nine steps, no jargon. For students, clients, panelists, advisers, or anyone who'd rather not decode *brownfield* and *traceability matrix* to get started. |
| **`SYSTEM ANALYSIS AND DOCUMENTATION WORKFLOW V2.md`** | The whole lifecycle as one 386-line file. Use when you want a single paste. |
| **`SYSTEM ANALYSIS AND DOCUMENTATION REPORT.md`** | The original. Analysis only — tech stack, architecture, process flowcharts, technical and non-technical output. No planning, no tasks, no tracking. Still right when all you want is to understand an existing codebase. |

---

## Technical and plain are interchangeable
Same steps, same output files, same rules — only the wording differs. They pair up one to one:

| Step | Technical | Plain |
|---|---|---|
| 01 | ANALYZER | LOOK AT WHAT EXISTS |
| 02 | REQUIREMENTS | WRITE DOWN WHAT'S NEEDED |
| 03 | PLANNER | MAKE A PLAN |
| 04 | TASK REGISTRY | KEEP TRACK OF TASKS |
| 05 | ROADMAP | PUT IT ON A TIMELINE |
| 06 | PROGRESS TRACKER | CHECK PROGRESS |
| 07 | TESTING | MAKE SURE IT WORKS |
| 08 | DIAGRAMS | DRAW THE PICTURES |
| 09 | TEMPLATES | EXAMPLES |

You can mix them freely. Run the plain analyzer with a student and the technical planner yourself — the documents fit together, because both write to the same folders with the same labels.

---

## What the newer versions added over the original

The original scans a codebase and produces two documents. Everything below was built on top of that:

- **Requirements** — the request sorted into ten categories, each with a way to check it
- **Gap analysis** — what exists now versus what's needed
- **Planning** — architecture, modules, data, connections, and the four strategies, each citing what it answers
- **Task registry** — your own task files indexed and tracked, never renamed or rewritten
- **Roadmap** — twelve stages with entry and exit conditions, checkpoints, and real dependencies
- **Progress tracking** — status that can't say "done" without proof
- **Testing** — checks tied to requirements, in both directions
- **Traceability** — requirement → finding → gap → task → test → doc, and back

The original's two best ideas survived into all of them: **a flowchart per process**, and **technical and non-technical versions of the same facts**.

---

## Three rules the whole workflow runs on

1. **Look before you recommend.** Never assume the project is new — inspect it first.
2. **You write the tasks.** The agent registers and tracks them; it never invents, renames, or renumbers them.
3. **No completion without evidence.** A commit, a passing test, a review — not a claim.
