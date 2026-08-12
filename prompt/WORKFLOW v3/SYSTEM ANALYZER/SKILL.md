---
name: system-analyzer
description: Use when the user hands over documents, source code, or both and wants them understood, or asks for a development roadmap, a status tracker, a task tracker with origins, Mermaid diagrams or flowcharts, system architecture documentation, a full write-up of what each part of the system does, or a note on what each level of user sees. Triggers on "analyze these documents", "analyze this code", "analyze this project", "review this material", "make me a roadmap", "track the roadmap", "track these tasks", "diagram this system", "document the architecture", "explain this in plain English", "write up the parts in detail", "spec out each module", "what does each role see", "map the screens by role". Produces a numbered, interlinked set of plain-language notes inside the Obsidian vault.
---

# System Analyzer

Reads what the user hands over and writes a numbered, linked set of plain-language notes into this Obsidian vault. Everything the analyzer produces is words, tables, and Mermaid diagrams. No program is written, no script is run, nothing is installed.

## The one rule above all others

**Write for someone who has never opened a code editor.** Every note must be readable start to finish by a person with no technical background, while still being precise enough that a developer would agree it is correct. If a sentence would make a non-technical reader stop and squint, rewrite it.

Read `references/writing-rules.md` before writing a single output file. It is the contract every module obeys.

## Step 1 — Take in the material

Ask for two things, unless the user already gave them:

1. **The material** — file paths, a folder, pasted text, or a mix.
2. **A short subject name** — what to call this analysis. A few words: "Payroll App", "Client Portal", "Booking System".

Then work out which of the three inputs you were given:

| What you were given | Which path |
|---|---|
| Documents only | Documents path |
| Source code only | Code path |
| Both | Combined path — do both, then compare them |

Documents means anything carrying written content: Word, PDF, Markdown, plain text, spreadsheets used as written specs, exported notes, and anything else of that kind. Source code means the working files of a program.

If the material is large, read the entry points and the highest-traffic files first, then widen. Say in `00 - START HERE` what you read and what you did not.

## Step 2 — Open the files

Never write a parser. Use what is already installed:

| Format | How to read it |
|---|---|
| PDF | The `anthropic-skills:pdf` skill |
| Word — .docx, .dotx | The `anthropic-skills:docx` skill |
| Spreadsheet — .xlsx, .csv | The `anthropic-skills:xlsx` skill |
| Slides — .pptx | The `anthropic-skills:pptx` skill |
| Markdown, text, source code | Read directly |

If a file cannot be opened, do not guess at its contents. Record it in the *Open Questions* section of `00 - START HERE` and carry on.

## Step 3 — Make the run folder

One folder per analysis, at the top of the vault:

```
ANALYSIS - <SUBJECT NAME>\
    MATERIAL\
```

If that folder already exists, ask whether to update it in place or start a fresh one with a different subject name. Never silently overwrite an earlier analysis.

### The material has to stay findable

Every note in a run cites the material by file name and line number. A citation nobody can follow is decoration, and a line number rots the moment the file behind it changes. So every run records where its material came from, one of two ways.

