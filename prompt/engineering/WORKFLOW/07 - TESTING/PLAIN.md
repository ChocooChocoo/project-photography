# Testing — prove each requirement actually works

> Standalone prompt — paste the whole file.

## Your responsibility

You prove that the system does what was requested. Every test, meaning a written check, must confirm one named requirement. A test that confirms nothing named is a test nobody can reasonably maintain.

## Rules that do not change

1. **Every test must point to a requirement.** Do not leave a test without a requirement, and do not leave a requirement without a test.
2. **Write the expected result before running the test.** Writing it after the run only records what happened; it does not prove what should have happened.
3. **A failing test stops completion.** Its task remains `In Progress`. “Mostly passing” is not passing.
4. **Keep the two audiences paired.** Every generated filename must exist under both the `PLAIN` and `TECHNICAL` folders for this stage. Add, delete, or rename both copies together.
5. **Keep the plain copy independent.** It must carry the same facts, statuses, dates, decisions, risks, and outcomes as the technical copy, but must not contain code, programming-language details, implementation notation, or links to the technical copy.

## What to do

### 1. Decide the testing approach first

State what will be checked at each level and what “enough” means for this project.

- **Unit test** — checks one function or class, meaning one small piece of the system. These are fast, so there can be many.
- **Integration test** — checks parts working together, including the real database. These are fewer and slower.
- **End-to-end test** — checks a complete person’s journey through the running system. These are the fewest.
- **Manual test** — a check performed by a person. Use this only for something that genuinely cannot be automated, and state why.

State the coverage target and what it applies to. “80% everywhere” is a number with no clear purpose. “Every scoring rule has a unit test” is a standard that can be checked and discussed.

### 2. Write the test cases

Use this format. `TEST-011`, `REQ-004`, and `TASK-014` are permanent reference numbers for the test, requirement, and task. “Integration” means the test checks parts working together; “Automated” gives the file that runs the check.

```markdown
### TEST-011 — Valid credentials create a session
**Type** Integration · **Verifies** REQ-004 · **Covers** TASK-014
**Check method** Automated · **Status** Not Run

**Preconditions.** A user exists with a known password.
**Steps.** 1. Sign in with the known email address and password. 2. Confirm that the person is signed in for the agreed length of time.
**Expected.** Sign-in succeeds and lasts for the agreed length of time.
**Actual.** —
```

In this example, a session is the saved sign-in record. The agreed length of time says how long that sign-in lasts.

Use only these status values: `Not Run`, `Passing`, `Failing`, or `Blocked`.

### 3. Check what should go wrong too

For every requirement, ask what happens when it is broken or misused. `REQ-004` needs a test for valid credentials and tests for invalid credentials, a locked account, and the throttle limit (the maximum number of allowed repeated attempts).

Most defects are in paths nobody thought to test.

### 4. Record the results

```markdown
## Run 2026-07-31
_Saved version a3f21 · 42 passing · 2 failing · 3 not run_

| Test | Status | Notes |
|---|---|---|
| TEST-011 | Passing | — |
| TEST-014 | Failing | Sign-in lasted for the fallback time instead of the agreed time — ISS-004 |
```

A saved version identifies the exact work being checked. `ISS-004` is the reference number for the recorded issue. Every failing test must receive an `ISS-###`, and that issue blocks its task. Do not merely note a failure and continue.

### 5. Check coverage in both directions

- **Requirements with no test** — list them; they are a gap.
- **Tests with no requirement** — either the requirement was never written, or the test checks something nobody requested.

Put both lists in the coverage section of the traceability matrix, the table that connects each requirement to the work and proof for it.

### 6. Write the validation report

Finish with one document that answers: does the system do what was asked? Show each requirement and the test that proves it. This is the document a panel or client can use to confirm the result.

## Files to produce

```text
docs/WORKFLOW/07 - TESTING/
|- PLAIN/
|  |- test-cases.md   strategy, TEST entries, and coverage
|  `- results.md      dated runs, issues, and final validation
`- TECHNICAL/
   |- test-cases.md   matching technical record
   `- results.md      matching technical record
```

These are the complete Stage 07 filenames. Create only what is needed, but every created file must exist in both audience folders with the same relative filename. Add, delete, and rename paired files together.

Put the strategy, test cases, and coverage lists in `test-cases.md`. Put dated runs, linked issues, and the final requirement-by-requirement validation report in `results.md`. Every document listed above must open with an `In plain terms` block: two to four sentences before any table or heading. It gives the people who commissioned the work a readable explanation before the detail.

## Done when

- [ ] Strategy written, with a coverage standard someone could argue about
- [ ] Every requirement has at least one test
- [ ] Every requirement has at least one negative-case test
- [ ] Every test names the requirement it verifies
- [ ] Expected results written before running
- [ ] Failures logged as issues and blocking their tasks
- [ ] Coverage gaps listed in both directions
- [ ] `test-cases.md` and `results.md` exist under both audience folders with matching relative filenames
- [ ] Both audience copies agree on facts, statuses, dates, decisions, risks, and outcomes
- [ ] Plain files contain no code, programming-language detail, implementation notation, or link to Technical
- [ ] Every document produced opens with an `In plain terms` block

## Input to provide

**Requirements:** `<path to docs/WORKFLOW/02 - REQUIREMENTS/PLAIN/>`
**Task index:** `<path to docs/WORKFLOW/04 - TASK TRACKING/PLAIN/index.md, or "none">`
**Test framework:** `<what's in use, or "recommend one">` — the test framework is the tool used to run automated checks.
**Docs go in:** `<path — default: docs/WORKFLOW>`
