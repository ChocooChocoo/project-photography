# Examples

> Reference file — what every document should look like. Copy from here instead of starting from scratch.
> Plain-language version of `09 - TEMPLATES.md` in `WORKFLOW - TECHNICAL/`. Same formats, simpler wording.

Every labelled item is written as a heading, so other documents can link straight to it.

---

## Something the system needs to do

```markdown
### REQ-004 — People sign in with an email address and a password
**Pile** What it does · **How important** Must have · **Came from** Your request, 31 July
**The statement.** People sign in using their email address and a password.
**Why.** Everything else about permissions depends on knowing who someone is.
**How you'd check it.** Correct details let you in. Wrong details show a general
"that didn't work" message and count toward the limit on failed attempts.
**Connected to** ANL-009 · GAP-005 · TASK-014 · TEST-011 · DEC-006
```

---

## Something you found while looking

```markdown
### ANL-009 — The login logic is written three separate times
**Where.** Sign-in and accounts
**What I found.** The code that creates a login session appears in three different
files, and the three copies no longer behave the same way.
**Proof.** `LoginController.php` lines 42-88, `ApiTokenController.php` lines 31-70,
`AdminController.php` lines 110-140
**Why it matters.** Changing how logins work means changing three files, and whoever
does it will probably miss one.
**Type.** Shortcut that needs fixing — medium priority
**Connected to** GAP-005 · TASK-014
```

**No proof means it isn't a finding.** Write it in the guesses list instead.

---

## A gap between now and what's needed

```markdown
### GAP-005 — No single place handles logins
**Now** Three copies of the same logic → **Needed** One place that handles it
**How serious** High · **How much work** Medium
**Comes from** ANL-009 · **Satisfies** REQ-004 · **Fixed by** TASK-014
```

---

## A task record

```markdown
# TASK-014 — 03.md — Build the login section

| | |
|---|---|
| My file | `prompts/tasks/03.md` |
| Where it stands | Ready · 0% · Important · Nobody assigned yet |
| Stage | Building · Checkpoint 2 · Aiming for 5 August |

**What it's for** _(your words, from `03.md`)_
> Replace the three copies of login handling with one section.

**Needs to start** — REQ-004 · ANL-009 · the current session file · two settings values
**Will produce** — one new login section · a database change · TEST-011 passing
**Waits for** — TASK-011, TASK-012 · **Holds up** — TASK-015
**Files it touches** — new login folder · one existing file changed

**Why this task exists** — three copies of the same logic that no longer match (ANL-009).
Approach: one new section rather than a shared piece — see DEC-006. Cost: three
existing files move over. Watch out for: RSK-003.

**How you'll know it's done**
- [ ] Correct details let someone in, and the timeout matches the setting
- [ ] Wrong details give a general message and count toward the attempt limit
- [ ] All three old files now use the new section _— suggested, not confirmed_

**Finished when** — built · all the above ticked · tests pass · documents and pictures
updated · links updated · proof recorded · nothing still blocking

**Proof** — saved change `—` · test run `—` · reviewed by `—`

**Notes** — 31 July: recorded from `03.md`, connected to GAP-005.
```

---

## The task list

```markdown
# Task List
_From: `prompts/tasks/` · 18 tasks · last updated 31 July_

| Task | Number | What it is | Where it stands | Progress | Waits for | File |
|---|---|---|---|---|---|---|
| 01 | TASK-011 | Set up the project | Done | 100% | — | `01.md` |
| 03 | TASK-014 | Build the login section | Ready | 0% | TASK-011, TASK-012 | `03.md` |

**Do this next:** TASK-014 — `03.md`, nothing is holding it up.
**Watch out for:** 2 unconfirmed dependencies · 1 task not connected to any
requirement · 3 suggestions waiting for your decision.
```

---

## A suggestion — not a task until accepted

```markdown
## Suggestion 1 — Limit how often the request form can be submitted
**Because of** REQ-019, GAP-008 · **Would go** after TASK-015 · **Size** Small

**Why it seems needed.** REQ-019 asks for protection against abuse on the public
request form. No task covers it, and the form is open to anyone in the new design.

**If you agree:** add it to your task folder using your own numbering and tell me.

**Status:** Waiting for your decision.
```

---

## Where things stand

