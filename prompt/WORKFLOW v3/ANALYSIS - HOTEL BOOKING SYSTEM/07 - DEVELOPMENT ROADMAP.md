# 07 - DEVELOPMENT ROADMAP

[[00 - START HERE|Back to start]] · Previous: [[06 - DIAGRAMS]] · Next: [[08 - ROADMAP TRACKER]]

## What this covers

Building work only — what gets made, in what order, and why that order. It reaches from where the two working files stand today to the point where everything the two documents promise is actually there. Nothing here concerns cost, staffing, opening dates, or how the hotel is run.

## Where the plan came from

Everything below is drawn from the four supplied files and nothing else. The gaps come from [[04 - COMBINED FINDINGS]]; the promises being honoured come from [[02 - DOCUMENT FINDINGS]]; the state of what already exists comes from [[03 - CODE FINDINGS]]. Two items — R-01 and R-02 — come from something none of the documents says, which is that nothing survives the program stopping.

## The phases at a glance

| Phase | What it delivers | Waits on |
|---|---|---|
| Phase 1 — Bookings And Rooms Survive | A booking made today, and a room cleaned today, are still so tomorrow | Nothing — this one starts |
| Phase 2 — The Rules Match What Was Promised | The written promises about bookings are actually kept | Phase 1 |
| Phase 3 — Every Level Has The Right Rights | Each of the four levels can do what it should and no more | Phase 1 |
| Phase 4 — Rooms Are Only Sold When They Are Fit | A dirty or broken room cannot be sold or walked into | Phase 3 |
| Phase 5 — Guests Are Recognised | A returning guest is known as the same person | Phase 3, and an answer to [[00 - START HERE#Open questions\|Q-04]] |
| Phase 6 — The Hotel Can See Itself | Money owed is recorded, and the manager can see how full the hotel has been | Phase 2 |

Phases 2 and 3 do not depend on each other and may run alongside. The same picture is in [[06 - DIAGRAMS#7. The order of the phases]].

---

## Phase 1 — Bookings And Rooms Survive

**The goal:** A booking made today is still there tomorrow, and so is a room marked clean, and so is a rate somebody changed — after the program has been switched off and on again.

**Why it comes first:** Everything is held in the program's own memory and lost when it stops — [[04 - COMBINED FINDINGS#Built but never written down|X-01]]. Every other phase adds something on top of bookings and rooms. Building any of it first would mean building on something that forgets, and then coming back to change the same parts a second time.

**What gets built:**

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-01 | A lasting place to keep bookings, room states, and rates | So none of them outlives only the program running | *Drawn from* `booking_service.py`, line 11 and `rooms.py`, lines 7–20 — [[04 - COMBINED FINDINGS#Built but never written down\|X-01]] |
| R-02 | Point the booking maker, the overlap check, the canceller, and the arrivals desk at that lasting place | So new bookings are kept there, old ones still block a taken room, and a cleaned room stays cleaned | *Drawn from* `booking_service.py`, lines 27–35, 49–60, 71, and 78–85 |

**How you know the phase is finished:**

- Make a booking, stop the program, start it again — the booking is still there.
- Try to book the same room on the same nights after restarting — it is refused.
- Mark a room dirty, restart, and it is still dirty.
- Change a rate, restart, and the new rate is still in force.

**What could hold it up:** [[00 - START HERE#Open questions|Q-02]] is unanswered — nobody has said where any of this ought to be kept. The work cannot start until someone decides. This is why R-01 shows as blocked in [[08 - ROADMAP TRACKER]] rather than merely not started.

---

## Phase 2 — The Rules Match What Was Promised

**The goal:** Every rule the documents write about bookings is genuinely enforced, with the right numbers in it.

**Why it comes here:** It needed Phase 1 because the confirmation email must go out for bookings that actually last. Sending a confirmation for something that will vanish when the program stops would be worse than sending nothing.

**What gets built:**

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-03 | Settle the cancellation cut-off and put one agreed number in one place | The documents say 48, the meeting said 24, the working file quietly chose 24 | `Requirements.md`, *Rules* item 3 — [[04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-01]] |
| R-04 | Fill in the messenger, so confirmation emails actually go out | Promised for every confirmed booking, and today nothing happens | `Requirements.md`, *Rules* item 7 — [[04 - COMBINED FINDINGS#Promised but missing\|G-01]] |
| R-05 | Have the booking maker ask the messenger to send, once a booking is safely kept | The messenger exists but nothing reaches for it | `booking_service.py`, lines 89–90 — [[03 - CODE FINDINGS#What is unfinished, switched off, or unused\|U-01]] |
| R-06 | Turn the five refusals into wording a guest would understand | They are short phrases meant for whoever built it, not for a person | *Drawn from* `booking_service.py`, lines 41, 43, 45, 47, and 67 |
| R-07 | Enforce check-in from 2pm and check-out by 11am | Both are promised and neither is applied | `Requirements.md`, *Rules* item 4 — [[04 - COMBINED FINDINGS#Promised but missing\|G-03]] |

**How you know the phase is finished:**

- The cut-off is one number, it matches what the hotel says it should be, and it appears in only one place.
- Make a booking and a confirmation email arrives.
- Every refusal a guest can trigger reads as a sentence, not a fragment.
- A guest cannot be checked in at eleven in the morning, or checked out at three in the afternoon.

**What could hold it up:** R-03 rests on [[00 - START HERE#Open questions|Q-01]] — 24 or 48 has not been confirmed by the hotel, and two documents disagree about it. It is a one-number change once answered, and cannot be guessed at before.

---

## Phase 3 — Every Level Has The Right Rights

**The goal:** Each of the four levels can do exactly what the documents say it can, and nothing else.

**Why it comes here:** It needed Phase 1, because a permission arrangement that reads bookings from a list which vanishes can only ever police bookings made since the last restart. It does not need Phase 2 and may be built alongside it.

**What gets built:**

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-08 | Build the permission check — one part that answers what a person of a given level may do | Four levels are promised and only one lopsided test exists | `Requirements.md`, *Who uses it* and *Rules* items 5 and 8 — [[04 - COMBINED FINDINGS#Promised but missing\|G-06]] |
| R-09 | Put the permission check in front of cancelling, replacing the is-this-a-guest test | Everybody who is not a guest can currently cancel anything | `booking_service.py`, lines 65 and 71 — [[04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-02]] |
| R-10 | Put the permission check in front of changing a rate | Only a manager may change a rate, and today anybody may | `Requirements.md`, *Rules* item 5 — [[04 - COMBINED FINDINGS#Promised but missing\|G-04]] |
| R-11 | Decide what the cleaning staff may do, and set it | They can cancel any booking in the hotel by accident | *Drawn from* `booking_service.py`, line 65 — [[04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-02]] |
| R-12 | Keep guest names off anything the cleaning staff see | Asked for by name at the handover meeting | `Handover Notes.md`, *What we agreed* — [[04 - COMBINED FINDINGS#Promised but missing\|G-09]] |

**How you know the phase is finished:**

- A receptionist can cancel any booking, and is not charged for it.
- A guest can cancel their own booking and cannot cancel anybody else's.
- A member of the cleaning staff cannot cancel a booking at all.
- Somebody who is not a manager cannot change a nightly rate.
- The cleaning list shows rooms and states, and no guest names anywhere on it.

**What could hold it up:** R-11 rests on [[00 - START HERE#Open questions|Q-09]] — nobody has said what the cleaning staff are meant to be able to do, only what the handover meeting asked to add. Until Phase 5 exists there is also no proper way to tell one guest from another, so R-08 will lean on the four names already written into the working file, to be replaced in Phase 5.

---

## Phase 4 — Rooms Are Only Sold When They Are Fit

**The goal:** A room that is dirty, or out of service, cannot be sold to anybody or walked into by anybody.

**Why it comes here:** It needed Phase 3, because it hands the cleaning staff a lever that takes rooms off sale, and today their rights are an accident rather than a decision. Giving that lever to a group whose powers nobody has settled would make the accident worse.

**What gets built:**

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-13 | Have the overlap check read a room's state and refuse anything not fit to sell | An out-of-service room is still sold today, which is the exact thing the meeting asked to prevent | `Handover Notes.md`, *What we agreed* — [[04 - COMBINED FINDINGS#Promised but missing\|G-08]] |
| R-14 | Refuse a check-in into a room that is not marked clean | Promised, and admitted as missing in the working file itself | `Requirements.md`, *Rules* item 6 — [[04 - COMBINED FINDINGS#Promised but missing\|G-02]] |
| R-15 | Give the cleaning staff a way to mark a room clean, take one out of service, and put one back | The pieces that do the first two exist and nothing calls them; nothing at all puts a room back | `booking_service.py` and `rooms.py`, lines 36–37 and 44–46 — [[03 - CODE FINDINGS#What is unfinished, switched off, or unused\|U-05]] |

**How you know the phase is finished:**

- A room marked out of service cannot be booked by anybody, on any night.
- A guest cannot be checked into a room that has not been cleaned since the last guest left.
- A member of the cleaning staff can mark a room done, and the front desk can see it immediately.
- A room put back into service becomes bookable again.

**What could hold it up:** R-15 rests on [[00 - START HERE#Open questions|Q-07]] — the meeting asked for rooms to be taken out of service and said nothing about who puts them back, or what becomes of a booking already sitting on a room that goes out. Neither can be guessed at.

---

## Phase 5 — Guests Are Recognised

**The goal:** A returning guest is known as the same person who came before.

**Why it comes here:** It needed Phase 3, because there is no point recognising somebody until something is asking who they are. The permission check built in R-08 is the first thing in the whole arrangement that needs a real answer to that question — today it makes do with four names typed into a file.

**What gets built:**

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-16 | Guest accounts | Required so that repeat visits are recognised | `Requirements.md`, *Decisions* — [[04 - COMBINED FINDINGS#Promised but missing\|G-07]] |
| R-17 | Replace the four staff names written into the working file with real accounts | So the permission check rests on something that can be changed without editing the program | `booking_service.py`, lines 15–20 — [[04 - COMBINED FINDINGS#Built but never written down\|X-02]] |

**How you know the phase is finished:**

- The same guest booking twice is recognised as the same person both times.
- A new receptionist can be added without anybody editing the program.
- The permission check tells one guest from another without the four names in the file.

**What could hold it up:** The specification says accounts are required and nothing more — not what they hold, not what signing in should check. This is [[00 - START HERE#Open questions|Q-04]], and the phase cannot be planned in any more detail until it is answered. R-17 is marked unclear in [[08 - ROADMAP TRACKER]] because how much of it is needed depends entirely on that answer.

---

## Phase 6 — The Hotel Can See Itself

**The goal:** Money the hotel is owed is written down, and the manager can see how full the hotel has been.

**Why it comes here:** It needed Phase 2, because both items rest on rules that Phase 2 corrects. A charge cannot be recorded against a cut-off nobody has agreed, and an occupancy figure counted from bookings that vanish on restart would be worthless.

**What gets built:**

| # | What gets built | Why it is needed | Where the need came from |
|---|---|---|---|
| R-18 | Record what a late cancellation owes, so the desk can collect it | The charge is worked out today and thrown away | `Requirements.md`, *Rules* item 3 — [[04 - COMBINED FINDINGS#Promised but missing\|G-05]] |
| R-19 | Show the manager how full the hotel has been, by week | Asked for at the handover meeting, and nothing counts anything today | `Handover Notes.md`, *What we agreed* — [[04 - COMBINED FINDINGS#Promised but missing\|G-10]] |

**How you know the phase is finished:**

- A guest who cancels inside the cut-off leaves a record of one night owed, and the desk can find it.
- The manager can see how full the hotel was last week, and the week before.

**What could hold it up:** R-18 rests on [[00 - START HERE#Open questions|Q-03]], which is the sharpest disagreement in the whole material: the specification promises to charge a late canceller, and separately rules out holding card details. Both cannot be true. R-19 rests on [[00 - START HERE#Open questions|Q-08]] — the meeting asked for the figure and, in its own words, nobody said where it should appear or how far back it should go.

---

## Deliberately left out

| What | Why it is not here |
|---|---|
| Taking payment online | Decided against — payment happens at the desk on the existing card machine, `Requirements.md`, *Not doing*. |
| Group bookings of more than three rooms | Decided against for this version — those go by telephone as they do today, `Requirements.md`, *Not doing*. |
| Connecting to the outside booking websites | Wanted, and explicitly not in the first version, `Requirements.md`, *Not doing*. Left here so nobody mistakes its absence for an oversight. |
| Changing a booking after check-in | Not building work until somebody answers [[00 - START HERE#Open questions\|Q-05]]. `Handover Notes.md` records that nobody in the meeting knew. |
| Deciding what happens to a guest who never arrives | Same — [[00 - START HERE#Open questions\|Q-06]]. There is no state for it and nobody has said there should be. |
| Making the overlap check faster | Real — it reads every booking ever made — but it will never matter at twenty-four rooms, [[03 - CODE FINDINGS#Things worth flagging]]. Noted so nobody spends a day on it. |
