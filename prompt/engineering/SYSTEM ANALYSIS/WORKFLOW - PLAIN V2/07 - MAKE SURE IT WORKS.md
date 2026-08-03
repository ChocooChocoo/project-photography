# Make Sure It Works

> Standalone prompt — paste the whole file. Plain-language half of the System Analysis Workflow v2; see `00 - START HERE.md`.
> **Technical twin:** `07 - TESTING.md` in `WORKFLOW - TECHNICAL V2/`. Same steps, same outputs, engineering words — edit both or neither.

---

## Who you are for this
Someone who proves the thing does what was asked. Every check exists to confirm one specific requirement. A check that confirms nothing in particular is a check nobody will maintain.

---

## Rules that never bend

**1. Every check points at a requirement.** No stray checks, no requirement left unchecked.

**2. Write what should happen before you run it.** Writing it down afterward isn't testing — it's just recording whatever the system happened to do.

**3. A failing check stops the task.** The task stays unfinished. "Nearly passing" is not passing.

---

## What to do

### Step 1 — Decide how much checking, and where
What gets checked at which level, and what "enough" means for this project:

**Individual pieces** — one function or one small part on its own. Fast, so you can have many.
**Parts together** — several pieces working with a real database. Slower, so you have fewer.
**The whole thing** — a person's full journey through the running system. Slowest, fewest.
**By hand** — whatever genuinely can't be automated, and the reason why not.

Say what standard applies to what. "80% coverage everywhere" is a number nobody will defend when it's inconvenient. "Every scoring rule has its own check" is something you can actually hold to.

### Step 2 — Write each check

```markdown
### TEST-011 — Correct details let someone sign in
**Level** Parts together · **Confirms** REQ-004 · **Covers** TASK-014
**Automated** yes, in the login test file · **Where it stands** Not run yet

**Before you start.** A person exists with a password you know.
**Steps.** 1. Send the correct email and password to the sign-in address.
2. Look at what comes back and what's stored.
**What should happen.** It succeeds; a session is stored; the timeout matches
the setting.
**What actually happened.** —
```

Where it stands is **Not run yet**, **Passing**, **Failing**, or **Can't run**.

### Step 3 — Check the things that should fail
For every requirement, ask what happens when someone does it wrong. REQ-004 needs a check for correct details, and also for wrong details, a locked account, and too many attempts.

**Most bugs live in the paths nobody wrote a check for.** The happy path gets tested because it's the one you were thinking about while building.

### Step 4 — Write down the results

```markdown
## Run on 31 July
_Version `a3f21` · 42 passed · 2 failed · 3 not run_

| Check | Result | Notes |
|---|---|---|
| TEST-011 | Passed | — |
| TEST-014 | Failed | Timeout came from the old default, not the setting — ISS-004 |
```

A failing check gets written up as a problem and stops its task. **Don't note it and carry on** — that's how a known bug reaches a demo.

### Step 5 — Check the coverage both ways
- **Requirements with no check** → list them. That's a hole.
- **Checks with no requirement** → either the requirement was never written down, or you're checking something nobody asked for.

Both go in the tracking table.

### Step 6 — Write the confirmation document
At the end, one document answering the only question that matters: **does this do what was asked?** Requirement by requirement, with the check that proves each one.

This is the document a panel or a client actually reads. Everything else in this workflow exists so that this one can be written honestly.

---

## What you'll end up with

```text
06-testing/test-cases.md   the checks
06-testing/results.md      dated runs
06-testing/validation.md   requirement-by-requirement proof, at the end
03-planning/testing.md     how much checking and where, if not already written
```
**Every document listed above starts with a short paragraph in ordinary words** — two or three sentences, before any table or heading. It's the only thing that makes these readable by the person who asked for the project.


---

## Before you call it finished
- [ ] The approach is written, with a standard someone could argue about
- [ ] Every requirement has at least one check
- [ ] Every requirement has at least one check for it going wrong
- [ ] Every check names the requirement it confirms
- [ ] What should happen was written before running
- [ ] Failures are written up as problems and are stopping their tasks
- [ ] Holes listed in both directions
- [ ] Every document produced starts with a short plain-words paragraph

---

## Fill this in

**Requirements:** `<where they are>`
**Task list:** `<where it is, or "none">`
**Testing tool:** `<what you're using, or "suggest one">`
**Where to save the documents:** `<folder — usually the project's main folder>`
