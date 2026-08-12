# P-01 - THE BOOKING MAKER

[[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE|Back to start]] · [[ANALYSIS - HOTEL BOOKING SYSTEM/11 - PARTS IN DETAIL|All parts]] · Next: [[P-02 - THE CANCELLER]]

**What it is for:** Checking the four rules a stay must pass, and recording the ones that pass.
**Where it sits:** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#The parts]]
**Built by:** [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive|R-02]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-05]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-06]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-13]]
**Status:** 🔵 Already there — found working in `booking_service.py`, lines 38–60. Four roadmap items still change it.

## Why it exists

The hotel wants guests to book a room on the website instead of telephoning — `Requirements.md`, under *Purpose*, recorded as [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do|D-01]]. This is the part that makes that possible.

It is also where every rule about *what may be booked* is enforced. No other part checks any of them.

## What it does

| # | What it does | Who asks for it | Where the need came from |
|---|---|---|---|
| 1 | Refuses a stay of less than one night, or more than fourteen | Whoever is asking for a room | `Requirements.md`, *Rules* item 2 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-03]] |
| 2 | Refuses an arrival date that has already passed | Whoever is asking for a room | *Drawn from* `booking_service.py`, lines 44–45. Neither document asks for this. |
| 3 | Refuses a room already taken on any night of the stay | Whoever is asking for a room | `Requirements.md`, *Rules* item 1 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-02]] |
| 4 | Copies the nightly rate for that room's type onto the booking | Whoever is asking for a room | `Requirements.md`, *Decisions* — rates are per room type |
| 5 | Records the booking and marks it confirmed | Whoever is asking for a room | `booking_service.py`, lines 49–59 |
| 6 | Hands the confirmed booking to the messenger, so a confirmation goes out | Nothing yet — this is not built | `Requirements.md`, *Rules* item 7 — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing\|G-01]] |

## Who may use it

| Who they are | What they may do here | What they may not do | Where the rule came from |
|---|---|---|---|
| A guest | Book a room for themselves | The material sets no limit | `Requirements.md`, *Purpose* and *Who uses it* |
| A receptionist | Take a booking on a guest's behalf | The material sets no limit | `Requirements.md`, *Rules* item 8 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-09]] |

**Nothing here checks anything.** This part is handed a name and writes it down; it never asks the level look-up who the person is. So the two rows above describe what the documents intend, not what the working file enforces — anyone who can reach this part can book in anybody's name.

The documents say nothing about whether the cleaning staff or the manager may book. No rows have been written for them, because there are none to write.

One limit the documents do set is not enforced here or anywhere: no group booking of more than three rooms at once, `Requirements.md`, *Not doing*. Nothing counts rooms per booking, because a booking is one room.

## The information it handles

| What it handles | What it is for | Where it is kept | Where this came from |
|---|---|---|---|
| Who is asking | Written onto the booking. Nothing checks it or looks it up. | With the booking, as a name | `booking_service.py`, lines 38 and 51 — [[ANALYSIS - HOTEL BOOKING SYSTEM/03 - CODE FINDINGS#What is unfinished, switched off, or unused\|U-06]] |
| Which room | Tested for a clash, and used to find the rate | With the booking | `booking_service.py`, lines 38 and 52 |
| The arrival and departure dates | Tested against today and against other stays | With the booking | `booking_service.py`, lines 53–54 |
| How many nights | Tested against the floor and the ceiling, then written down | With the booking | `booking_service.py`, lines 39 and 55 |
| The nightly rate | Copied from the rate table at the moment of booking | With the booking, as its own copy | `booking_service.py`, line 56 — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Built but never written down\|X-05]] |
| Whether the booking stands | Set to confirmed when written | With the booking | `booking_service.py`, line 57 |

Everything above lives in one place: a plain list held in the program's own memory, `booking_service.py`, line 11. It begins empty every time the program starts, so every booking is lost when it stops — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Built but never written down|X-01]].

The rate being copied rather than looked up later is worth noticing. It means a price change never alters a booking already made, which is almost certainly right and is written down in no document at all.

## How it behaves, step by step

**Somebody asks for a room**

1. They say who they are, which room, and the two dates — `booking_service.py`, line 38.
2. The number of nights is worked out from the dates — line 39.
3. Fewer than one night is refused — lines 40 to 41.
4. More than fourteen nights is refused — lines 42 to 43.
5. An arrival date earlier than today is refused — lines 44 to 45.
6. The room is checked against every booking already made, on every night of the stay. Any clash is refused — lines 46 to 47.
7. Having passed all four, the rate is fetched, the booking is assembled and marked confirmed, and it is written into the list — lines 49 to 59.
8. The finished booking is handed back — line 60.

