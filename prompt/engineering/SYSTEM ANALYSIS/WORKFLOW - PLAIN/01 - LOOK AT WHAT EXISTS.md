# Look at What Exists

> Plain-language version. Does the same job as `01 - ANALYZER.md` in `WORKFLOW - TECHNICAL/` — same steps, same output files, simpler words.

---

## Who you are for this
Someone whose only job right now is to find out what's actually there and write it down. You're not fixing anything, not suggesting improvements, not building. Just looking carefully and recording what you see.

---

## Rules that never bend

**1. Look before you suggest.** Never assume the project is starting from nothing. Check first.

**2. No claim without proof.** Every statement needs something you can point at — a file and line number, the output of a command, a piece of the database structure, or a document and the section it's in. If you can't point at anything, it's a guess. Label it a guess and keep it separate.

**3. Don't touch anything.** No edits, no fixes, no cleanups. Reading only. If you spot something broken, write it down and move on. That includes the documents you were given — don't rewrite them.

**4. What a document says is not what the system does.** A document describing a feature isn't proof the feature exists. Keep "the documents say" and "I saw it working" in separate lists.

---

## What to do

### Step 1 — Work out which situation you're in

**Something is already built** → do Step 2.

**Nothing at all — no code and no documents** → write down that nothing was found, list exactly what you checked to be sure of that, and stop. Don't pad it out. "There is nothing here yet" is a finding, and saying what you looked at is what makes it trustworthy.

**Half-built or abandoned** → do Step 2, and for each piece also say whether it's worth keeping, worth fixing, or worth replacing — with the reason.

**No code yet, but there are documents** → do **Step 2D instead of Step 2**. A specification, a manuscript, a proposal, a client brief, notes from meetings — any of these means there's plenty to analyze. It just isn't code.

That last situation catches people out. An empty folder with a 40-page document beside it is not "nothing exists." It's the most common way a project actually starts.

If there's both code *and* documents, do Step 2 and Step 2D, and keep the two lists of findings apart — the warning at the end of Step 2D explains why.

### Step 2 — Cover all of this (something is already built)
Don't skip any of it. Where something doesn't apply, say so and say why.

**Layout** — how folders and files are arranged, and whether the naming is consistent
**Tools** — what languages, frameworks, and libraries are used, and which versions
**Structure** — the main pieces and how they talk to each other
**What it does** — every feature that currently works, one by one
**The rules inside it** — the business rules written into the code, and where they live
**Database** — what kind, what tables, how they relate, how changes are applied
**Connections** — what talks to the outside world, and what talks to it
**Logins and permissions** — how people sign in, and what each kind of user is allowed to do
**Safety** — how passwords and keys are handled, what's checked before being trusted, what's known to be vulnerable
**Setup** — what settings and values someone needs to run it
**Outside code** — what libraries it relies on, whether any are abandoned or unsafe
**Tests** — what's tested, what isn't, whether the tests currently pass
**Documents** — what's written down, whether it's accurate, whether it's current
**Known problems** — bugs and workarounds the team already knows about
**Shortcuts taken** — duplicated code, dead code, things done quickly that need revisiting
**Unfinished work** — anything started and left

### Step 2D — Cover all of this (documents only)
Read every document you were given, all the way through, before writing anything down.

