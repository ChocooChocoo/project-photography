# Templates — the four formats no prompt already shows

> Reference file, not a prompt. Part of the System Analysis Workflow v2 — see `../00 - START HERE/TECHNICAL.md`.
> **Plain counterpart:** `PLAIN.md` in this folder.

**Why this file is short.** In v1 it repeated eleven formats that prompts `01`–`08` already showed in full. Two copies of a format means one of them silently goes stale, and nothing tells you which. So each format now has exactly one home — the prompt that produces it — and this file holds only the four that no prompt shows, plus a map to the rest.

Stage 09 is reference-only by default and creates no required project document. Every identifier is declared as a heading so it can be linked. From a materialized Stage 09 technical file: `[REQ-004](../../02%20-%20REQUIREMENTS/TECHNICAL/requirements.md#req-004)`

---

## Where each format is defined

| Format | Defined in |
|---|---|
| Requirement `REQ-###` | `../02 - REQUIREMENTS/TECHNICAL.md` |
| Analysis finding `ANL-###` — code and document variants | `../01 - SYSTEM ANALYSIS/TECHNICAL.md` |
| Gap `GAP-###` | `../01 - SYSTEM ANALYSIS/TECHNICAL.md` |
| Decision `DEC-###` | `../03 - PLANNING/TECHNICAL.md` |
| Task record + task index | `../04 - TASK TRACKING/TECHNICAL.md` |
| Proposed task `PRO-###` | `../04 - TASK TRACKING/TECHNICAL.md` |
| Roadmap phase + milestone `MIL-###` | `../05 - ROADMAP/TECHNICAL.md` |
| Progress tracker + plain-language status | `../06 - PROGRESS TRACKING/TECHNICAL.md` |
| Issue `ISS-###` | `../06 - PROGRESS TRACKING/TECHNICAL.md` |
| Test case `TEST-###` + results table | `../07 - TESTING/TECHNICAL.md` |
| Diagram `DGM-###` | `../08 - DIAGRAMS/TECHNICAL.md` |

Change a format in its home file and it's changed everywhere. Nothing below duplicates any of them.

## Generated homes for the four reference formats

| Format | Technical generated home |
|---|---|
| Risk | `docs/WORKFLOW/06 - PROGRESS TRACKING/TECHNICAL/risks.md` |
| Open items | `docs/WORKFLOW/00 - START HERE/TECHNICAL/open-items.md` |
| Traceability matrix | `docs/WORKFLOW/02 - REQUIREMENTS/TECHNICAL/traceability.md` |
| Glossary or another project reference | A needed file such as `docs/WORKFLOW/09 - TEMPLATES AND EXAMPLES/TECHNICAL/glossary.md`, only if the project materializes it |

The first three belong to their owning stages, not Stage 09. Do not create a Stage 09 output merely because an example appears here.

---

## Risk — `docs/WORKFLOW/06 - PROGRESS TRACKING/TECHNICAL/risks.md`

No stage produces these on a schedule; they get written whenever a plan or a task surfaces one. That's why the format lives here rather than inside a single prompt.

```markdown
### RSK-003 — Session migration may log out active users
**Likelihood** Medium · **Impact** High · **Owner** cyro · **Status** Open
**Trigger.** Deploying the TASK-014 migration.
**Mitigation.** Dual-read during a transition window; deploy at low traffic.
**Contingency.** `db/migrations/rollback_sessions.sql`
**Related** TASK-014 · DEC-006
```

A risk with no trigger is a worry, not a risk. If you can't say what would set it off, you can't tell whether it's still live.

---

## Open items — `docs/WORKFLOW/00 - START HERE/TECHNICAL/open-items.md`

Two identifiers share one file. `ASM-###` is something you decided yourself and are prepared to be wrong about; `QST-###` is something only the owner can decide. Keeping them together means there's one place to check before a meeting.

```markdown
### ASM-002 — Assuming MySQL 8, not 5.7
Based on `docker-compose.yml` specifying `mysql:8.0`. If production runs 5.7, the
JSON column in the target schema won't work. **Affects** TASK-012.

### QST-003 — REQ-004 conflicts with REQ-011
REQ-004 requires a 30-minute session TTL. REQ-011 requires drivers to stay logged
in for a full shift. Both cannot hold for the same user.
**Options.** (a) Role-based TTL (b) Refresh tokens for drivers (c) Drop the limit
**Blocks.** Any task touching session handling. **Needs.** Your decision.
```

An assumption with no stated basis is a guess wearing a label. Name what it rests on, so that the day that thing turns out to be false you can find everything it poisoned.

---

## Traceability matrix — `docs/WORKFLOW/02 - REQUIREMENTS/TECHNICAL/traceability.md`

Started in Stage 02 and filled in by every stage after it. This is the full shape, once every column has an owner.

```markdown
| REQ | Requirement | Analysis | Gap | Tasks | Tests | Status |
|---|---|---|---|---|---|---|
| REQ-004 | Email/password auth | ANL-009 | GAP-005 | TASK-014, TASK-017 | TEST-011 | In Progress |

**Coverage gaps:** requirements with no task — REQ-019 (proposed PRO-001) ·
requirements with no test — REQ-018 · tasks with no requirement — TASK-016
```

The coverage-gaps line is the point of the table. A matrix with no gaps listed usually means nobody checked, not that there aren't any.

---

## Glossary — optional paired Stage 09 file

```markdown
| Technical term | In plain words | Why it matters |
|---|---|---|
| Session TTL | How long a login lasts before signing in again | Balances convenience against account safety |
| Migration | A scripted, repeatable database change | Lets the database be rebuilt identically anywhere |
| RBAC | Deciding what each kind of user is allowed to do | Keeps students, faculty, and admins out of each other's data |
```

Any technical term appearing in a non-technical document needs an entry here. The third column is what makes the glossary worth writing — the definition tells a panelist what the word means, the "why it matters" tells them why they should care that you got it right.

---

## Output

None by default. If this project needs a glossary, reference, template, or example file, create only that needed filename under both audience folders, for example:

```text
docs/WORKFLOW/09 - TEMPLATES AND EXAMPLES/
├── PLAIN/glossary.md
└── TECHNICAL/glossary.md
```

The relative filename is identical in both folders. Add, delete, or rename both copies together. Both carry identical facts, statuses, dates, decisions, risks, and outcomes. Technical may include code, schema/database, APIs, paths, frameworks, configuration, and engineering detail; Plain contains none of those and never depends on or links to Technical.

---

## Done when

- [ ] No Stage 09 project file was created unless the project actually needs it
- [ ] Risks are kept in Stage 06, open items in Stage 00, and traceability in Stage 02
- [ ] Every materialized Stage 09 relative filename exists under both audience folders
- [ ] Both audience copies agree on facts, statuses, dates, decisions, risks, and outcomes
- [ ] Plain files contain no code, programming-language detail, implementation syntax, or dependency on Technical
