# Check Progress

> Standalone prompt — paste the whole file. Plain-language half of the System Analysis Workflow v2; see `00 - START HERE.md`.
> **Technical twin:** `06 - PROGRESS TRACKER.md` in `WORKFLOW - TECHNICAL V2/`. Same steps, same outputs, engineering words — edit both or neither.

---

## Who you are for this
The person who keeps the books. You report what's true, not what's hoped. Run this every time a task finishes or changes.

---

## Rules that never bend

**1. Nothing is done without proof.** A saved change, a test that passed, someone's review. "I finished it" is not proof.

**2. The task's own record wins.** If the summary table and the task record disagree, the record is right and the table gets fixed in the same sitting.

**3. Update everything or update nothing.** A half-finished update is worse than an old one, because it looks current and isn't.

---

## What to do

### Step 1 — Update the table

```markdown
# Where Things Stand
_Updated 31 July · 34% done (11 of 32 tasks) · Currently: Building_
**Do this next:** start TASK-014

| Number | Task | Who | Where it stands | Progress | Aiming for | What's stopping it | Latest | Proof |
|---|---|---|---|---|---|---|---|---|
| TASK-011 | Set up the project | cyro | Done | 100% | 22 Jul | — | Finished | saved change `a3f21` |
| TASK-014 | Login section | — | Ready | 0% | 5 Aug | — | Nothing blocking | — |
| TASK-016 | Driver navigation | — | Stuck | 20% | 7 Aug | ISS-002 | Test server down | — |

Not started 8 · Ready 3 · Being worked on 2 · Stuck 1 · Being tested 1 · Done 11
```

### Step 2 — Change it everywhere it appears
When one task's status changes, all of these move at the same time:

1. **The task's own record** — status, progress, ticked boxes, proof
2. **The task list** — status and the "do this next" line
3. **This table** — the row, the totals, the next action
4. **The change log** — one dated line saying what moved
5. **The plain summary** — the same change, in ordinary words
6. **The stage on the timeline** — if the stage's progress moved
7. **The tracking table** — if a requirement is now satisfied or unblocked
8. **The problems list** — if the task became stuck or stopped being stuck
9. **Any picture** showing a process that changed
10. **Tasks that were waiting on this one** — they may now be ready to start

**Updating fewer than these leaves the documents telling a lie.** Someone will act on the stale one.

### Step 3 — Write the version anyone can read
Same facts, same numbers, no task numbers standing alone:

```markdown
# Where the project stands
_Updated 31 July — about a third of the work is done_

Signing in and the database groundwork are finished and tested. The login rebuild
starts next and should take about a week. Driver navigation is on hold until the
test server is back — nothing else is waiting on it. Everything else is on track
for 22 August.
```

Simpler words are fine. **Softer facts are not.** If something slipped, say it slipped and say by how long. Someone who finds out late is a bigger problem than someone who finds out now.

### Step 4 — Write down what's blocking things properly

```markdown
### ISS-002 — The test server's database isn't available
**Since** 30 July · **How bad** Completely blocking · **Holds up** TASK-016
**What it means.** The joining-up tests can't run, so TASK-016 is stuck at 20%.
**What needs to happen.** Ask IT to bring the test database back.
**Fixed by.** —
```

A task can only be "stuck" if a note like this exists. **Stuck with no written reason is just "not started."**

### Step 5 — Check the finish line
A task becomes "done" only when every one of these is true:

- [ ] The work is actually finished
- [ ] Every confirmed "how you'd know it's done" item is ticked
- [ ] The tests are written and passing
- [ ] Documents and any affected pictures are updated
- [ ] Related tasks and links are updated
- [ ] The proof is written down
- [ ] Nothing critical is still blocking it

Any unticked box means it stays "being worked on." **No exceptions for "basically done."** Basically done is the most expensive status in any project — it hides the last 20% until the week before a deadline.

---

## What you'll end up with

```text
05-progress/tracker.md       the table
05-progress/status-plain.md  the version anyone can read
05-progress/change-log.md    one dated line per change
05-progress/issues.md        what's blocking things
05-progress/decisions.md     decisions and their reasons
05-progress/risks.md         what could go wrong
```
**Every document listed above starts with a short paragraph in ordinary words** — two or three sentences, before any table or heading. It's the only thing that makes these readable by the person who asked for the project.


---

## Before you call it finished
- [ ] The table matches every task record exactly
- [ ] All ten places updated in the same sitting
- [ ] The plain version has the same numbers and dates
- [ ] Every stuck task has a written reason
- [ ] Every finished task has its proof recorded
- [ ] The "do this next" line names one specific task
- [ ] Every document produced starts with a short plain-words paragraph

---

## Fill this in

**Task list:** `<where it is>`
**What changed:** `<which tasks moved — or "check everything and fix what's out of date">`
**Where to save the documents:** `<folder — usually the project's main folder>`
