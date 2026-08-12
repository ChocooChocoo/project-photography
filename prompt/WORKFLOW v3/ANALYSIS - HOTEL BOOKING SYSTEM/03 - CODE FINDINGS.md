# 03 - CODE FINDINGS

[[00 - START HERE|Back to start]] · Previous: [[02 - DOCUMENT FINDINGS]] · Next: [[04 - COMBINED FINDINGS]]

## What was read

| Folder or file | What it appears to be for | How much was read |
|---|---|---|
| `booking_service.py` | Making, cancelling, and running bookings — arrivals and departures included | All 90 lines |
| `rooms.py` | The rooms themselves: which exist, what state each is in, and what each type costs a night | All 50 lines |

Two files, 140 lines between them, both read end to end. Nothing in these notes rests on guesswork about unread material.

## The main parts

| Part | Where it lives | What it is responsible for |
|---|---|---|
| The list of bookings | `booking_service.py`, line 11 | Holding every booking made since the program started |
| The staff list | `booking_service.py`, lines 15–20 | Four names with a level beside each. Stands in for real accounts. |
| The level look-up | `booking_service.py`, lines 23–24 | Answering what level a person is. Anybody not on the staff list is a guest. |
| The overlap check | `booking_service.py`, lines 27–35 | Answering whether a room is free across a run of nights |
| The booking maker | `booking_service.py`, lines 38–60 | Checking four rules, then writing the booking down |
| The canceller | `booking_service.py`, lines 63–72 | Deciding whether a cancellation is allowed, then marking it cancelled |
| The arrivals and departures desk | `booking_service.py`, lines 75–86 | Marking a guest as staying or finished, and moving the room's state with them |
| The messenger | `booking_service.py`, lines 89–90 | Meant to email the guest. Does nothing at all. |
| The room book | `rooms.py`, lines 7–23 and 36–50 | Which rooms exist, what type each is, and what state each is in |
| The rate table | `rooms.py`, lines 16–20, 26–28, and 31–33 | What a night costs, by room type |

## How work travels through it

**Making a booking**

1. Somebody asks for a room between two dates, naming themselves — `booking_service.py`, line 38.
2. The length of the stay is worked out and compared against the limits. Under one night or over fourteen is refused — lines 39 to 43.
3. The arrival date is compared against today. A date in the past is refused — lines 44 to 45.
4. The room is checked against every booking already made. Any overlap on any night is refused — lines 46 to 47, using the overlap check at lines 27 to 35.
5. Having passed all four, the booking is written down with the room's rate copied onto it, and marked confirmed — lines 49 to 59.
6. The finished booking is handed back — line 60.

Nothing calls the messenger at any point in this journey.

**Cancelling a booking**

1. Somebody asks to cancel, naming themselves and the booking — line 63.
2. How many days remain before arrival is worked out — line 64.
3. **If, and only if, that person is a guest**, two things are checked: that the booking is theirs, and whether the cut-off has passed — lines 65 to 70.
4. The booking is marked cancelled — line 71.

Step 3 is the important one, and it is examined below under *Things worth flagging*.

**Checking a guest in**

1. A booking is handed over — line 75.
2. It is marked as staying, and the room is marked occupied — lines 78 to 79.

Two notes left in the file at lines 76 and 77 admit that the room is never checked for cleanliness and the two o'clock rule is never applied.

**Checking a guest out**

1. A booking is handed over — line 83.
2. It is marked finished, and the room is marked dirty — lines 84 to 85.

Nothing checks the eleven o'clock rule.

## Where information is kept

