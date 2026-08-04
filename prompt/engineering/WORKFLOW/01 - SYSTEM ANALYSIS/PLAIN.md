# Look at What Exists

> Standalone prompt: paste this whole file into your assistant.

## Your job

Carefully inspect, verify, and record what is there. Do not recommend, redesign, or build anything at this stage.

## Rules that never bend

1. **Look before recommending.** Never assume the project is new; inspect it first.
2. **No claim without evidence.** Every finding needs a file path and line range, command output, an excerpt showing the layout of stored information (schema), or a document and section. If you cannot point to evidence, it is an assumption: log it as `ASM-###`, not a finding.
3. **Do not change anything.** This is read-only work: no edits, fixes, code reorganization (refactors), or rewriting supplied documents.
4. **A document is not the system.** Keep what a document claims separate from what you observe the system doing. This is especially important in Mode D, where every finding is a claim.

### Generated-document contract

- Keep this source prompt where it is. Put generated project documents under `docs/WORKFLOW/<numbered stage>/<audience>/`, where the audience folders are `PLAIN` and `TECHNICAL`.
- Create every generated filename in both audience folders. Add, delete, or rename the pair together, and keep facts, statuses, dates, decisions, risks, and outcomes aligned.
- The plain version must stand alone and use everyday language. It must contain no code, programming-language names, implementation syntax, or link or dependency on the technical version.
- The technical version carries the corresponding engineering detail, including code, stored-information design, system connections, file locations, chosen tools, and setup settings.
- Create only the listed files the project needs; any file created must have its matching audience copy.

## What to do

### 1. Choose the mode

| Mode | When | What to do |
|---|---|---|
| **A - Existing** | Working code or a live database exists | Complete Step 2, the code coverage list. |
| **B - New** | Nothing exists: no code, no documents | State that nothing was found, list what you inspected to confirm it, and stop. |
| **C - Partial** | Code is half-built or abandoned | Complete Step 2, and mark every part of the system usable, salvageable, or replaceable with a reason. |
| **D - Documents only** | No code exists, but a specification, manuscript, proposal, previous documentation, or client brief exists | Complete Step 2D instead of Step 2, using the document coverage list. |

Mode D is common. A fresh repository with a 40-page manuscript is not Mode B or Mode A: there is plenty to examine, but it is not code.

When both code and documents exist, complete Steps 2 and 2D and keep their findings separate.

### 2. Cover every area for Modes A and C

Do not miss an area. If it does not apply, write `not applicable` and explain why.

- **Structure:** project layout, folders and files, naming conventions.
- **Tools used:** programming languages; frameworks, meaning the main software foundation; libraries, meaning reusable outside code; the software environment that runs it; versions; and the files that list those tools.
- **How the system is arranged:** its main parts, their boundaries, and how they communicate.
- **Features:** what the system actually does today, feature by feature.
- **Business logic:** rules contained in the code and where they live.
- **Stored information:** the kind of database, whether it uses linked tables or another arrangement, its layout (schema), relationships, changes that update it (migrations), and starting sample data.
- **Connections:** the system addresses other programs use (API endpoints), the agreed request-and-response rules (contracts), and outside services.
- **Sign-in and access rights:** how people prove who they are (authentication), what each role is allowed to do (authorization), and the roles and permissions involved.
- **Safety:** handling of private values such as keys and passwords (secrets), checks on submitted information, and any known unsafe outside code it relies on.
- **Setup and runtime settings:** setup files, stored setting values, and what is needed to run the system.
- **Outside code:** direct dependencies and the dependencies they bring with them (transitive dependencies), versions, and anything abandoned or vulnerable.
- **Testing:** what is covered, what is not, and whether tests currently pass.
- **Documentation:** what exists, whether it is accurate, and whether it is current.
- **Known issues:** known bugs, workarounds, and known wrong behavior.
- **Past shortcuts needing attention:** duplicated or unused code, copies that no longer match, and shortcuts.
- **Missing or half-finished:** anything started and abandoned.

### 2D. Cover every area for Mode D

Read every supplied document completely before recording anything. The documents are the source, not the code.

- **Intent:** what the system is for, who it serves, and the problem it solves.
- **Actors and roles:** every named user type and what each is said to be able to do.
- **Features:** every described capability, one by one.
- **Business rules:** policies, formulas, eligibility, thresholds, fee structures, and scoring logic.
- **Entities and relationships:** the things the system stores and how they relate; this is the draft data model.
- **Processes:** every described workflow from start to finish, including passing mentions.
- **External systems:** anything the documents say it must connect to.
- **Constraints:** named platform, budget, deadline, institutional requirements, and standards.
- **Quality expectations:** stated speed, scale, uptime, accessibility, or safety expectations.
- **Where each claim came from:** the document, its date, and how official it is: approved specification, draft, meeting note, or someone's opinion.

Also record the four areas that make document review valuable:

- **Contradictions:** two documents, or sections, that say different things. Name both sides.
- **Missing things (omissions):** obvious but unmentioned needs. Always check error handling, permissions, failure behavior, and how long information is kept (data retention).
- **Statements with more than one meaning (ambiguity):** statements that could reasonably mean two things.
- **Unstated assumptions:** what the documents take for granted without saying.

> **Document findings are claims, not observations.** A spec describing a feature is not proof that it exists, works, or is still wanted. Label every Mode D finding as a claim and name its source document. If you run Mode A and Mode D for the same project, do not merge “the code does X” with “a document says it should do X”; that is how teams build something the client stopped wanting two revisions ago.

