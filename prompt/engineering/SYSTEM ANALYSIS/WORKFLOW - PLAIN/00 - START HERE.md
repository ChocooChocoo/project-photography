# Start Here — the plain-language version

Nine prompts that take a software project from "we're not sure what we have" to "it's built, tested, and written down."

This is the plain-language set. There's a matching technical set in `WORKFLOW - TECHNICAL/` that does exactly the same work in engineering vocabulary. **Same steps, same output files, same rules — only the wording differs.** You can mix them: run the plain analyzer and the technical planner, and they'll fit together.

Use this set if you're a student, a project owner, an adviser, or anyone who'd rather not decode words like *brownfield* and *traceability matrix* to get started.

---

## How to use these

Each file is a complete instruction sheet. Pick the one that matches what you need right now, paste the whole thing into your AI assistant, fill in the questions at the bottom, and send it. You don't need to read the others first.

---

## The files

| # | File | Use it when |
|---|---|---|
| 01 | **LOOK AT WHAT EXISTS** | Something is already built and you need to know what's in it |
| 02 | **WRITE DOWN WHAT'S NEEDED** | You have an idea or a request and need it written as clear, checkable statements |
| 03 | **MAKE A PLAN** | You know what exists and what's needed, and now you decide how to get there |
| 04 | **KEEP TRACK OF TASKS** | You've written your task list and want it organized and watched |
| 05 | **PUT IT ON A TIMELINE** | Tasks exist and you need to group them into stages with checkpoints |
| 06 | **CHECK PROGRESS** | Work is happening and you need an honest picture of where it stands |
| 07 | **MAKE SURE IT WORKS** | You need to prove the thing does what was asked |
| 08 | **DRAW THE PICTURES** | Any process, structure, or database needs a diagram |
| 09 | **EXAMPLES** | Reference — what every document should look like |

**The usual order:** 01 → 02 → 03 → 04 → 05 → 08 → 07 → 06, then 06 again and again as you build.

**Nothing built, but you have a specification or manuscript?** Still start at 01 — it reads documents too, and that's usually where the real work is.
**Truly nothing yet — no code, no documents?** Skip 01, start at 02.
**Just want your documents organized?** 04 and 06 are enough.

---

## Four situations you might be in
Step 01 works out which one applies. It's worth knowing them, because the fourth catches people out.

| Situation | What it means |
|---|---|
| **Something is built** | Look at everything before deciding anything |
| **Nothing at all** — no code, no documents | Say so, list what you checked to be sure, then start writing requirements |
| **Half-built or abandoned** | Look at it, then mark each piece keep / fix / replace, with reasons |
| **Documents only** — nothing built, but there's a specification, manuscript, proposal, or client brief | Read and analyze the documents. There's plenty to find; it just isn't code. |

An empty folder with a 40-page document beside it is the fourth situation, not the second. It's the most common way a project actually starts.

---

## Three rules that never bend
Every file repeats these, so you can't lose them by starting in the middle.

**1. Look before you suggest.** Never assume a project is starting from nothing. Check first. Most "let's rebuild this" advice comes from not having looked.

**2. You write the tasks. The assistant tracks them.** It should never invent tasks, rename your files, or renumber them. If it thinks something is missing, it writes a suggestion and asks you.

**3. Nothing is "done" without proof.** A saved change, a passing test, someone's review. Not "I finished it."

---

## The labels
The prompts tag things with short codes so documents can point at each other. You don't need to memorize these — just know that when you see `REQ-004`, it means requirement number four, and you can search for it.

| Label | Means |
|---|---|
| `REQ-004` | Something the system needs to do |
| `ANL-009` | Something you found while looking at the existing system |
| `GAP-005` | A difference between what exists now and what's needed |
| `TASK-014` | One of your tasks |
| `TEST-011` | A test |
| `DGM-004` | A diagram |
| `DEC-006` | A decision someone made, and why |
| `RSK-003` | Something that could go wrong |
| `ISS-002` | Something that's currently blocking work |
| `ASM-002` | Something assumed because nobody said otherwise |
| `QST-007` | A question waiting on your answer |

Numbers are permanent. Once something is `REQ-004`, it stays `REQ-004` forever, even if it's later cancelled — so links never break.

---

## Where things get saved

```text
docs/
├── 00-overview/       what the project is, in plain words
├── 01-requirements/   what it needs to do
├── 02-analysis/       what's already there
├── 03-planning/       how you'll build it
├── 04-tasks/          your task list, indexed
├── 05-progress/       where things stand, decisions, risks, problems
├── 06-testing/        tests and results
├── 07-diagrams/       the pictures
├── 08-references/     word list and outside sources
└── README.md          the front page

prompts/tasks/         your own task files — never moved, never renamed
```

Only create what your project actually needs. An empty folder helps nobody.

---

## Two versions of everything
Anything written for engineers should also exist in words a client or panel can follow. The technical version is the one that must be exactly right; the plain version says the same thing more simply.

Simpler words are fine. **Softer facts are not.** If something slipped by two weeks, both versions say two weeks.

---

## When to stop and ask
Go ahead and use sensible judgment where a mistake is easy to undo — just write down what you assumed.

Stop and ask when:

- Anything would change your task list — adding, removing, splitting, combining, or altering a task. No exceptions, no matter how obvious it looks.
- A database change would lose data
- Existing documents would be deleted or replaced
- A new tool or technology is being introduced that nobody asked for
- Two requirements contradict each other

Keep working on whatever doesn't depend on the answer.
