# P-03 - THE ARRIVALS AND DEPARTURES DESK

[[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE|Back to start]] · [[ANALYSIS - HOTEL BOOKING SYSTEM/11 - PARTS IN DETAIL|All parts]] · Previous: [[P-02 - THE CANCELLER]] · Next: [[P-04 - THE ROOM BOOK]]

**What it is for:** Marking a guest as staying when they arrive and finished when they leave, and moving the room with them.
**Where it sits:** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#The parts]]
**Built by:** [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-07]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-14]]
**Status:** 🔵 Already there — found working in `booking_service.py`, lines 75–86. Two roadmap items still change it.

## Why it exists

The front desk needs to run arrivals and departures from the same system that holds the bookings — `Requirements.md`, under *Purpose*. Without this part a booking would be made and then nothing would ever happen to it.

It is also the only part that keeps a room's state in step with reality. When it works, a room becomes occupied when somebody walks in and dirty when they walk out, without anybody having to remember.

## What it does

| # | What it does | Who asks for it | Where the need came from |
|---|---|---|---|
| 1 | Marks an arriving guest's booking as staying | A receptionist | `Requirements.md`, *Who uses it* |
| 2 | Marks that guest's room as occupied | A receptionist | `booking_service.py`, line 79 |
| 3 | Marks a departing guest's booking as finished | A receptionist | `Requirements.md`, *Who uses it* |
| 4 | Marks that guest's room as dirty, so it can be cleaned | A receptionist | `booking_service.py`, line 85 |

Four things, and every one of them is a change with no question in front of it.

## Who may use it

| Who they are | What they may do here | What they may not do | Where the rule came from |
|---|---|---|---|
| A receptionist | Check guests in and out | The material sets no limit | `Requirements.md`, *Who uses it* |
| The manager | Everything a receptionist can | The material sets no limit | `Requirements.md`, *Who uses it* — managers do everything above |

**Nothing here checks anything.** This part never asks the level look-up who it is dealing with. A guest who could reach it could check themselves in, and there is nothing in either working file to stop them. The two rows above say what the documents intend, not what is enforced.

Whether a receptionist may change a booking once a guest has checked in is the one question the handover meeting recorded and could not answer — [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-05]]. It sits squarely on this part.

## The information it handles

| What it handles | What it is for | Where it is kept | Where this came from |
|---|---|---|---|
| The booking | The thing being moved from confirmed to staying to finished | In the list of bookings | `booking_service.py`, lines 78 and 84 |
| The room number on that booking | Finding the room whose state must change | With the booking | `booking_service.py`, lines 79 and 85 |
| The room's state | Moved to occupied on arrival and dirty on departure | In the room book, in memory only | `rooms.py`, lines 9–13 and 40–41 |
| The time of day | **Nothing.** This part never looks at a clock. | Nowhere | `booking_service.py`, lines 76–77 |

The last row is [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing|G-03]]. Two rules turn on the hour — check-in from 2pm, check-out by 11am — and nothing in either file has any notion of the time.

## How it behaves, step by step

**A guest arrives**

1. A receptionist hands the booking over — `booking_service.py`, line 75.
2. The booking is marked staying — line 78.
3. The room is marked occupied — line 79.
4. The booking is handed back — line 80.

**A guest leaves**

1. A receptionist hands the booking over — line 83.
2. The booking is marked finished — line 84.
3. The room is marked dirty — line 85.
4. The booking is handed back — line 86.

Both journeys are two changes and a reply, with no question asked at any point. The picture is [[ANALYSIS - HOTEL BOOKING SYSTEM/06 - DIAGRAMS#4. Who talks to whom, in order — checking a guest in]].

Two notes left in the working file at lines 76 and 77 admit that the room is never checked for cleanliness and the two o'clock rule is never applied. Whoever wrote this knew both were missing.

## The states things move through

| From | To | What causes the move | Who can cause it | Can it go back? |
|---|---|---|---|---|
| Confirmed | Staying | The desk checks the guest in | Anybody who reaches this part | No |
| Staying | Finished | The desk checks the guest out | Anybody who reaches this part | No |
| Room: clean | Room: occupied | A guest is checked in | The same | Only by checking out |
| Room: occupied | Room: dirty | A guest is checked out | The same | Only by the cleaning staff marking it |

This part moves two different things through states at once — a booking and a room — and that is what makes it worth a page. Nothing checks that the two agree. A booking can be moved to staying while its room is already occupied by somebody else's booking, because no check exists.

There is no state for a guest who never arrives. A booking sits at confirmed forever, which is [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-06]].

## What it checks before it agrees

Nothing at all. Both journeys change what they are given and hand it back.

Three checks the documents ask for are missing:

| # | What should be checked | Where the rule came from |
|---|---|---|
| 1 | Is the room marked clean? | `Requirements.md`, *Rules* item 6 — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing\|G-02]] |
| 2 | Is it 2pm or later, for a check-in? | `Requirements.md`, *Rules* item 4 |
| 3 | Is it 11am or earlier, for a check-out? | `Requirements.md`, *Rules* item 4 |

A fourth is implied and stated nowhere: is this booking in a state that can be checked in at all. Nothing stops a cancelled booking being checked in. It is not written above because no document asks for it — it is noted here so somebody can decide.

## When something goes wrong

**A guest can be put into a dirty room.** This is the most likely thing on this page to actually happen, because the desk has no way of knowing. The room book holds the answer and this part never asks it. Fixed by [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit|R-14]].

**The room state and the booking state can drift apart.** They are changed one after another with nothing tying them together. If the second change failed once the store is a lasting one, a booking would say staying while its room said clean, and nothing would ever notice. The material does not address this and it needs settling alongside [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-02]].

**Rooms go dirty and stay dirty.** The piece that marks a room clean exists at `rooms.py`, lines 36 to 37 and nothing calls it — [[ANALYSIS - HOTEL BOOKING SYSTEM/03 - CODE FINDINGS#What is unfinished, switched off, or unused|U-05]]. So today every departure makes a room dirty and no path exists to make it clean again. In a system that also refused dirty rooms, the hotel would sell out permanently after twenty-four stays.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | What it needs from it | What happens if that part is not there yet |
|---|---|---|
| [[P-01 - THE BOOKING MAKER\|P-01]] | A confirmed booking with a room on it | Nothing to check in |
| [[P-04 - THE ROOM BOOK\|P-04]] | Somewhere to record that a room is now occupied or dirty | It is there — `rooms.py`, lines 7–13 |
| The permission check | An answer to whether this person may check anybody in | It does not exist, so nobody is asked. Built as [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-08]]. |

**These need it:**

| Part | What it takes from this one |
|---|---|
| [[P-04 - THE ROOM BOOK\|P-04]] | Almost every state change a room ever undergoes |
| The occupancy report | Which bookings actually became stays, rather than being cancelled |

## How you know it is finished

- A guest cannot be checked into a room that has not been cleaned since the last guest left.
- A guest cannot be checked in at eleven in the morning, or checked out at three in the afternoon.
- Checking a guest out makes their room dirty, and the cleaning list shows it within moments.
- A booking that was cancelled cannot be checked in.

## What the material does not say

| # | What is unclear | Why it matters here |
|---|---|---|
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-05]] | May a receptionist change a booking after check-in? | This part is where a booking becomes a stay, so any rule about changing one afterwards lands here. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-06]] | What happens when a guest never arrives? | There is no state between confirmed and staying, so a no-show is indistinguishable from a booking for next year. |