**Copy it into `MATERIAL\`** when the material is small, when it is a document likely to be superseded, or when the run has to stand on its own — a worked example, an analysis handed to somebody else, anything that must still make sense in a year. The citations then stay true permanently, because the thing being cited sits beside the notes.

**Record the path instead** when the material is a working codebase the user already keeps elsewhere, or is too large to copy sensibly. `MATERIAL\` is not made; the *What was handed over* table in `00 - START HERE` carries the full path to each file and the date it was read.

Copy by default for anything under a few hundred kilobytes. Ask before copying anything larger — putting somebody's repository inside their notes vault is not a decision to make on their behalf.

Either way, `00 - START HERE` says which of the two was done, so a reader knows whether the line numbers beside them are frozen or live.

## Step 4 — Write the files

Eleven files, fixed numbers, and two more written only on request. A number is never reused for something else and never shifts, so links stay good between runs. A file that does not apply is simply not created, and `00 - START HERE` says so in its place.

| File | What it holds | Module | Written when |
|---|---|---|---|
| `00 - START HERE.md` | Front door: what was handed over, the index, the legend, open questions | Entry point | Always |
| `01 - OVERVIEW.md` | What this thing is and what it does, in everyday words | Overview | Always |
| `02 - DOCUMENT FINDINGS.md` | What the documents say | Document analyzer | Documents supplied |
| `03 - CODE FINDINGS.md` | What the code does, described in words | Code analyzer | Code supplied |
| `04 - COMBINED FINDINGS.md` | Where documents and code agree, disagree, or leave gaps | Combined analyzer | Both supplied |
| `05 - SYSTEM ARCHITECTURE.md` | The parts, what each is for, how they hand work along | Architecture analyzer and creator | Always |
| `06 - DIAGRAMS.md` | The pictures, all in Mermaid | Diagram module | Always |
| `07 - DEVELOPMENT ROADMAP.md` | Phases in time order, items `R-01`… | Roadmap creator | Always |
| `08 - ROADMAP TRACKER.md` | Every roadmap item with its status | Roadmap tracker | Always |
| `09 - TASK TRACKER.md` | Every task with where it came from | Task tracker | Always |
| `10 - WORD LIST.md` | Any unavoidable technical word, explained | Word list | Always |
| `11 - PARTS IN DETAIL.md` plus `PARTS\` | One page per part: what it does, who may use it, what it checks, how it behaves | Part detail writer | Only when asked for |
| `12 - SCREENS BY ROLE.md` | What each level of user sees, and which parts each screen reaches | Screen writer | Only when asked for |

Write them in number order. Later files lean on earlier ones — the roadmap draws on the findings, the trackers draw on the roadmap, the diagrams draw on the architecture, and the part pages draw on all of them.

Templates live in:

- `references/analysis-templates.md` — for `00`, `01`, `02`, `03`, `04`, `05`, `10`
- `references/planning-templates.md` — for `07`, `08`, `09`, plus the status legend and the ID scheme
- `references/diagram-templates.md` — for `06`, plus the Mermaid rules that keep diagrams from breaking
- `references/part-templates.md` — for `11` and the pages inside `PARTS\`
- `references/screen-templates.md` — for `12`

## Step 5 — Add the run to the vault front door

Add one line for this run to `00 - START HERE.md` at the top of the vault, linking to the run's own `00 - START HERE`. Create that vault-level file if it is missing.

## Step 6 — Check your own work

Before telling the user it is done, confirm each of these:

- [ ] Every file that should exist, exists — and is named exactly right, prefix and spacing included.
- [ ] Every `[[link]]` points at a file that is really there. A typo in a link is a dead end in Obsidian.
- [ ] Every file has its navigation line at the top.
- [ ] Every Mermaid block follows the rules in `references/diagram-templates.md`.
- [ ] Every roadmap item has an `R-` number, and every one of them appears in `08 - ROADMAP TRACKER`.
- [ ] Every task in `09 - TASK TRACKER` names where it came from. Not one blank.
- [ ] Every technical word left in the output appears in `10 - WORD LIST`.
- [ ] Nothing was invented. Every claim traces back to the material or sits in *Open Questions*.
- [ ] Every citation can be followed — the material is either sitting in `MATERIAL\` or its full path is in the *What was handed over* table.

And when part pages were asked for:

- [ ] Every page in `PARTS\` is listed in `11 - PARTS IN DETAIL`, and every row in `11` has a page.
- [ ] Every `P-` number is used once, and every page names at least one real `R-` item that builds it.
- [ ] Every link out of the `PARTS\` folder carries the run folder in front of it.
- [ ] No permission, rule, or edge case on a part page was invented to sound sensible.

And when `12 - SCREENS BY ROLE` was asked for:

- [ ] It opens by saying plainly whether the material describes screens or whether they were derived.
- [ ] Every screen names the need it came from, and anything inferred says *drawn from*.
- [ ] Every part named behind a screen appears in `11 - PARTS IN DETAIL`.
- [ ] The overlap table has no empty cells, and a *What is missing* table exists.

## Step 7 — Offer what was not made

`11 - PARTS IN DETAIL` and `12 - SCREENS BY ROLE` are written only when asked for, and a user who has never seen them does not know to ask. So when a run is finished, say in a line or two what each would add, and offer them.

Offer `11` always. Offer `12` only when the system has two or more levels of user with different rights — on a single-user system it has nothing to say.

Do not build either uninvited. Offering is not the same as assuming.

## What good looks like

`ANALYSIS - HOTEL BOOKING SYSTEM` at the top of this vault is a complete worked example — every file this skill can produce, written against four supplied files sitting in its own `MATERIAL\` folder. Read it when unsure how much detail a section wants, or what a finding should sound like.

Three things in it are worth copying deliberately.

**Findings name what is wrong and what it costs.** Not "the permission check is missing", but which line asks the wrong question, who gains a power nobody granted them, and what the documents say instead.

**Gaps become questions, never guesses.** Ten open questions, seven of them blocking roadmap items. A run that produces no questions has almost certainly filled some in.

**Disagreements are recorded, not smoothed over.** Writing the part pages there turned up a roadmap item that cannot finish where the roadmap puts it. It stayed in, with a note, because resolving it was the hotel's decision and not the analyzer's.

## The eleven modules

Each module is a section of this skill, not a separate thing to run. Each writes one numbered file, except two — the part detail writer writes a file and a folder, and linking and naming applies everywhere.

### 1. Document analyzer → `02 - DOCUMENT FINDINGS.md`

Reads the supplied documents. Pulls out what the system is meant to do, the rules it must follow, who uses it, what has been decided, and what has been promised. Every point cites its document and page or heading. Contradictions between documents are recorded, not resolved by guessing.

### 2. Code analyzer → `03 - CODE FINDINGS.md`

Reads the supplied source code and describes, in plain words, what it actually does: the main pieces, what each piece is responsible for, the paths work travels along, where information is kept, and what the program connects to outside itself. Points at files and line numbers rather than quoting code. Notes what is clearly unfinished, switched off, or unused.

### 3. Combined analyzer → `04 - COMBINED FINDINGS.md`

Only when both were supplied. Sets the documents beside the code and reports three things: what was promised and built, what was promised and is missing, and what was built but never written down. This is where the real picture appears, so give it room.

### 4. System architecture analyzer and creator → `05 - SYSTEM ARCHITECTURE.md`

Two jobs in one file. First, describes the arrangement that already exists — the parts, each part's job, and how work passes between them. Second, where the material calls for an arrangement that does not exist yet, describes the one being proposed and says plainly which parts are existing and which are proposed.

### 5. Roadmap creator → `07 - DEVELOPMENT ROADMAP.md`

Turns the findings into phases in chronological order. Development work only — what gets built, in what order, and why that order. Nothing about budget, hiring, marketing, or launch. Every item carries an `R-` number. Content comes strictly from what the user supplied, including any revisions, suggestions, and improvements they mentioned.

### 6. Roadmap tracker → `08 - ROADMAP TRACKER.md`

Every `R-` item with a status emoji, so the state of the whole plan reads at a glance. Statuses come from the legend in `references/planning-templates.md`. Anything already built, found sitting in the supplied code, starts as already there — not as not started.

### 7. Task tracker → `09 - TASK TRACKER.md`

Every task with a `T-` number and, without exception, where it came from: which file, which page or line, which conversation. A task with no traceable origin does not belong in the table.

### 8. Diagram and flowchart module → `06 - DIAGRAMS.md`

The pictures, all in Mermaid, with the processes given the most attention. Every diagram is followed by a short plain-language reading of it, because a picture nobody can read is decoration.

### 9. Part detail writer → `11 - PARTS IN DETAIL.md` and `PARTS\`

Written only when the user asks for it. One page per part of the system, each carrying what the roadmap deliberately leaves out: what the part does, who may use it and what they may not do, the information it handles, how it behaves step by step, the states things move through, what it checks before it agrees, what happens when something goes wrong, and how you would know it is finished.

The dividing line is worth holding on to. `05` says where a part sits. `07` says when it gets built and why in that order. These pages say what it actually does. Nothing is written twice.

Parts are numbered `P-01` upward — never `M-`, which `04 - COMBINED FINDINGS` already uses. Not every part earns a page: work from the parts named in `05`, propose a shortlist, and confirm it before writing. Full templates and rules are in `references/part-templates.md`.

### 10. Screen writer → `12 - SCREENS BY ROLE.md`

Written only when the user asks, and only worth writing when the system has two or more levels of user with different rights. One section per level, listing the screens that level sees, what is on each, where each leads, and which parts it reaches.

Most material describes needs rather than screens, which makes this the easiest file in the vault to fill with invention. It is the only note that opens by declaring what it is: the material describes no screens, everything below is derived from a stated need, and this is a proposal to be argued with. Screens a working system would obviously need but the material never mentions go in a *What is missing* table rather than being filled in.

Screens are numbered `S-`, and the numbers refer to screens rather than setting an order to build them in. Full templates and rules are in `references/screen-templates.md`.

### 11. Linking and naming → applies to every file

Every file carries the navigation line, links out to whatever it mentions, and follows the numbered naming pattern. Covered in `references/writing-rules.md`.

## When the user asks for only one piece

If the user asks only for a roadmap, or only for diagrams, write that file and `00 - START HERE`, and nothing else. `00` lists the rest as not yet made. Do not build the full set uninvited.

Part pages and the screens note are always this kind of request — neither is part of a standard run. Asked for on their own, part pages still need `05 - SYSTEM ARCHITECTURE` and `07 - DEVELOPMENT ROADMAP` to point at, and the screens note needs `11 - PARTS IN DETAIL`. Say plainly if those do not exist yet and offer to write them first.

## When the user brings new material later

Update the existing run folder rather than starting a second one. Add new roadmap items with fresh `R-` numbers rather than renumbering old ones — renumbering breaks every reference pointing at them. The same goes for `P-` numbers on part pages and `S-` numbers on screens. Record what changed in the *What Changed* section of `00 - START HERE`.
