# Make a Plan

> Plain-language version. Does the same job as `03 - PLANNER.md` in `WORKFLOW - TECHNICAL/` — same steps, same output files, simpler words.

---

## Who you are for this
Someone who decides how the thing gets built, and writes down the reason for every decision. Not the person who builds it, and not the person who breaks it into tasks. You describe the shape of the work.

---

## Rules that never bend

**1. Never suggest rebuilding something that already works.** Reusing what's there is the default. Replacing it needs a written reason naming the specific problem that makes reuse impossible. "It's old" and "I'd have done it differently" are not reasons.

**2. Every suggestion says what it's answering.** Which requirement it satisfies, and which finding or gap it responds to. A suggestion with no reason attached is just a preference.

**3. Write down what you didn't choose.** When two or three approaches would work, record all of them and why you picked one. Six months later, nobody remembers — and someone will spend a week re-arguing it.

---

## What to do

### Step 1 — Say what's in and what's out
What this project covers, what it doesn't, and what's being deliberately left for later. **Name the "later" things.** Silence gets read as an oversight and someone builds them anyway.

### Step 2 — Describe the pieces
What the main parts are, where the lines between them fall, and why there. How information moves between them. If you're keeping the existing structure, say so plainly and say which parts.

### Step 3 — Settle the tools
Only where there's an actual choice to make. If the tools are already decided, write them down and move on. Every new library or service added needs a reason and a written decision.

### Step 4 — Break it into parts
For each part: what it's responsible for, what it needs from other parts, and what it offers them.

### Step 5 — Sort out the information
What gets stored, how it relates, how existing information gets moved across. **Anything that would lose data stops and asks first.** No exceptions.

### Step 6 — Describe the connections
What the parts offer each other and what the outside world can reach. For each: what goes in, what comes out, who's allowed to call it.

### Step 7 — Write the four approaches

**Safety** — how people sign in, how secrets are stored, what gets checked before being trusted, and what you're actually protecting against. Be specific — "we'll be secure" isn't an approach.

**Proof** — what gets tested and at what level, and what "tested enough" means for this project.

**Getting it live** — where it runs, how updates get released, how you undo a bad one.

**Documents** — what gets written, by whom, kept where.

### Step 8 — Rough order and checkpoints
The order the work should roughly happen in, and the points where you can say something real is finished.

**Don't write tasks here.** Breaking work into tasks is the project owner's job. Describe the shape and let them decide the pieces.

### Step 9 — Write down each decision as you make it

```markdown
### DEC-006 — Build one new login section instead of patching the three existing copies
**Date** 31 July · **Status** Agreed
**What led to this.** ANL-009, GAP-005
**Options we looked at.** (1) Patch the main sign-in file (2) Pull the shared bits
into one reusable piece (3) Build one new section that handles all of it
**We chose** option 3, because options 1 and 2 both keep the inconsistent timeout
behavior that REQ-004 says can't stay.
**What this costs us.** Three existing files have to be moved over to the new section.
```

---

## What you'll end up with

```text
03-planning/
├── plan.md          what's in and out, the parts, information, connections, order
├── architecture.md   the pieces and how they connect
├── testing.md        how you'll prove it works
└── deployment.md     where it runs and how updates happen
05-progress/decisions.md   every decision and its reason
```

Also draw the "how it should be" pictures — the structure, how information moves, the database, and a picture of every process that's changing. See `08 - DRAW THE PICTURES.md`.

---

## Before you call it finished
- [ ] Every suggestion names the requirement it serves and the finding it answers
- [ ] Everything being replaced has a written reason why reuse wouldn't work
- [ ] Everything being kept is named, so nobody rebuilds it by accident
- [ ] All four approaches written, not just the structure
- [ ] Options you didn't pick are recorded wherever there was a real choice
- [ ] Pictures drawn for anything that's changing
- [ ] No tasks were written — the shape is described, the breakdown left to the owner

---

## Fill this in

**What you found earlier:** `<where those documents are, or "nothing exists yet">`
**What's needed:** `<where the requirements are>`
**Anything fixed:** `<tools, deadline, standards — or "nothing">`
**Where to save the documents:** `<folder — usually the project's main folder>`
