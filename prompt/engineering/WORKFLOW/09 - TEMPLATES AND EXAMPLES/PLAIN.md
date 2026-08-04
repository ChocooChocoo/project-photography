# Templates and examples — the four formats not shown elsewhere

> Reference file, not a prompt.

## Why this file is short

Eleven formats already have a complete example in the document that creates them. Keeping two copies of a format makes one copy likely to become outdated, with no clear way to know which is correct. Each format therefore has one home; this file keeps only the four formats that no other prompt shows, plus the list below.

Stage 09 is reference-only by default and creates no required project document. Write every identifier as a heading so another generated document can link directly to it. From a materialized Stage 09 plain file, for example: `[REQ-004](../../02%20-%20REQUIREMENTS/PLAIN/requirements.md#req-004)`.

## Where each format is defined

| What this is | Plain-language workflow stage |
|---|---|
| A requirement — what the system must do — `REQ-###` | `../02 - REQUIREMENTS/PLAIN.md` |
| An analysis finding — something discovered in the code or documents — `ANL-###` | `../01 - SYSTEM ANALYSIS/PLAIN.md` |
| A gap — the difference between current and needed — `GAP-###` | `../01 - SYSTEM ANALYSIS/PLAIN.md` |
| A decision — the chosen approach and reason — `DEC-###` | `../03 - PLANNING/PLAIN.md` |
| A task record and task list | `../04 - TASK TRACKING/PLAIN.md` |
| A proposed task `PRO-###` | `../04 - TASK TRACKING/PLAIN.md` |
| A roadmap phase and milestone `MIL-###` | `../05 - ROADMAP/PLAIN.md` |
| Progress tracking and a status anyone can read | `../06 - PROGRESS TRACKING/PLAIN.md` |
| An issue — something blocking or needing attention — `ISS-###` | `../06 - PROGRESS TRACKING/PLAIN.md` |
| A test case `TEST-###` and its results | `../07 - TESTING/PLAIN.md` |
| A diagram `DGM-###` | `../08 - DIAGRAMS/PLAIN.md` |

`REQ`, `ANL`, `GAP`, `DEC`, `PRO`, `MIL`, `ISS`, `TEST`, and `DGM` are numbered reference labels: respectively requirement, analysis finding, shortfall, decision, proposed task, milestone, issue, test, and diagram. Keep each format in the stage where it belongs; the examples below do not duplicate those formats.

## Generated homes for the four reference formats

| What this is | Plain generated home |
|---|---|
| Risk | `docs/WORKFLOW/06 - PROGRESS TRACKING/PLAIN/risks.md` |
| Open items | `docs/WORKFLOW/00 - START HERE/PLAIN/open-items.md` |
| Tracking table | `docs/WORKFLOW/02 - REQUIREMENTS/PLAIN/traceability.md` |
| Word list or another project reference | A needed file such as `docs/WORKFLOW/09 - TEMPLATES AND EXAMPLES/PLAIN/glossary.md`, only if the project materializes it |

The first three belong to their owning stages, not Stage 09. Do not create a Stage 09 output merely because an example appears here.

## Risk — `docs/WORKFLOW/06 - PROGRESS TRACKING/PLAIN/risks.md`

Risks are not created on a fixed schedule. Add one whenever a plan or task reveals something that could go wrong, which is why this format has its own location.

```markdown
### RSK-003 — Session migration may log out active users
**Likelihood** Medium · **Impact** High · **Owner** cyro · **Status** Open
**Trigger.** Deploying the TASK-014 migration.
**Mitigation.** Keep the old and new sign-in records available during a transition period; make the change when few people are active.
**Contingency.** Return to the previous sign-in process.
**Related** TASK-014 · DEC-006
```

`RSK-003` is the risk reference. A session is a saved sign-in record. A migration is a controlled change to stored information. The **Likelihood** field says how likely the problem is, **Impact** says how serious it would be, **Owner** says who watches it, **Trigger** says what would set it off, **Mitigation** says how to reduce the chance or effect, and **Contingency** gives the fallback action.

