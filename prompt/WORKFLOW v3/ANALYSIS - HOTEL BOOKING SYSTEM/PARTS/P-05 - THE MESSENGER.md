# P-05 - THE MESSENGER

[[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE|Back to start]] · [[ANALYSIS - HOTEL BOOKING SYSTEM/11 - PARTS IN DETAIL|All parts]] · Previous: [[P-04 - THE ROOM BOOK]] · Next: [[P-06 - THE RATE TABLE]]

**What it is for:** Telling the guest, by email, that their booking stands.
**Where it sits:** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#What changes and why]]
**Built by:** [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-04]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-05]]
**Status:** ⭕ Not started. A named but empty shell exists — `booking_service.py`, lines 89–90, recorded as [[ANALYSIS - HOTEL BOOKING SYSTEM/03 - CODE FINDINGS#What is unfinished, switched off, or unused|U-01]].

## Why it exists

The specification promises an email for every confirmed booking — `Requirements.md`, *Rules* item 7, recorded as [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do|D-08]]. [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#Rules it has to follow]] puts it plainly: a booking confirmed and not emailed is a broken rule, not an oversight.

Today nothing is sent. A part exists with the right name, it takes a booking, and it does nothing. Nothing calls it either. Two failures rather than one — the sending was never built, and [[P-01 - THE BOOKING MAKER|P-01]] was never wired to ask for it. They are separate roadmap items for that reason.

This would also be the first time the system talks to anything outside itself. Today it talks to nothing — [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#Where it touches the outside world]].

## What it does

| # | What it does | Who asks for it | Where the need came from |
|---|---|---|---|
| 1 | Sends the guest an email saying their booking stands | [[P-01 - THE BOOKING MAKER\|P-01]], once a booking is safely kept | `Requirements.md`, *Rules* item 7 |
| 2 | Records a failure to send, rather than discarding it | Nobody — it does this on its own | [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#How work would pass between them]] |

What the email says is nowhere in the material. The rule requires that one is sent and nothing about its contents — [[ANALYSIS - HOTEL BOOKING SYSTEM/09 - TASK TRACKER|T-10]] exists to get that decided.

## Who may use it

Nobody uses this part directly. It is set going by [[P-01 - THE BOOKING MAKER|P-01]] and by nothing else.

The material says nothing about a receptionist sending a confirmation again, or a guest asking for one. Neither is written above, because neither is asked for — and a front desk that cannot resend a confirmation is the kind of thing somebody notices in the first week. Worth putting to the hotel rather than deciding here.

## The information it handles

| What it handles | What it is for | Where it is kept | Where this came from |
|---|---|---|---|
| The confirmed booking — room, dates, nights, rate | Whatever the email says | In the list of bookings | `booking_service.py`, lines 49–58 |
| The guest's email address | Somewhere to send it | **Nowhere.** Nothing in any of the four files holds one. | [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-04]] |
| Whether the message was sent | So a failure is not silently lost | Not decided — nothing holds this today | [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#How work would pass between them]] |

The second row is the problem, and it is the same problem twice. A booking carries a name — `booking_service.py`, line 51 — and nothing suggests that name is an email address. Nothing in either document provides one either. An email is promised to a guest the system has no way of reaching.

The likeliest home for an address is a guest account, which is required and otherwise undescribed. That makes this part quietly dependent on [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-04]] — a dependency the roadmap does not show, because the roadmap puts the messenger in Phase 2 and accounts in Phase 5.

**This is the one place where the plan and this page disagree**, and it is recorded here rather than quietly fixed. Either the messenger needs an address from somewhere before Phase 5, or R-04 cannot finish when the roadmap says it can.

## How it behaves, step by step

**A booking is confirmed**

1. [[P-01 - THE BOOKING MAKER|P-01]] writes the booking down and marks it confirmed — `booking_service.py`, lines 49 to 59.
2. It hands the finished booking to this part.
3. The email is sent to the guest.
4. Whoever asked for the booking is handed it back without waiting for step 3 to finish.

Step 4 comes from [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#How work would pass between them]]: the booking is not held up waiting for the email to leave. Everything else is *drawn from* the same section and `Requirements.md`, *Rules* item 7.

## The states things move through

| From | To | What causes the move | Who can cause it | Can it go back? |
|---|---|---|---|---|
| Not sent | Sent | The email leaves successfully | This part, on its own | Not applicable |
| Not sent | Failed | The email cannot be sent | This part, on its own | The material does not say whether a failure is ever tried again |

A booking's own state is untouched by any of this. A confirmed booking whose email failed is still confirmed.

## What it checks before it agrees

Nothing. The rule asks for an email on every confirmed booking with no condition attached, so this part refuses nothing.

The one thing it cannot do without is an address, and that is a gap in the material rather than a check this part performs.

## When something goes wrong

**A failed email does not undo a booking.** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#How work would pass between them]] settles it: the booking still stands and the failure is recorded rather than thrown away. The guest has a room and does not know it — which is bad, and better than losing the room.

**A confirmation must never go out for a booking that will not survive.** This is why the messenger sits in Phase 2 and not earlier. Confirming something that vanishes when the program stops would be worse than sending nothing.

**Nobody is watching the recorded failures.** The material says failures are kept and says nothing about who reads them, how often, or whether the guest is told afterwards. A record nobody reads keeps the promise on paper only.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | What it needs from it | What happens if that part is not there yet |
|---|---|---|
| [[P-01 - THE BOOKING MAKER\|P-01]] | A confirmed booking, handed over | Nothing is ever sent — the situation today. Fixed by [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-05]]. |
| The lasting store | Confidence the booking being confirmed will still exist tomorrow | Confirming bookings that vanish. The roadmap puts the store first for this reason. |
| Guest accounts | An email address | The promise cannot be kept at all. See the note above about the plan and this page disagreeing. |

**These need it:**

Nothing. No part waits on this one or reads anything it produces.

## How you know it is finished

- Make a booking and a confirmation email arrives.
- The email names the room, the dates, and the rate, matching what was actually recorded.
- Make a booking while sending is broken: the booking still stands, and the failure is written down where a person can find it.
- No confirmation is ever sent for a request that was refused.

## What the material does not say

| # | What is unclear | Why it matters here |
|---|---|---|
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-04]] | What does a guest account hold? | An account is the likeliest home for an email address, and without one this part cannot do the only thing it exists for. |

What the email should say is also unwritten. It is not raised as a question, because it is wording for the hotel to choose rather than a gap that blocks the work.
