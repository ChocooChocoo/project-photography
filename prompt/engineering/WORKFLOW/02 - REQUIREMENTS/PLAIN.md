# Write Down What Is Needed

> Standalone prompt: paste this whole file into your assistant.

## Your job

Turn what is actually being requested into clear, checkable needs, sort them into the right category, and raise everything that is still unclear. Do not decide how the solution will be built.

## Rules that never bend

1. **Only include what was asked for.** Do not invent requirements the user did not state or imply.
2. **Raise ambiguity; do not settle it.** When two readings are possible, create a `QST-###` and continue with work that does not need the answer.
3. **Every requirement must be checkable.** If you cannot write a checkable proof of success (acceptance condition), it is not yet a requirement.

### Generated-document contract

- Keep this source prompt where it is. Put generated project documents under `docs/WORKFLOW/<numbered stage>/<audience>/`, where the audience folders are `PLAIN` and `TECHNICAL`.
- Create every generated filename in both audience folders. Add, delete, or rename the pair together, and keep facts, statuses, dates, decisions, risks, and outcomes aligned.
- The plain version must stand alone and contain no code, programming-language names, implementation syntax, or link or dependency on the technical version.
- The technical version contains the matching engineering detail, including stored-information design, system connections, file locations, chosen tools, and setup settings.
- Create only the listed files the project needs; any file created must have its matching audience copy.

## What to do

### 1. Extract and classify every requirement

Put each requirement in exactly one category:

| Category | Covers |
|---|---|
| **Functional** | What the system does |
| **Non-functional** | How well it works: speed, availability, capacity, usability |
| **Technical** | Required tools: the chosen languages and tools (stack), platform, main software foundation (framework), version limits |
| **Business** | Rules, policies, and business limits |
| **Security** | Sign-in checks (authentication), access rights, data protection, and following required rules (compliance) |
| **Data** | What is stored, how long it is kept (retention), accuracy, and safe movement of existing data (migration) |
| **Integration** | External systems, program-to-program connections (APIs), third parties |
| **Deployment** | Where the system runs, how it is released, and the equipment or services it uses |
| **Documentation** | What must be written and for whom |
| **Testing** | What must be verified and to what standard |

### 2. Record each requirement as `REQ-###`

```markdown
### REQ-004 - Users authenticate with email and password
**Category** Functional · **Priority** Must · **Source** User request, 2026-07-31
**Statement.** The system shall authenticate users by email address and password.
**Why.** Every other access-rights check depends on knowing who the user is.
**Acceptance.** A valid email-and-password pair starts a signed-in session; an invalid pair returns a
general failure and increments the failed-attempt (throttle) counter.
**Traces to** ANL-009 · GAP-005 · (task and test linked later)
```

The priority must be `Must`, `Should`, or `Could`. Nothing is `Must` by default.

### 3. Record what is not a requirement

This is half the value of this stage. Update both `docs/WORKFLOW/00 - START HERE/PLAIN/open-items.md` and `docs/WORKFLOW/00 - START HERE/TECHNICAL/open-items.md`, keeping the same items and status while expressing each for its audience. Record risks separately in both Stage 06 `risks.md` files and link them from the relevant requirement and open item:

- **Missing information:** what you need to know before you can write a complete requirement.
- **Ambiguity:** statements with more than one reasonable reading.
- **Assumptions (`ASM-###`):** what you filled in yourself and what supports it.
- **Constraints:** fixed budget, deadline, team, platform, or anything else fixed.
- **Outside needs (dependencies):** what this needs from outside the project.
- **Risks (`RSK-###`):** what could make a requirement impossible to achieve; its permanent home is Stage 06 `risks.md`, not `open-items.md`.
- **Conflicts:** name conflicting requirements as pairs.

```markdown
### QST-003 - REQ-004 conflicts with REQ-011
REQ-004 requires a session TTL (time limit) of 30 minutes. REQ-011 requires drivers to stay
logged in for a full shift, up to 12 hours. Both cannot hold for the same user.

**Options.** (a) Role-based TTL (b) Refresh tokens, a way to extend sign-in time, for drivers (c) Drop REQ-004's limit
**Blocks.** Any task touching session handling.
**Needs.** Your decision.
```

### 4. Check coverage against the analysis

If the analysis stage was run, compare the records. Raise a question when a `GAP-###` has no requirement explaining why it matters, or when a requirement conflicts with something the analysis found already exists.

## Required output

```text
docs/WORKFLOW/02 - REQUIREMENTS/
|- PLAIN/{requirements.md, traceability.md}
`- TECHNICAL/{requirements.md, traceability.md}

Update the paired existing files:
docs/WORKFLOW/00 - START HERE/PLAIN/open-items.md
docs/WORKFLOW/00 - START HERE/TECHNICAL/open-items.md
docs/WORKFLOW/06 - PROGRESS TRACKING/PLAIN/risks.md (when risks are found)
docs/WORKFLOW/06 - PROGRESS TRACKING/TECHNICAL/risks.md (when risks are found)
```

Start the traceability matrix, the tracking table that connects each requirement to related findings, gaps, tasks, and tests. Fill the columns you can now and leave the rest for later stages:

```markdown
| REQ | Requirement | Analysis | Gap | Tasks | Tests | Status |
|---|---|---|---|---|---|---|
| REQ-004 | Email/password auth | ANL-009 | GAP-005 | - | - | Not Started |
```

Every listed document starts with an **In plain terms** block of two to four sentences before any table or heading.

Generated links must stay in the same audience. For example, the plain tracking table links to `[GAP-005](../../01%20-%20SYSTEM%20ANALYSIS/PLAIN/gaps.md#gap-005)`, while its technical counterpart links to the matching file under `TECHNICAL`.

## Done when

- [ ] Every requirement is in exactly one category.
- [ ] Every requirement has a checkable proof of success (acceptance condition).
- [ ] Every requirement has a priority that was actually chosen.
- [ ] Conflicts are named as pairs and raised as questions, never silently settled.
- [ ] Assumptions are logged separately from requirements.
- [ ] The traceability matrix has been started.
- [ ] Nothing was invented that the user did not ask for.
- [ ] Every produced document starts with an **In plain terms** block.
- [ ] `requirements.md` and `traceability.md` exist in both audience folders, and the paired facts, statuses, dates, decisions, risks, and outcomes agree.
- [ ] Both Stage 00 `open-items.md` files were updated together.
- [ ] Every `RSK-###` found here is recorded in both Stage 06 `risks.md` files and linked from the relevant requirement and open item.
- [ ] Plain documents stand alone without code, programming-language names, implementation syntax, or links or dependencies to technical documents.

## Input

**Request:** `<what you want built, changed, or fixed>`

**Existing analysis:** `<paths to the paired docs/WORKFLOW/01 - SYSTEM ANALYSIS/ audience folders, or "none">`

**Constraints:** `<chosen tools (stack), deadlines, standards - or "none">`

**Docs go in:** `<project root - generated files use docs/WORKFLOW/>`