A risk without a trigger is only a worry: if you cannot say what would set it off, you cannot tell whether it is still active.

## Open items — `docs/WORKFLOW/00 - START HERE/PLAIN/open-items.md`

Two labels share this file. `ASM-###` is an assumption you made and are prepared to revise. `QST-###` is a question that only the owner can answer. Keeping both in one place gives the team one page to check before a meeting.

```markdown
### ASM-002 — Assuming all active users can sign in again after the change
Based on the owner confirming that no uninterrupted sign-in is required. If that
is incorrect, some active work may be interrupted. **Affects** TASK-012.

### QST-003 — REQ-004 conflicts with REQ-011
REQ-004 requires sign-in to end after 30 minutes. REQ-011 requires drivers to stay signed
in for a full shift. Both cannot hold for the same user.
**Options.** (a) Different limits by user type (b) Let drivers continue without signing in again (c) Drop the limit
**Blocks.** Any task that changes how long sign-in lasts. **Needs.** Your decision.
```

**Affects** names work influenced by the item, **Blocks** names work that must wait, and **Needs** names the decision required.

An assumption with no stated basis is only a guess. State what it relies on so that, if it proves false, every affected item can be found.

## Tracking table — `docs/WORKFLOW/02 - REQUIREMENTS/PLAIN/traceability.md`

Start this table in Stage 02 and have every later stage fill it in. It connects each requirement to the analysis, gap, tasks, tests, and current state that support it.

```markdown
| REQ | Requirement | Analysis | Gap | Tasks | Tests | Status |
|---|---|---|---|---|---|---|
| REQ-004 | Email/password auth | ANL-009 | GAP-005 | TASK-014, TASK-017 | TEST-011 | In Progress |

**Coverage gaps:** requirements with no task — REQ-019 (proposed PRO-001) ·
requirements with no test — REQ-018 · tasks with no requirement — TASK-016
```

The tracking table is also called a traceability matrix: it follows each requirement through the work and proof for it. **Requirement** says what the system must do; **Analysis** names what was found; **Gap** names the difference between current and needed; **Tasks** names the work; **Tests** names the proof; and **Status** says its current state. In the example, “Email/password auth” means signing in with an email address and password. The coverage-gaps line is the purpose of the table. A matrix with no gaps listed usually means nobody checked, not that no gaps exist.

## Word list — optional paired Stage 09 file

```markdown
| Technical term | In plain words | Why it matters |
|---|---|---|
| Session TTL | How long a login lasts before signing in again | Balances convenience against account safety |
| Migration | A scripted, repeatable database change | Lets the database be rebuilt identically anywhere |
| RBAC | Deciding what each kind of user is allowed to do | Keeps students, faculty, and admins out of each other's data |
```

`RBAC` means role-based access control: deciding what each kind of user is allowed to do. Any specialist term in a document for non-technical readers needs an entry here. The plain-words column explains the term, while **Why it matters** explains why the reader should care that it is correct.

## Files to produce

None by default. If this project needs a word list, reference, template, or example file, create only that needed filename under both audience folders, for example:

```text
docs/WORKFLOW/09 - TEMPLATES AND EXAMPLES/
|- PLAIN/glossary.md
`- TECHNICAL/glossary.md
```

The relative filename must be identical in both folders. Add, delete, or rename both copies together. Both copies must carry the same facts, statuses, dates, decisions, risks, and outcomes. The Plain copy must be self-contained and contain no code, programming-language details, implementation notation, or link to Technical.

## Done when

- [ ] No Stage 09 project file was created unless the project actually needs it
- [ ] Risks are kept in Stage 06, open items in Stage 00, and traceability in Stage 02
- [ ] Every materialized Stage 09 relative filename exists under both audience folders
- [ ] Both audience copies agree on facts, statuses, dates, decisions, risks, and outcomes
- [ ] Plain files are independent and contain no code, programming-language detail, implementation notation, or link to Technical
