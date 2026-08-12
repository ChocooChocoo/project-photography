# P-06 - THE RATE TABLE

[[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE|Back to start]] · [[ANALYSIS - HOTEL BOOKING SYSTEM/11 - PARTS IN DETAIL|All parts]] · Previous: [[P-05 - THE MESSENGER]]

**What it is for:** Holding what a night costs, by room type, and answering when asked.
**Where it sits:** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#The parts]]
**Built by:** [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights|R-10]]
**Status:** 🔵 Already there — found working in `rooms.py`, lines 16–20, 26–28, and 31–33. One roadmap item still changes it.

## Why it exists

Every booking needs a price, and the hotel decided that price is set per room type rather than per room — `Requirements.md`, under *Decisions*, 19 July 2026, recorded as [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised and built|M-03]]. Rooms of the same type are the same room to the hotel.

It is a small part with one rule attached to it, and that rule is the reason it has a page: only a manager may change a rate. It is the clearest single-sentence permission in the whole specification, and nothing enforces it.

## What it does

| # | What it does | Who asks for it | Where the need came from |
|---|---|---|---|
| 1 | Holds one nightly rate for each of the three room types | [[P-01 - THE BOOKING MAKER\|P-01]] | `Requirements.md`, *Decisions* — built at `rooms.py`, lines 16–20 |
| 2 | Answers what a given room costs a night, by looking up its type | [[P-01 - THE BOOKING MAKER\|P-01]], at the moment of booking | `rooms.py`, lines 26–28 |
| 3 | Changes a rate | Anybody who can reach it | `rooms.py`, lines 31–33 — [[ANALYSIS - HOTEL BOOKING SYSTEM/03 - CODE FINDINGS#What is unfinished, switched off, or unused\|U-04]] |

## Who may use it

| Who they are | What they may do here | What they may not do | Where the rule came from |
|---|---|---|---|
| The manager | Change any nightly rate | The material sets no limit | `Requirements.md`, *Rules* item 5 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-06]] |
| A guest, a receptionist, a member of the cleaning staff | Nothing | **Change any rate.** The rule names the manager and excludes everybody else by saying "only". | `Requirements.md`, *Rules* item 5 |

**None of this is enforced.** The piece that changes a rate asks nothing of anybody, and a note beside it at `rooms.py`, line 32 admits that a manager check is missing. Whoever wrote it knew.

This is [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing|G-04]], and it is the simplest of the permission gaps to close — one question in front of one piece, once [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights|R-08]] exists to answer it.

## The information it handles

| What it handles | What it is for | Where it is kept | Where this came from |
|---|---|---|---|
| Three nightly rates, one per room type | Pricing a booking | In the program's own memory | `rooms.py`, lines 16–20 |
| A room's type | Finding which of the three rates applies | In the room book | `rooms.py`, lines 27–28 |

Both are lost when the program stops. A manager who changes a rate on Monday finds the old rate back on Tuesday if anything restarted in between, and nothing tells them. Part of [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Built but never written down|X-01]], and easy to miss because only the booking list carries a note admitting the problem.

There is no record of what a rate used to be, and no record of who changed it. Neither document asks for either.

## How it behaves, step by step

**A booking needs a price**

1. [[P-01 - THE BOOKING MAKER\|P-01]] asks what a given room costs — `rooms.py`, line 26.
2. The room's type is looked up in the room book — line 27.
3. The rate for that type is handed back — line 28.
4. The booking maker copies that number onto the booking — `booking_service.py`, line 56.

Step 4 matters more than it looks. Because the number is copied rather than looked up again later, a rate change never alters a booking already made. Nothing in the material says this should be so, and it is almost certainly right — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Built but never written down|X-05]].

**Somebody changes a rate**

1. They give a room type and a new rate — `rooms.py`, line 31.
2. The rate is changed — line 33.

No question is asked at any point, about who they are or about the number they gave.

## The states things move through

Nothing here moves through named states. A rate is a number that is either the current one or has been replaced, and no history is kept.

## What it checks before it agrees

Nothing.

Two checks the material asks for or implies:

| # | What should be checked | What should happen when it fails | Where the rule came from |
|---|---|---|---|
| 1 | Is this person a manager? | The change is refused | `Requirements.md`, *Rules* item 5 — stated outright, and the missing check is admitted in a note at `rooms.py`, line 32 |
| 2 | Is the new rate a sensible number? | Not stated anywhere | *Drawn from* `rooms.py`, line 33 — nothing stops a rate of nought, or a negative one |

The second is marked *drawn from* and deliberately given no consequence, because no document says what a sensible rate is. Inventing a floor or a ceiling here would be exactly the kind of plausible-sounding rule that has no business in these notes.

## When something goes wrong

**Anybody can change any price.** This is the plainest rule in the specification and it is entirely unenforced. Reachability is the only thing protecting it today, and reachability is not a permission.

**A changed rate silently reverts.** Because rates live in memory, any restart puts them back to 95, 130, and 210. A manager would have no way of knowing, and bookings made in between would carry the wrong price permanently, because each booking keeps its own copy.

**No history, no attribution.** If a rate is wrong, there is nothing to say what it was before or who changed it. Neither document asks for this and both effects follow directly from how the part is built.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | What it needs from it | What happens if that part is not there yet |
|---|---|---|
| [[P-04 - THE ROOM BOOK\|P-04]] | A room's type | It is there, in the same file |
| The permission check | An answer to whether this person is a manager | It does not exist, so anybody may change any rate. Built as [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-08]]. |
| The lasting store | Somewhere a changed rate survives a restart | Rates revert silently |

**These need it:**

| Part | What it takes from this one |
|---|---|
| [[P-01 - THE BOOKING MAKER\|P-01]] | The nightly rate to copy onto a new booking |
| [[P-02 - THE CANCELLER\|P-02]] | Indirectly — the one-night charge is worked out from the rate copied onto the booking |

## How you know it is finished

- A manager can change the nightly rate for Suites.
- A receptionist, a guest, and a member of the cleaning staff all cannot.
- A rate changed today is still changed after the program has been stopped and started.
- A booking made before a rate change still shows the price the guest agreed to.

## What the material does not say

| # | What is unclear | Why it matters here |
|---|---|---|
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-02]] | Where should rates be kept once the program stops? | A rate change is meant to last. Today it lasts until the next restart and nobody is told. |

Only one question on this page, and that is the point of it: this is the one part where the material says clearly what should happen, and the working file simply did not do it.