```markdown
# Where Things Stand
_Updated 31 July · 34% done (11 of 32 tasks) · Currently: Building_
**Do this next:** start TASK-014

| Number | Task | Who | Where it stands | Progress | Aiming for | What's stopping it | Proof |
|---|---|---|---|---|---|---|---|
| TASK-011 | Set up the project | cyro | Done | 100% | 22 Jul | — | saved change `a3f21` |
| TASK-016 | Driver navigation | — | Stuck | 20% | 7 Aug | ISS-002 | — |

Not started 8 · Ready 3 · Being worked on 2 · Stuck 1 · Being tested 1 · Done 11
```

---

## A stage on the timeline

```markdown
## Stage 6 — Building

| | |
|---|---|
| Checkpoint | MIL-002 · In progress · 40% · Aiming for 15 August |
| Can start when | The design is agreed and everyone can run it locally |
| Finished when | Every task in this stage is done and its tests pass |
| Overlaps with | Stage 8 — testing starts as each part is finished |

**Tasks** — TASK-014, TASK-015, TASK-016
**Requirements this covers** — REQ-004, REQ-007, REQ-012
**What comes out of it** — the login section · the dispatch section · input checking
**Waits for** — Stage 5 finished · DEC-006 decided · **Watch out for** — RSK-003
```

---

## A decision

```markdown
### DEC-006 — Build one new login section instead of patching the three copies
**Date** 31 July · **Status** Agreed
**What led to this.** ANL-009, GAP-005
**Options we looked at.** (1) Patch the main sign-in file (2) Pull the shared bits
into one reusable piece (3) Build one new section
**We chose** option 3, because 1 and 2 both keep the inconsistent timeout behavior
that REQ-004 says can't stay.
**What this costs us.** Three existing files have to move over — TASK-014.
```

---

## Something that could go wrong

```markdown
### RSK-003 — Moving the login data might sign everyone out
**How likely** Medium · **How bad** High · **Who's watching it** cyro · **Status** Open
**What would set it off.** Releasing the TASK-014 database change.
**How to reduce it.** Read from both old and new during the changeover; release at
a quiet time of day.
**If it happens anyway.** There's an undo script ready.
**Connected to** TASK-014 · DEC-006
```

---

## Something currently blocking work

```markdown
### ISS-002 — The test server's database isn't available
**Since** 30 July · **How bad** Completely blocking · **Holds up** TASK-016
**What it means.** The joining-up tests can't run, so TASK-016 is stuck at 20%.
**What needs to happen.** Ask IT to bring the test database back.
**Fixed by.** —
```

---

## A check

```markdown
### TEST-011 — Correct details let someone sign in
**Level** Parts together · **Confirms** REQ-004 · **Covers** TASK-014
**Automated** yes, in the login test file · **Where it stands** Not run yet

**Before you start.** A person exists with a password you know.
**Steps.** 1. Send the correct email and password to the sign-in address.
2. Look at what comes back and what's stored.
**What should happen.** It succeeds; a session is stored; the timeout matches the setting.
**What actually happened.** —
```

---

## The tracking table

```markdown
| Requirement | What it is | Found | Gap | Tasks | Checks | Where it stands |
|---|---|---|---|---|---|---|
| REQ-004 | Email and password sign-in | ANL-009 | GAP-005 | TASK-014, TASK-017 | TEST-011 | Being worked on |

**Holes:** requirements with no task — REQ-019 (see suggestion 1) · requirements with
no check — REQ-018 · tasks not connected to any requirement — TASK-016
```

---

## Guesses and open questions

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

---

## The version anyone can read

```markdown
# Where the project stands
_Updated 31 July — about a third of the work is done_

Signing in and the database groundwork are finished and tested. The login rebuild
starts next and should take about a week. Driver navigation is on hold until the
test server is back — nothing else is waiting on it. Everything else is on track
for 22 August.
```

Same numbers and dates as the detailed version. Simpler words are fine; softer facts are not.

---

## The word list

```markdown
| Technical word | What it means | Why it matters |
|---|---|---|
| Session timeout | How long a sign-in lasts before you have to do it again | Balances convenience against someone else using your account |
| Migration | A repeatable, written-down database change | Lets the database be rebuilt the same way on any machine |
| RBAC | Deciding what each type of user is allowed to do | Keeps students, staff, and admins out of each other's information |
```

Any technical word that appears in a document meant for non-technical readers needs a line here.
