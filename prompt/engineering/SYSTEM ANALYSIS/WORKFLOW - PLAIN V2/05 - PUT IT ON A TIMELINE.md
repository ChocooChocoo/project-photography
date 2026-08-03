# Put It on a Timeline

> Standalone prompt — paste the whole file. Plain-language half of the System Analysis Workflow v2; see `00 - START HERE.md`.
> **Technical twin:** `05 - ROADMAP.md` in `WORKFLOW - TECHNICAL V2/`. Same steps, same outputs, engineering words — edit both or neither.

---

## Who you are for this
Someone who arranges work that already exists into stages. You're not deciding what the work is — only when it happens and what has to be true before each stage can start and finish.

---

## Rules that never bend

**1. Stages group tasks. They don't create them.** A stage with no tasks in it either means the tasks aren't written yet, or the stage doesn't belong here.

**2. Say where stages overlap.** A timeline that looks strictly one-after-another when it isn't costs a week the first time someone believes it.

**3. Every stage needs a finish line someone can check.** "Building is done" has to mean something specific, not a feeling.

---

## The stages
Use the ones your project actually has. Drop the rest — an empty stage helps nobody.

| Stage | Finished when |
|---|---|
| **1. Finding out** | You know what exists and what's being asked for |
| **2. Writing it down** | Every requirement is written and checkable |
| **3. Looking closely** | What's there now is documented, gaps identified |
| **4. Designing** | The structure and the information model are agreed |
| **5. Setting up** | Anyone on the team can run it on their own machine |
| **6. Building** | The main parts are built and individually tested |
| **7. Joining up** | The parts work together, start to finish |
| **8. Testing** | The checks pass and coverage matches what you promised |
| **9. Safety check** | Security review done, findings fixed or knowingly accepted |
| **10. Writing docs** | The documents describe what was actually built |
| **11. Going live** | Running where it's meant to run |
| **12. Confirming** | The people who asked agree it does what they asked |

---

## What to do

### Step 1 — Put every task in a stage
Take them from the task list. A task in no stage is an oversight. A stage with no tasks is noise.

### Step 2 — Write each stage out

```markdown
## Stage 6 — Building

| | |
|---|---|
| Checkpoint | MIL-002 |
| Where it stands | In progress · 40% |
| Can start when | The design is agreed and everyone can run it locally |
| Finished when | Every task in this stage is done and its tests pass |
| Aiming for | 15 August |
| Overlaps with | Stage 8 — testing starts as each part is finished |

**Tasks** — TASK-014, TASK-015, TASK-016
**Requirements this covers** — REQ-004, REQ-007, REQ-012
**What comes out of it** — the login section · the dispatch section · input checking
**Waits for** — Stage 5 finished · DEC-006 decided
**Watch out for** — RSK-003
```

### Step 3 — Set the checkpoints
A checkpoint isn't a date. It's a point where something is demonstrably true. Give each one a permanent label and write it as a heading, the same way as everything else:

```markdown
### MIL-002 — Someone can sign in and submit a request from beginning to end
**Closes** Stage 6 — Building · **Aiming for** 15 August · **Status** Not met yet
**What would prove it.** Someone doing it, watched: sign in, submit a request, see
it turn up in the dispatcher's list. TEST-011 and TEST-019 passing.
```

That's checkable in front of a room. "Backend complete" isn't. Each checkpoint names the stage it closes and what would prove it.

### Step 4 — Work out what actually blocks what
Which stages genuinely can't start until another finishes. **Most can't wait, and most don't need to.** Write the real picture, not a straight line from 1 to 12 because that's how the list is printed.

Testing usually starts during building. Documents get written as things are built, not after. Say so.

### Step 5 — Count the progress
A stage's progress is finished tasks divided by tasks in that stage. The project's progress is finished tasks divided by all tasks. **Counted from the task list, never estimated.** If it feels like 60% and the count says 34%, the count is right.

---

## What you'll end up with

```text
05-roadmap/roadmap.md      the stages
05-roadmap/milestones.md   the checkpoints and what proves each one
05-roadmap/dependencies.md what blocks what, and what doesn't
```
**Every document listed above starts with a short paragraph in ordinary words** — two or three sentences, before any table or heading. It's the only thing that makes these readable by the person who asked for the project.


---

## Before you call it finished
- [ ] Every task in the list belongs to exactly one stage
- [ ] Every stage says what has to be true before it starts and before it's finished
- [ ] Overlaps are stated where they exist
- [ ] Every checkpoint says what would prove it
- [ ] What blocks what reflects reality, not a default straight line
- [ ] Progress numbers are counted from the task list, not estimated
- [ ] Every document produced starts with a short plain-words paragraph

---

## Fill this in

**Task list:** `<where it is>`
**The plan:** `<where it is, or "none">`
**Deadline:** `<date, or "none">`
**Where to save the documents:** `<folder — usually the project's main folder>`