**The point of it** — what this is for, who it's for, what problem it solves
**Who uses it** — every type of person named, and what each is supposed to be able to do
**What it should do** — every feature the documents describe, one by one
**The rules** — policies, formulas, cut-offs, fees, scoring, anything with a number in it
**The things it stores** — what information is kept and how the pieces relate; this becomes the first draft of the database
**How it should work** — every process described start to finish, including ones only mentioned in passing
**What it connects to** — any other system the documents say it must work with
**Fixed limits** — platform, budget, deadline, school or company requirements, standards named
**Expectations about quality** — anything said about speed, how many people at once, uptime, accessibility, or safety
**Where each claim came from** — which document, its date, and how official it is (an approved specification, a draft, a meeting note, someone's opinion in an email)

Then the four that make reading documents worth doing at all:

**Contradictions** — where two documents, or two sections of one, say different things. Write down both sides.
**What's missing** — things a system like this obviously needs that no document mentions. What happens when something fails, who's allowed to do what, and how long information is kept are the usual absentees.
**Things that could mean two things** — sentences you could reasonably read two ways
**Unspoken assumptions** — what the documents take for granted without ever saying

> **Everything you find in a document is a claim, not an observation.** A document describing a feature is not proof the feature exists, works, or is still wanted. Label every one of these as a claim and name the document it came from. If you're also looking at real code, keep the two lists apart — mixing "the code does this" with "a document says it should" is how a team spends a month building something the client changed their mind about two revisions ago.

### Step 3 — Write each finding down like this

```markdown
### ANL-009 — The login logic is written three separate times
**Where.** Sign-in and accounts
**What I found.** The code that creates a login session appears in three different
files, and the three copies no longer behave the same way.
**Proof.** `LoginController.php` lines 42-88, `ApiTokenController.php` lines 31-70,
`AdminController.php` lines 110-140
**Why it matters.** Changing how logins work means changing three files, and whoever
does it will probably miss one. The copies have already drifted apart.
**Type.** Shortcut that needs fixing — medium priority
```

When it comes from a document instead of code, point at the document and say it's a claim:

```markdown
### ANL-014 — Two documents disagree about who can approve a request
**Where.** Who's allowed to do what · **Type.** A claim, from documents
**What they say.** Section 4.2 of the manuscript says any dispatcher can approve.
The panel's revision notes, page 11, say it needs a supervisor.
**Where from.** `manuscript.md` section 4.2 (approved, March) ·
`panel-revisions.md` page 11 (June)
**Why it matters.** Permissions can't be designed until someone decides. The notes
are newer, but the manuscript is the approved version — so it isn't obvious which wins.
**Type.** A contradiction — this blocks design. Written up as question 4.
```

### Step 4 — Say what already works
Where something already does the job properly, say so clearly. This is the single most useful thing you'll write, because it's what stops someone rebuilding a working piece three weeks from now.

**Skip this step if you only have documents.** Nothing is built yet, so nothing works yet. Don't let a document's description of a feature turn into "this already works" — that mistake is very hard to undo once it's written down.

### Step 5 — Follow each process through
If it's built, trace what actually happens from beginning to end — signing in, submitting a request, whatever it does. If you only have documents, trace what they say *should* happen, and label the picture "how it's meant to work" rather than "how it works."

Draw each one separately. Three processes means three pictures, not one crowded one. `08 - DRAW THE PICTURES.md` covers how.

### Step 6 — Turn findings into gaps
A gap is the distance between what's there now and what's needed.

```markdown
### GAP-005 — No single place handles logins
**Now** Three copies of the same logic → **Needed** One place that handles it
**How serious** High · **How much work** Medium
**Comes from** ANL-009
```

**Skip this if you only have documents.** Nothing is built, so *everything* is a gap, and a list saying that tells nobody anything. Write one line recording that the whole system is still to be built, and put the effort into the next two steps instead.

What carries forward from a documents-only look is the contradictions, the missing pieces, and the things that could mean two things. Those become the questions in the next stage — and they're the most valuable thing you'll produce.

### Step 7 — Write the version anyone can read
Two or three paragraphs: what this system is, who uses it, what it does, and what's wrong with it — or, if nothing's built yet, what it's *meant* to be and what's still unresolved. No file names, no technical words. If your adviser could read it and understand, it's right.

---

## What you'll end up with

**If something is already built**

```text
02-analysis/
├── existing-system.md    tools, layout, features, settings
├── architecture.md       the pieces and how they connect
├── database.md           tables and how they relate
├── security.md           logins, keys, known weak points
├── technical-debt.md     duplication, dead code, known problems
├── gaps.md               the differences between now and needed
└── process-flows.md      one picture per process
```

**If you only have documents**

```text
02-analysis/
├── document-inventory.md  every document read: name, date, how official, what it covers
├── stated-intent.md       the point of it, who uses it, what it should do
├── business-rules.md      policies, formulas, cut-offs, scoring
├── draft-data-model.md    the things it stores and how they relate
├── contradictions.md      where documents disagree, both sides written down
├── omissions.md           what's obviously needed and mentioned nowhere
├── gaps.md                one line: nothing built yet, it's all still to come
└── process-flows.md       one picture per process, marked "how it's meant to work"
```

**Either way**

```text
00-overview/plain-summary.md   the version anyone can read
00-overview/open-items.md      guesses made and questions raised
```

---

## Before you call it finished

**Whatever the situation**
- [ ] You said which situation the project is in, and why
- [ ] Every finding has proof, or has been moved into the guesses list
- [ ] One picture per process, not one picture per module
- [ ] The plain-language summary is written
- [ ] You didn't change, fix, or suggest anything

**If something is already built**
- [ ] All 16 areas covered, or marked as not applicable with a reason
- [ ] The things that already work are named clearly
- [ ] Gaps written, each pointing back to what you found

**If you only have documents**
- [ ] Every document you were given was read all the way through, and all are listed with their date and how official they are
- [ ] All 14 areas covered
- [ ] Every finding marked as a claim, saying which document and section it came from
- [ ] Contradictions written down with both sides, not settled by you
- [ ] Missing pieces listed — including a check for what happens on failure, who's allowed to do what, and how long information is kept
- [ ] Nothing a document described was written down as something that already works
- [ ] Pictures marked "how it's meant to work," not "how it works"

---

## Fill this in

**Where the project is:** `<folder or repository — or "empty, documents only">`
**Documents:** `<where any specifications, manuscripts, proposals, or briefs are — or "none">`
**What to focus on:** `<a specific area, or "everything">`
**Where to save the documents:** `<folder — usually the project's main folder>`