| What is kept | Where it is kept | What it is for |
|---|---|---|
| Every booking — who, which room, arrival, departure, nights, rate, and state | A plain list held in the program's own memory, `booking_service.py`, line 11 | Deciding whether a room is free, and remembering what has been agreed |
| Every room — its type and its state | A set of entries held in the program's own memory, `rooms.py`, lines 7 to 13 | Knowing what the hotel has and what condition each room is in |
| The nightly rates, one per room type | Held in the program's own memory, `rooms.py`, lines 16 to 20 | Working out what a booking costs |
| Four staff names and their levels | Written directly into the working file, `booking_service.py`, lines 15 to 20 | Standing in for accounts that do not exist |

None of it outlives the program. A note beside the list of bookings at line 11 says so plainly. Every booking, every room state, and any changed rate is lost the moment the program stops.

## What it connects to on the outside

Nothing. Neither file talks to any other program or service. There is no email arrangement of any kind, despite the messenger existing at lines 89 to 90.

## What is unfinished, switched off, or unused

| # | What | Where | How it looks |
|---|---|---|---|
| U-01 | The messenger | `booking_service.py`, lines 89–90 | Written but empty. It has a name, it takes a booking, and it does nothing. Nothing calls it either. |
| U-02 | The late-cancellation charge | `booking_service.py`, lines 68–70 | Half-built. The charge is worked out and then nothing happens to it. A note in the file says as much. |
| U-03 | The cleanliness and time-of-day rules at check-in | `booking_service.py`, lines 76–77 | Not built. Two notes admit both are missing. |
| U-04 | The manager-only rule on rates | `rooms.py`, lines 31–33 | Not built. A note admits nothing checks, and the rate can be changed by anybody who can reach it. |
| U-05 | Marking a room clean, and taking one out of service | `rooms.py`, lines 36–37 and 44–46 | Built and working, and nothing anywhere calls either of them. |
| U-06 | Guest accounts | Nowhere in either file | Absent. A guest is a name handed in from outside; nothing creates, stores, or checks an account. |

## Things worth flagging

**Housekeeping can cancel any booking in the hotel.** The canceller only applies its two tests — is this your booking, and is there still time — when the person asking is a guest, `booking_service.py`, line 65. Everybody else falls straight past both to line 71 and the booking is cancelled. The specification gives that power to receptionists, `Requirements.md`, *Rules* item 8, and to nobody else. The cleaning staff have it because of how the test at line 65 is written, not because anybody decided they should. *Drawn from* lines 63 to 72.

**The cut-off is coded at twenty-four hours,** `booking_service.py`, line 9, with a note beside it recording that the specification says forty-eight and the handover meeting said twenty-four. The working file took the meeting's side and left the disagreement in writing.

**The late charge blocks nothing.** Even inside the cut-off, the booking is still cancelled — the charge is worked out at line 69 and the cancellation goes through at line 71 regardless. So today the cut-off changes nothing at all for anybody.

**A cancelled booking frees its room again.** The overlap check skips anything marked cancelled, lines 31 to 32. Neither document asks for this. It is the sensible behaviour, and it is worth knowing that it came from whoever wrote the file rather than from anybody's decision.

**An out-of-service room can still be booked.** The overlap check looks only at other bookings, lines 27 to 35. It never looks at the room's state. So a room marked out of service at `rooms.py`, line 46 is still sold — which is precisely what the handover meeting asked to prevent.

**Room states and rates do not survive either.** A room marked clean, or a rate a manager changed, is gone when the program stops, exactly as bookings are. This follows from `rooms.py`, lines 7 to 20, and is easy to miss because only the booking list carries a note admitting it.

**The overlap check reads every booking ever made** to answer one question, lines 28 to 35. For twenty-four rooms this will never matter. Worth knowing rather than worth fixing.

**Refusals are short phrases.** Five of them — lines 41, 43, 45, 47, and 67 — each a couple of words meant for whoever built the program. Whatever ends up showing these to a guest will need to turn them into something a person would want to read.

**What is built is built well.** All four checks happen before anything is written down, the overlap test is correct across a run of nights rather than a single date, and cancelled bookings correctly release their rooms. The problem with these files is what is missing from them, not what is in them.