All four checks happen before anything is written, so a half-made booking cannot exist. Nothing happens after step 8 — the confirmation email the specification promises is not on this path, because nothing calls the messenger.

Step 6 will gain a second question once [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-13]] is built: not only is the room free, but is it fit to sell. Today it is never asked, which is why a room out of service is still booked.

## The states things move through

| From | To | What causes the move | Who can cause it | Can it go back? |
|---|---|---|---|---|
| Nothing | Confirmed | A request passes all four checks | Whoever asked | Not applicable |

This part only ever creates. Everything that happens to a booking afterwards belongs to [[P-02 - THE CANCELLER|P-02]] and [[P-03 - THE ARRIVALS AND DEPARTURES DESK|P-03]]. The full life story is in [[ANALYSIS - HOTEL BOOKING SYSTEM/06 - DIAGRAMS#5. The life story of a booking]].

There is no held or pending state. A booking is confirmed or it was never made.

## What it checks before it agrees

| # | What is checked | What happens when the check fails | Where the rule came from |
|---|---|---|---|
| 1 | Is the stay at least one night? | Refused with a short phrase — `booking_service.py`, line 41 | `Requirements.md`, *Rules* item 2 |
| 2 | Is the stay fourteen nights or fewer? | Refused with a short phrase — line 43 | `Requirements.md`, *Rules* item 2 |
| 3 | Is the arrival date in the future? | Refused with a short phrase — line 45 | *Drawn from* lines 44–45 — no document asks for this |
| 4 | Is the room free on every night of the stay? | Refused with a short phrase — line 47 | `Requirements.md`, *Rules* item 1 |

All four refusals are short phrases meant for whoever built the program. Turning them into sentences a guest would want to read is [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-06]].

Check 4 is done properly. It compares the whole run of nights rather than a single date — `booking_service.py`, line 33 — so a stay that starts inside somebody else's stay is caught. It also skips bookings already cancelled, lines 31 to 32, so a cancelled booking frees its room. Neither document asks for either behaviour.

## When something goes wrong

Today, very little can. Everything this part needs is in the program's own memory, so nothing is unreachable, and all four checks run before anything is written. A request either becomes a booking or changes nothing at all.

That changes twice as the plan is built out.

**Once bookings are kept somewhere lasting** — [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive|R-01]] — the store can be unreachable at the moment of writing. The material says nothing about what should happen then, and it needs settling alongside [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-02]].

**Once confirmations are sent** — [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-05]] — a booking may be recorded while its email fails. [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#How work would pass between them]] settles this: the booking still stands and the failure is recorded.

**Two people asking for the same room at the same moment** is not addressed anywhere in the material, and cannot be seen in files of this size read on their own. It matters more here than in most systems, because a hotel sells the same twenty-four things over and over. Worth raising with whoever builds the lasting store.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | What it needs from it | What happens if that part is not there yet |
|---|---|---|
| The overlap check | An answer to whether a room is free across a run of nights | It is there, in the same file — `booking_service.py`, lines 27–35 |
| [[P-06 - THE RATE TABLE\|P-06]] | The nightly rate for a room's type | It is there — `rooms.py`, lines 26–28 |
| The list of bookings, and later the lasting store | Somewhere to write, and somewhere the overlap check can read | It works today against a list that forgets everything on restart |
| [[P-05 - THE MESSENGER\|P-05]] | Something to hand a confirmed booking to | Nothing happens. The guest is never told, and `Requirements.md`, *Rules* item 7 is simply not kept. |

**These need it:**

| Part | What it takes from this one |
|---|---|
| [[P-02 - THE CANCELLER\|P-02]] | The bookings it cancels, and whose they are |
| [[P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] | The bookings it checks in and out |
| The occupancy report | Every booking ever made, to count them |

## How you know it is finished

- A stay of nought nights and a stay of fifteen nights are both refused, and both refusals read as sentences a guest would understand.
- A room booked for the 3rd to the 6th cannot also be booked for the 5th to the 8th.
- A booking made today is still blocking that room after the program has been stopped and started.
- A room marked out of service cannot be booked at all.
- Make a booking and a confirmation email arrives.

## What the material does not say

| # | What is unclear | Why it matters here |
|---|---|---|
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-02]] | Where should bookings be kept once the program stops? | Everything this part records is lost today, and the answer decides what "something went wrong while writing" even means. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-04]] | What does a guest account hold? | This part writes down a name and never checks it. Until accounts exist, anybody can book in anybody's name. |
