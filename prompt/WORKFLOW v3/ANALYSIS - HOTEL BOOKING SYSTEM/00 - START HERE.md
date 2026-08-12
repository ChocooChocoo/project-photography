# 00 - START HERE

Next: [[01 - OVERVIEW]]

**What this is about:** Hotel Booking System — the Riverside
**Written:** 8 August 2026
**Last updated:** 8 August 2026

## What was handed over

| What | Kind | Where it came from | Read? |
|---|---|---|---|
| `Requirements.md` | Document — a written specification, version 0.4, dated 3 August 2026 | `MATERIAL\Requirements.md` — copied into this run | Yes — in full |
| `Handover Notes.md` | Document — notes from a meeting on 21 July 2026 | `MATERIAL\Handover Notes.md` — copied into this run | Yes — in full |
| `booking_service.py` | Source code — 90 lines | `MATERIAL\booking_service.py` — copied into this run | Yes — in full |
| `rooms.py` | Source code — 50 lines | `MATERIAL\rooms.py` — copied into this run | Yes — in full |

Four files. Two describe what a twenty-four room hotel wants its booking system to do; two hold a first attempt at building it. Small enough that all four were read end to end, so nothing here rests on a partial reading.

**All four were copied into `MATERIAL` beside these notes.** Every line number cited anywhere in this analysis therefore points at a file sitting in this folder, and will still be correct however the originals change.

## The short version

The Riverside wants guests to book online, the front desk to run arrivals and departures, and the cleaning staff to record which rooms are ready — replacing a paper diary and a spreadsheet that disagree most weeks.

The working files are about half of it, and the half that is built is the arithmetic. Rooms are never double-booked, stay lengths are enforced, rates are right, and a cancelled booking correctly frees its room. All of that is sound.

Everything about *people* is missing or wrong. There are no guest accounts — four staff names are typed into the working file instead. Nothing stops anybody changing a nightly rate. Nobody is ever emailed a confirmation. A guest can be checked into a room that has not been cleaned, and a room taken out of service is still sold. And because the cancellation test asks *is this person a guest* rather than *is this person allowed*, the cleaning staff can cancel any booking in the hotel — which nobody decided and no document permits.

Beneath all of it, nothing survives the program being switched off. And the one number the two documents disagree about has already been settled inside the working file without anybody being told.

## Everything in this analysis

| File | What it holds |
|---|---|
| [[01 - OVERVIEW]] | What this thing is and what it does |
| [[02 - DOCUMENT FINDINGS]] | What the specification and the handover notes say |
| [[03 - CODE FINDINGS]] | What the two working files actually do |
| [[04 - COMBINED FINDINGS]] | Where the documents and the working files agree and disagree |
| [[05 - SYSTEM ARCHITECTURE]] | The parts and how they hand work along |
| [[06 - DIAGRAMS]] | The pictures |
| [[07 - DEVELOPMENT ROADMAP]] | What to build, in what order |
| [[08 - ROADMAP TRACKER]] | Where each roadmap item stands |
| [[09 - TASK TRACKER]] | Every task and where it came from |
| [[10 - WORD LIST]] | Plain meanings for the words that could not be avoided |
| [[11 - PARTS IN DETAIL]] | What each part does and how it behaves |
| [[12 - SCREENS BY ROLE]] | What each of the four levels of person sees |

## How to read this

For the truth about the gap between what was promised and what exists, read [[04 - COMBINED FINDINGS]] — it is the most useful file here. For what to do about it, [[07 - DEVELOPMENT ROADMAP]]. For where things stand right now, [[08 - ROADMAP TRACKER]]. For how it all fits together, [[05 - SYSTEM ARCHITECTURE]] and [[06 - DIAGRAMS]]. For what any one part actually does, in full, [[11 - PARTS IN DETAIL]]. For what a guest, a receptionist, a member of the cleaning staff, or the manager each sees, [[12 - SCREENS BY ROLE]].

If you read only one diagram, read [[06 - DIAGRAMS#3. How a job gets done — cancelling a booking]]. The shape of that picture is the most serious thing this analysis found.

## Status legend

| Emoji | Means |
|---|---|
| ✅ | Finished — built, checked, working |
| 🟨 | Being worked on right now |
| ⭕ | Not started — waiting its turn |
| ❌ | Blocked — something is stopping it |
| 🔵 | Already there — found in the supplied material, built before this plan |
| ⬜ | Dropped — decided against, kept for the record |
| ❓ | Unclear — the material does not say |

## Open questions

Things the material does not settle. Each one is a real gap, not a guess dressed up as a question.

| # | Question | Why it matters | Who can answer |
|---|---|---|---|
| Q-01 | Is the cancellation cut-off 24 hours before arrival, or 48? | `Requirements.md` says 48. `Handover Notes.md` records the meeting agreeing 24 and asking for the specification to be updated, which never happened. The working file has already chosen 24. Three sources, two answers, and nobody outside the working file knows which won. Blocks [[08 - ROADMAP TRACKER\|R-03]]. | The hotel |
| Q-02 | Where should bookings, room states, and rates be kept once the program stops? | Today all three are held only while the program runs, so everything is lost when it stops. Neither document mentions keeping anything anywhere. Blocks [[08 - ROADMAP TRACKER\|R-01]], and everything else waits behind it. | The hotel, with whoever builds it |
| Q-03 | How is the late-cancellation charge actually taken? | `Requirements.md` charges one night for cancelling inside the cut-off, and separately rules out holding card details. `Handover Notes.md` records the developer pointing out that these cannot both be true, and nobody settling it. Blocks [[08 - ROADMAP TRACKER\|R-18]]. | The hotel |
| Q-04 | What does a guest account hold, and what should signing in check? | Accounts are required and that is the whole of what is said about them. Not enough to plan the work from. Blocks [[08 - ROADMAP TRACKER\|R-16]]. | The hotel |
| Q-05 | May a receptionist change a booking after the guest has checked in? | `Handover Notes.md` records this under *Left open* — nobody in the meeting knew. Until somebody says, there is no rule to build and no way to know whether the desk is currently doing something it should not. | The hotel |
| Q-06 | What happens to a booking when the guest never arrives? | Also *Left open* in the notes — the desk currently leaves it in the paper diary. There is no state for it, and a booking that is neither cancelled nor finished will sit in the system forever. | The hotel |
| Q-07 | Who puts a room back into service, and what happens to a booking already on a room that goes out? | The handover meeting asked for rooms to be taken out of service after a burst pipe and said nothing about either. Blocks [[08 - ROADMAP TRACKER\|R-15]]. | The hotel |
| Q-08 | Where should the occupancy figure appear, and how far back should it go? | `Handover Notes.md` says in its own words that nobody said. Blocks [[08 - ROADMAP TRACKER\|R-19]]. | The manager |
| Q-09 | What should the cleaning staff be able to do, beyond marking rooms? | They can currently cancel any booking in the hotel — not by anybody's decision, but because of how one test is written, see [[04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-02]]. The documents only ever say what to *add* for housekeeping, never what to keep them away from. Blocks [[08 - ROADMAP TRACKER\|R-11]]. | The hotel |
| Q-10 | May a receptionist resend a confirmation email, and may a guest ask for one? | `Requirements.md` requires an email on every confirmed booking and says nothing about sending one again. A front desk that cannot resend a confirmation is the sort of thing that surfaces in the first week. Surfaced by [[11 - PARTS IN DETAIL#What writing these turned up]]. | The hotel |