### 3. Record each finding as `ANL-###`

Use one heading per finding so it can be linked:

```markdown
### ANL-009 - Sign-in is handled in three separate places
**Area.** System arrangement / Sign-in
**Observation.** The system remembers a signed-in person in three separate places, and those places use different time limits.
**Evidence.** Three inspected sign-in areas contain separate versions of this responsibility.
**Impact.** One policy change needs three edits; the copies have already diverged.
**Classification.** Past shortcut needing attention, medium seriousness.
```

In Mode D, the evidence is a document and section, and the finding says what the document claims:

```markdown
### ANL-014 - Two documents disagree on who can approve a request
**Area.** Roles and permissions · **Type.** Claim, from documents
**Claim.** Section 4.2 of the manuscript says any dispatcher may approve. The panel
revision notes, page 11, say approval requires a supervisor.
**Source.** `manuscript.md` §4.2 (approved, March) · `panel-revisions.md` p.11 (June)
**Impact.** The set of access rules cannot be designed until this is settled. Later
document, but the earlier one is the approved version - unclear which governs.
**Classification.** Contradiction - blocks design. Raised as QST-004.
```

### 4. State what already works - Modes A and C

Clearly name every existing part that already meets a need. This stops later stages from proposing an unnecessary rebuild.

Skip this in Mode D. Nothing exists yet, so nothing works yet. A document's description of a feature must not become a claim that it already works.

### 5. Trace the processes

For Modes A and C, follow each process through the code. For Mode D, follow each process as the documents describe it and mark each drawing as **intended**, not actual. Create one flowchart per process, never one combined diagram for a whole work area (module).

### 6. Turn findings into gaps

```markdown
### GAP-005 - No single agreed sign-in process
**Now** Three separate areas manage sign-in differently -> **Target** One agreed sign-in process managed in one place
**Severity** High · **Effort** Medium
**From** ANL-009 · **Resolved by** - (a task will be linked here later)
```

In Mode D, skip a full gap list. Nothing is built, so every feature is a gap and that list adds no value. Put one line in `gaps.md` saying the whole system is still to be built (to-be). Carry contradictions, missing things (omissions), and unclear statements (ambiguities) into Stage 02 as `QST-###` entries instead; they are the most valuable Mode D result.

### 7. Write the summaries and scope

Write two or three paragraphs in each audience's `summary.md`: what the system is, who uses it, what it does, and what is wrong with it. In Mode D, explain what it will be and what remains unresolved. Also write `scope.md` with what was inspected, what was not inspected, the chosen mode, and the evidence boundary. Keep `open-items.md` for assumptions and questions. The plain copies use everyday language and no file locations; the technical copies hold the matching engineering detail.

## Required output

Create the same relative filenames in both audience folders:

```text
docs/WORKFLOW/
|- 00 - START HERE/
|  |- PLAIN/{summary.md, scope.md, open-items.md}
|  `- TECHNICAL/{summary.md, scope.md, open-items.md}
`- 01 - SYSTEM ANALYSIS/
   |- PLAIN/{existing-system.md, architecture.md, database.md, security.md, technical-debt.md, gaps.md, process-flows.md}
   `- TECHNICAL/{existing-system.md, architecture.md, database.md, security.md, technical-debt.md, gaps.md, process-flows.md}
```

For Mode D, keep the filenames above: put the document inventory and stated intent in `existing-system.md`, claimed parts and business rules in `architecture.md`, the draft information model in `database.md`, safety claims and omissions in `security.md`, contradictions, omissions, ambiguity, and unstated assumptions in `technical-debt.md`, the one-line to-be statement in `gaps.md`, and intended flows in `process-flows.md`.

Every listed document starts with an **In plain terms** block of two to four sentences before any table or heading. Generated links must point only to files in the same audience, for example from the plain `gaps.md` to `[ANL-009](architecture.md#anl-009)`.

## Done when

**Every mode**

- [ ] The mode and the reason for it are recorded.
- [ ] Every finding has evidence or was demoted to `ASM-###`.
- [ ] There is one flowchart for every distinct process.
- [ ] The paired summaries, scopes, and open-items records are written.
- [ ] Nothing was edited, fixed, or recommended.
- [ ] Every produced document starts with an **In plain terms** block.
- [ ] Every generated filename exists in both audience folders, and the paired facts, statuses, dates, decisions, risks, and outcomes agree.
- [ ] Plain documents stand alone without code, programming-language names, implementation syntax, or links or dependencies to technical documents.

**Modes A and C**

- [ ] All 16 coverage areas are addressed or marked not applicable with a reason.
- [ ] Existing parts that already meet a need are explicitly named.
- [ ] Each gap comes from a finding and links back to its `ANL-###`.

**Mode D**

- [ ] Every supplied document was read fully and listed with its date and authority.
- [ ] All 14 document coverage areas are addressed.
- [ ] Every finding is labelled as a claim and names the document and section it came from.
- [ ] Contradictions name both sides and are not resolved.
- [ ] Missing things (omissions) include checks for error handling, permissions, failure behavior, and how long information is kept (data retention).
- [ ] Nothing described in a document is recorded as already working.
- [ ] Flowcharts are marked intended, not actual.

## Input

**Project:** `<path to repository, or "fresh repository - documents only">`

**Documents:** `<paths to any specs, manuscripts, proposals, briefs, or prior documentation - or "none">`

**Focus:** `<specific area to prioritize, or "everything">`

**Docs go in:** `<project root - generated files use docs/WORKFLOW/>`
