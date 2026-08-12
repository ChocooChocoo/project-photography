# 02 - DOCUMENT FINDINGS

[[00 - START HERE|Back to start]] · Previous: [[01 - OVERVIEW]] · Next: [[03 - CODE FINDINGS]]

## What was read

| Document | Kind | How much was read |
|---|---|---|
| `Requirements.md` | A written specification, version 0.4, dated 3 August 2026 | All of it |
| `Handover Notes.md` | Notes from a meeting on 21 July 2026 | All of it |

Two documents, both short. The specification is orderly — a purpose, four levels of user, nine rules, three decisions, three things ruled out. The handover notes are looser, and they disagree with the specification in one place and go beyond it in three others.

The notes are dated thirteen days before the specification, which matters: the specification is the newer document and still carries the older number. See the contradiction below.

## What the documents say the system must do

| # | What it must do | Where it says so |
|---|---|---|
| D-01 | Let guests book a room on the website instead of telephoning | `Requirements.md`, under *Purpose* |
| D-02 | Never let two stays overlap on the same room | `Requirements.md`, *Rules* item 1 |
| D-03 | Accept stays of at least one night and at most fourteen | `Requirements.md`, *Rules* item 2 |
| D-04 | Let a guest cancel free of charge up to a set time before arrival, and charge one night inside it | `Requirements.md`, *Rules* item 3 |
| D-05 | Allow check-in from 2pm and require check-out by 11am | `Requirements.md`, *Rules* item 4 |
| D-06 | Let only a manager change a nightly rate | `Requirements.md`, *Rules* item 5 |
| D-07 | Require a room to be marked clean before a guest is checked into it | `Requirements.md`, *Rules* item 6 |
| D-08 | Email the guest for every confirmed booking | `Requirements.md`, *Rules* item 7 |
| D-09 | Let a receptionist book on a guest's behalf, and cancel any booking with no charge | `Requirements.md`, *Rules* item 8 |
| D-10 | Take payment at the desk on arrival | `Requirements.md`, *Rules* item 9 |
| D-11 | Require guest accounts, so a returning guest is recognised | `Requirements.md`, under *Decisions* |
| D-12 | Let housekeeping take a room out of service, and stop anybody booking it until it comes back | `Handover Notes.md`, under *What we agreed* |
| D-13 | Keep guest names off the cleaning list entirely | `Handover Notes.md`, under *What we agreed* |
| D-14 | Show the manager how full the hotel has been, by week | `Handover Notes.md`, under *What we agreed* |

## Rules it has to follow

Nine limits in the specification, and one more from the notes.

- **No room is sold twice.** No two stays may overlap on one room for any night — *Rules* item 1.
- **One to fourteen nights.** Shorter or longer is refused — *Rules* item 2.
- **Cancelling is free until a cut-off, then it costs a night.** Where the cut-off sits is disputed — see below — *Rules* item 3.
- **Two o'clock in, eleven o'clock out.** *Rules* item 4.
- **Rates are a manager's business.** Nobody else may change one — *Rules* item 5.
- **A dirty room is not sold.** A room must be marked clean before a guest goes into it — *Rules* item 6.
- **Every confirmation is told.** A booking confirmed and not emailed is a broken rule, not an oversight — *Rules* item 7.
- **The desk has powers a guest does not.** A receptionist may book for somebody else, and may cancel anything without charging — *Rules* item 8.
- **Money changes hands at the desk.** *Rules* item 9.
- **A broken room can be pulled from sale.** Housekeeping marks it out of service and nobody may book it until it is marked back — `Handover Notes.md`.

Three of these depend on a time of day rather than a date: the cancellation cut-off, check-in, and check-out. Nothing in either document says whether a booking carries a time at all.

## Decisions already made

| Decision | Reasoning given | Where it says so |
|---|---|---|
| Guest accounts are required | So a returning guest is recognised and does not retype everything | `Requirements.md`, *Decisions*, 12 July 2026 |
| Rates are per room type per night, not per room | Rooms of the same type are the same room to the hotel | `Requirements.md`, *Decisions*, 19 July 2026 |
| Twenty-four rooms — ten Standard, ten Double, four Suite | This is the hotel as it stands | `Requirements.md`, *Decisions*, 19 July 2026 |
| No payment online | There is a card machine at the desk and it works | `Requirements.md`, *Not doing* |
| No group bookings above three rooms at once | Larger groups go by telephone, as they do today | `Requirements.md`, *Not doing* |
| No connection to the outside booking websites yet | Wanted, but not in the first version | `Requirements.md`, *Not doing* |

## Where the documents disagree

| # | One says | The other says | Where |
|---|---|---|---|
| C-01 | A guest may cancel free of charge up to **48 hours** before arrival | The meeting agreed **24 hours**, because 48 loses too many late bookings, and somebody was supposed to update the specification | `Requirements.md`, *Rules* item 3, and `Handover Notes.md`, *What we agreed* |

The notes say the specification should have been updated and it was not — the specification is the newer document of the two and still says 48. So the older document records the later decision. Which is right is not for these notes to settle: [[00 - START HERE#Open questions|Q-01]].

This one matters more than a number usually would, because the working file has already taken a side. See [[04 - COMBINED FINDINGS#Where they flatly contradict each other|K-01]].

## What the documents leave unsaid

- **Where bookings are kept.** Neither document says a booking must survive anything. Raised as [[00 - START HERE#Open questions|Q-02]].
- **How the late cancellation is charged.** The specification charges a night; it also rules out holding card details. The notes record the developer saying these two cannot both be true and nobody resolving it. Raised as [[00 - START HERE#Open questions|Q-03]].
- **What a guest account holds.** Accounts are required, and that is the whole of what is said. Raised as [[00 - START HERE#Open questions|Q-04]].
- **Whether a receptionist may change a booking after check-in.** Written down in `Handover Notes.md`, under *Left open*, as something nobody in the room knew. Raised as [[00 - START HERE#Open questions|Q-05]].
- **What happens when a guest never arrives.** Also *Left open* — the desk currently leaves it in the diary. Raised as [[00 - START HERE#Open questions|Q-06]].
- **Who puts a room back into service,** and what becomes of a booking already sitting on a room that goes out of service. The notes ask for the lever and say nothing about either. Raised as [[00 - START HERE#Open questions|Q-07]].
- **Where the occupancy figure should appear,** and how far back it should reach. The notes say plainly that nobody said. Raised as [[00 - START HERE#Open questions|Q-08]].
