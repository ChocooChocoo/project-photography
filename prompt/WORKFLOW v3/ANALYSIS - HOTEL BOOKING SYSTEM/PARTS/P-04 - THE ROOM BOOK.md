# P-04 - THE ROOM BOOK

[[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE|Back to start]] · [[ANALYSIS - HOTEL BOOKING SYSTEM/11 - PARTS IN DETAIL|All parts]] · Previous: [[P-03 - THE ARRIVALS AND DEPARTURES DESK]] · Next: [[P-05 - THE MESSENGER]]

**What it is for:** Holding which rooms the hotel has, what type each is, and what state each is in.
**Where it sits:** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#The parts]]
**Built by:** [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-13]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-15]]
**Status:** 🔵 Already there — found working in `rooms.py`, lines 7–23 and 36–50. Two roadmap items still change it.

## Why it exists

A hotel is its rooms. Twenty-four of them, ten Standard, ten Double, four Suite — `Requirements.md`, under *Decisions*, recorded as [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised and built|M-04]]. Every other part needs to know what exists before it can do anything.

It also holds something no document ever asked for: a state on each room. That state is the whole reason the cleaning staff are users of this system at all.

## What it does

| # | What it does | Who asks for it | Where the need came from |
|---|---|---|---|
| 1 | Holds twenty-four rooms with a type on each | [[P-06 - THE RATE TABLE\|P-06]], and anything needing to know what the hotel has | `Requirements.md`, *Decisions* — built at `rooms.py`, lines 8–13 |
| 2 | Holds a state on each room — clean, occupied, dirty, or out of service | [[P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] | *Drawn from* `rooms.py`, line 23. No document lists these four. |
| 3 | Marks a room clean | Nothing calls it | `rooms.py`, lines 36–37 — [[ANALYSIS - HOTEL BOOKING SYSTEM/03 - CODE FINDINGS#What is unfinished, switched off, or unused\|U-05]] |
| 4 | Marks a room dirty | [[P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]], on check-out | `rooms.py`, lines 40–41 |
| 5 | Takes a room out of service | Nothing calls it | `Handover Notes.md`, *What we agreed* — built at `rooms.py`, lines 44–46 |
| 6 | Lists every room of a given type | Nothing calls it | `rooms.py`, lines 49–50 |

Three of the six are reachable by nothing at all.

## Who may use it

| Who they are | What they may do here | What they may not do | Where the rule came from |
|---|---|---|---|
| The cleaning staff | Mark a room clean. Take a room out of service. | See any guest name attached to a room | `Handover Notes.md`, *What we agreed* — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-12]] and [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-13]] |
| A receptionist | See which rooms are ready | Nothing is written down | *Drawn from* `Requirements.md`, *Who uses it* — the desk cannot run arrivals without knowing |
| The manager | Everything above | Nothing is written down | *Drawn from* `Requirements.md`, *Who uses it* |

**None of this is enforced, and none of it is even reachable.** The two pieces the cleaning staff would use exist and nothing calls them, so today the cleaning staff cannot use this part at all — see [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-15]].

The rule about guest names is the one permission in the whole system that is about *seeing* rather than *doing*, and it is the head of housekeeping's own request. Nothing anywhere separates what one level sees from another — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing|G-09]].

## The information it handles

| What it handles | What it is for | Where it is kept | Where this came from |
|---|---|---|---|
| Twenty-four room numbers | Naming the rooms — 101 to 110, 201 to 210, 301 to 304 | In the program's own memory | `rooms.py`, lines 8–13 |
| A type on each room | Finding the right nightly rate | With the room | `rooms.py`, lines 4 and 9–13 |
| A state on each room | Knowing whether a room is ready, in use, needs cleaning, or is broken | With the room | `rooms.py`, lines 9–13 and 23 |

All of it is rebuilt from scratch every time the program starts, with **every room marked clean** — `rooms.py`, lines 8 to 13. A hotel restarting its system at midnight would find every dirty room magically ready and every broken room back on sale. Nothing in the material mentions this, and unlike the list of bookings it carries no note admitting it. Part of [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Built but never written down|X-01]].

