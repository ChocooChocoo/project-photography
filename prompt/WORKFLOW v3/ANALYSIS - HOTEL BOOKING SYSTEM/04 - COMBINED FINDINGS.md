# 04 - COMBINED FINDINGS

[[00 - START HERE|Back to start]] · Previous: [[03 - CODE FINDINGS]] · Next: [[05 - SYSTEM ARCHITECTURE]]

## The picture in one paragraph

The rules about *rooms and dates* are built, and built properly. The rules about *people* are almost entirely missing. Every promise that turns on arithmetic — no overlaps, one to fourteen nights, the right rate for the right type — is kept. Every promise that turns on who somebody is — only a manager changes a rate, only a receptionist cancels without charge, a guest has an account — is either absent or, in one case, wired the wrong way round so that the cleaning staff hold powers nobody gave them. On top of that, nothing survives the program stopping, and the one number the two documents disagree about has already been decided in the working file without anybody being told.

## Promised and built

| # | What was promised | Where it was promised | Where it was built | Does it match? |
|---|---|---|---|---|
| M-01 | No two stays overlap on the same room | `Requirements.md`, *Rules* item 1 | `booking_service.py`, lines 27–35 and 46 | Fully — and correctly across a run of nights, not just a single date |
| M-02 | A stay is between one and fourteen nights | `Requirements.md`, *Rules* item 2 | `booking_service.py`, lines 7–8 and 40–43 | Fully |
| M-03 | Rates are set per room type, not per room | `Requirements.md`, *Decisions* | `rooms.py`, lines 16–20 and 26–28 | Fully |
| M-04 | Twenty-four rooms — ten Standard, ten Double, four Suite | `Requirements.md`, *Decisions* | `rooms.py`, lines 8–13 | Fully |

Four promises kept, and all four are about rooms and dates. Not one of them is about a person.

## Promised but missing

| # | What was promised | Where it was promised | What is there instead |
|---|---|---|---|
| G-01 | Email the guest for every confirmed booking | `Requirements.md`, *Rules* item 7 | An empty part with the right name — `booking_service.py`, lines 89–90 — that nothing calls |
| G-02 | A room must be clean before a guest is checked into it | `Requirements.md`, *Rules* item 6 | Nothing. A note at line 76 admits it. A guest can be put into a room marked dirty. |
| G-03 | Check-in from 2pm, check-out by 11am | `Requirements.md`, *Rules* item 4 | Nothing. A booking carries dates and no time of day, so there is nothing to measure against. |
| G-04 | Only a manager may change a nightly rate | `Requirements.md`, *Rules* item 5 | Nothing. `rooms.py`, lines 31–33 changes the rate for anybody who asks, with a note admitting it. |
| G-05 | One night is charged for cancelling inside the cut-off | `Requirements.md`, *Rules* item 3 | The charge is worked out at line 69 and then discarded. Nobody is ever charged, and the cancellation goes through anyway. |
| G-06 | A receptionist may cancel any booking without charge | `Requirements.md`, *Rules* item 8 | No rule about receptionists exists. What is there is wider and wrong — see K-02. |
| G-07 | Guest accounts, so a returning guest is recognised | `Requirements.md`, *Decisions* | A list of four staff names written into the file — `booking_service.py`, lines 15–20. No guest is on it, and nothing creates one. |
| G-08 | Nobody may book a room that is out of service | `Handover Notes.md`, *What we agreed* | Half. A room can be marked out of service at `rooms.py`, lines 44–46, and the overlap check never looks at room state, so it is sold anyway. |
| G-09 | The cleaning list shows no guest names | `Handover Notes.md`, *What we agreed* | Nothing. Nothing anywhere separates what one level of person may see from another. |
| G-10 | Show the manager how full the hotel has been, by week | `Handover Notes.md`, *What we agreed* | Nothing. Nothing counts anything. |

Ten promises unkept, and eight of the ten are about people rather than rooms. These are the strongest candidates for [[07 - DEVELOPMENT ROADMAP]].

## Built but never written down

| # | What exists | Where | Why it matters |
|---|---|---|---|
| X-01 | Nothing survives the program stopping | `booking_service.py`, line 11, and `rooms.py`, lines 7–20 | Every booking, every room state, and every changed rate is lost. Neither document mentions keeping anything anywhere, so nobody has ever decided this — it simply happened. |
| X-02 | Four staff names are written directly into the working file | `booking_service.py`, lines 15–20 | This is the whole of the system's idea of who anybody is. Adding a receptionist means editing the program. Anybody not on the list is silently treated as a guest. |
| X-03 | Rooms carry a state, and one of the four is out of service | `rooms.py`, line 23 | The specification never mentions room states at all. Only the handover notes hint at one of the four. |
| X-04 | A cancelled booking frees its room again | `booking_service.py`, lines 31–32 | Sensible, and nobody asked for it. Worth recording as a decision somebody made rather than a rule anybody set. |
| X-05 | Every booking keeps its own copy of the rate | `booking_service.py`, line 56 | It means a later rate change does not alter bookings already made, which is almost certainly right and is written down nowhere. |

## Where they flatly contradict each other

| # | The documents say | The working files do | Where |
|---|---|---|---|
| K-01 | Cancel free up to 48 hours before arrival — `Requirements.md`, *Rules* item 3 | The cut-off is set to 24 hours, with a note recording the disagreement and choosing the meeting's number | `Requirements.md`, *Rules* item 3, and `booking_service.py`, line 9 |
| K-02 | A receptionist may cancel any booking without charge — and nobody else is given that power | Everyone who is not a guest may cancel anything: receptionists, the manager, **and the cleaning staff** | `Requirements.md`, *Rules* item 8, and `booking_service.py`, lines 65 and 71 |

K-01 is really the document disagreement in [[02 - DOCUMENT FINDINGS#Where the documents disagree|C-01]] with a third voice added. Two documents disagree, and the working file has quietly settled it. Nobody outside the file knows.

K-02 is the more serious of the two. It is not a number in the wrong place — it is a power handed to a group of people by accident, because the test at line 65 asks *is this person a guest* rather than *is this person allowed*. Whichever way [[00 - START HERE#Open questions|Q-09]] is answered, the shape of that test has to change.

## What this means for what happens next

Three things lead the plan, and they lead it in this order.

**Nothing lasting can be built on something that forgets.** Every booking, room state, and rate vanishes when the program stops — X-01. Building confirmation emails, charges, or occupancy figures on top of that would mean building them twice. This is why the first phase of [[07 - DEVELOPMENT ROADMAP]] is about nothing else.

**The rules that exist should say what they were meant to say.** The cut-off has a number nobody agreed to in writing, the confirmation email is promised and silent, and the check-in and check-out hours are unenforced. These are small, well-understood pieces of work, and they close five of the ten gaps above.

**Then the hard part: people.** Eight of the ten missing promises are about who somebody is and what that entitles them to. They cannot be done piecemeal, because they all lean on the same absent thing — a reliable answer to *who is this*. Today that answer is four names in a file. The roadmap treats levels and accounts as one strand and gives it two phases.

One gap sits outside all three: the late-cancellation charge cannot be designed at all until somebody settles how money is taken — [[00 - START HERE#Open questions|Q-03]] — because the specification promises a charge and rules out holding the means to take it.
