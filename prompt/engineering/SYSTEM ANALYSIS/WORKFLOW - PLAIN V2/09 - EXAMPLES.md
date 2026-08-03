# Examples — the four things you can't find in the other files

> Reference sheet, not a prompt. Part of the System Analysis Workflow v2 — see `00 - START HERE.md`.
> **Technical twin:** `09 - TEMPLATES.md` in `WORKFLOW - TECHNICAL V2/`. Same formats, engineering words.

**Why this file got shorter.** It used to repeat eleven examples that files `01`–`08` already show in full. Two copies of the same example means one of them quietly goes out of date, and nothing tells you which one you're looking at. So every example now lives in exactly one place — the file that produces it — and this sheet keeps only the four that appear nowhere else, plus a map to the rest.

Every labelled item is written as a heading, so other documents can link straight to it.

---

## Where to find each example

| Example | It's in |
|---|---|
| Something the system needs to do — `REQ-004` | `02 - WRITE DOWN WHATS NEEDED.md` |
| Something you found while looking — `ANL-009`, both the code and the document kind | `01 - LOOK AT WHAT EXISTS.md` |
| A gap between now and what's needed — `GAP-005` | `01 - LOOK AT WHAT EXISTS.md` |
| A decision and its reason — `DEC-006` | `03 - MAKE A PLAN.md` |
| A task record, and the task list table | `04 - KEEP TRACK OF TASKS.md` |
| A suggestion — `PRO-001` | `04 - KEEP TRACK OF TASKS.md` |
| A stage, and a checkpoint — `MIL-002` | `05 - PUT IT ON A TIMELINE.md` |
| Where things stand, and the version anyone can read | `06 - CHECK PROGRESS.md` |
| Something blocking work — `ISS-002` | `06 - CHECK PROGRESS.md` |
| A check — `TEST-011`, and the results table | `07 - MAKE SURE IT WORKS.md` |
| A picture — `DGM-004` | `08 - DRAW THE PICTURES.md` |

Change an example in the file it belongs to and it's changed everywhere. Nothing below repeats any of them.

---

## Something that could go wrong

No single stage produces these. They get written whenever a plan or a task turns one up, which is why the example sits here instead of in one of the numbered files.

```markdown
### RSK-003 — Moving the login data might sign everyone out
**How likely** Medium · **How bad** High · **Who's watching it** cyro · **Status** Open
**What would set it off.** Releasing the TASK-014 database change.
**How to reduce it.** Read from both old and new during the changeover; release at
a quiet time of day.
**If it happens anyway.** There's an undo script ready.
**Connected to** TASK-014 · DEC-006
```

A risk with no "what would set it off" is just a worry. If you can't say what triggers it, you can't tell whether it's still a live concern or something that passed weeks ago.

---

## Guesses and open questions

Two kinds of thing share one file. A guess is something you decided yourself and are willing to be wrong about. A question is something only you can answer. They live together so there's one page to check before a meeting.

```markdown
### ASM-002 — Assuming the newer database version, not the older one
Based on the setup file naming version 8. If the live server runs the older version,
one of the planned table columns won't work at all. **Affects** TASK-012.

### QST-003 — REQ-004 and REQ-011 contradict each other
REQ-004 says people get logged out after 30 minutes. REQ-011 says drivers stay signed
in for a whole shift. Both can't be true for the same person.
**Possible answers.** (a) Different limits per role (b) Drivers get a way to stay
signed in (c) Drop the 30-minute limit
**What this holds up.** Anything to do with signing in. **Waiting on.** Your decision.
```

A guess with no reason attached is just a guess wearing a label. Say what it's based on — so the day that turns out to be wrong, you can find everything it affected.

---

## The tracking table

Started in `02 - WRITE DOWN WHATS NEEDED.md` and filled in by every step after it. This is what it looks like once every column has something in it.

```markdown
| Requirement | What it is | Found | Gap | Tasks | Tests | Where it stands |
|---|---|---|---|---|---|---|
| REQ-004 | Email and password sign-in | ANL-009 | GAP-005 | TASK-014, TASK-017 | TEST-011 | Being worked on |

**Holes:** requirements with no task — REQ-019 (see PRO-001) · requirements with
no check — REQ-018 · tasks not connected to any requirement — TASK-016
```

The "holes" line is the reason the table exists. A tracking table with no holes listed usually means nobody looked, not that there aren't any.

---

## The word list

```markdown
| Technical word | What it means | Why it matters |
|---|---|---|
| Session timeout | How long a sign-in lasts before you have to do it again | Balances convenience against someone else using your account |
| Migration | A repeatable, written-down database change | Lets the database be rebuilt the same way on any machine |
| RBAC | Deciding what each type of user is allowed to do | Keeps students, staff, and admins out of each other's information |
```

Any technical word that appears in a document meant for non-technical readers needs a line here. The third column is what makes the list worth writing — the middle column tells your panel what the word means, the last one tells them why it matters that you got it right.