## How it behaves, step by step

**A room is cleaned**

1. The cleaning staff say the room is done — no path to this exists today.
2. The room's state becomes clean — `rooms.py`, line 37.

**A room breaks**

1. Somebody says the room is out of service — no path to this exists today either.
2. The room's state becomes out of service — `rooms.py`, line 46.
3. Nothing else happens. The room continues to be sold, because the overlap check never reads room state.

Step 3 is [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing|G-08]], and it defeats the entire reason the handover meeting asked for out of service in the first place — a burst pipe in room 204 that the desk kept selling.

## The states things move through

| From | To | What causes the move | Who can cause it | Can it go back? |
|---|---|---|---|---|
| Clean | Occupied | A guest is checked in | [[P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] | By checking out |
| Occupied | Dirty | A guest is checked out | [[P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] | No |
| Dirty | Clean | The room is marked done | Nothing today — the piece exists and nothing calls it | Yes, by the next guest |
| Any | Out of service | The room is taken out of service | Nothing today — same | Nothing puts a room back |

The last row is worth staring at. A room can go out of service and **nothing anywhere brings it back** — no piece exists for it. The handover meeting asked for the way out and never mentioned the way in, which is [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-07]].

The picture is [[ANALYSIS - HOTEL BOOKING SYSTEM/06 - DIAGRAMS#6. The life story of a room]].

Nothing prevents any move. A room being lived in can be marked clean, or out of service, with the guest still in it.

## What it checks before it agrees

Nothing. Every one of the four state changes happens on request, with no question asked about who is asking or what state the room is in now.

Two checks the material implies and nobody wrote:

| # | What should be checked | Where the rule came from |
|---|---|---|
| 1 | Is this person allowed to change a room's state? | *Drawn from* `Requirements.md`, *Who uses it*, which gives marking rooms to the cleaning staff and nobody else |
| 2 | Is there a guest in the room right now? | *Drawn from* `Handover Notes.md` — a room taken out of service should stop being sold, which implies knowing whether it is currently in use |

Both are marked *drawn from* because neither document states them. They are what the stated rules would need in order to work.

## When something goes wrong

**Every room state is lost on restart, and all of them come back clean.** This is the most dangerous behaviour in the whole system, because it fails silently and in the direction of selling rooms that should not be sold. A dirty room becomes clean. A broken room becomes sellable. Fixed by [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive|R-01]], and worth naming to whoever answers [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-02]].

**A room can go out of service with a booking already on it.** Nothing looks, nothing warns, and nobody has said what should happen to that guest. This is the second half of [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-07]] and it is the question the hotel is most likely to hit in practice.

**Rooms accumulate as dirty.** With no path to marking a room clean, every check-out permanently removes a room from the pool — once rooms are actually checked before sale.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | What it needs from it | What happens if that part is not there yet |
|---|---|---|
| The lasting store | Somewhere for room states to survive a restart | Every state resets to clean. Built as [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-01]]. |
| The permission check | An answer to whether this person may change a room's state | It does not exist, so anybody may |

**These need it:**

| Part | What it takes from this one |
|---|---|
| [[P-06 - THE RATE TABLE\|P-06]] | A room's type, to find its rate |
| [[P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] | Somewhere to record occupied and dirty |
| The overlap check | Nothing today. After [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-13]], a room's state, so unfit rooms are not sold. |

## How you know it is finished

- Mark a room dirty, restart the program, and it is still dirty.
- A room marked out of service cannot be booked by anybody, on any night.
- A member of the cleaning staff can mark a room done, and the front desk sees it immediately.
- A room put back into service becomes bookable again.
- The cleaning list shows room numbers and states, and no guest names anywhere on it.

## What the material does not say

| # | What is unclear | Why it matters here |
|---|---|---|
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-02]] | Where should room states be kept once the program stops? | Every state resets to clean on restart, which is the worst possible default. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-07]] | Who puts a room back into service, and what happens to a booking already on it? | Nothing puts a room back at all, and nothing considers the guest already booked into a room that breaks. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-09]] | What may the cleaning staff do? | This is the part they are supposed to use, and it is the only part where the material gives them anything. |
