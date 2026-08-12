# 11 - PARTS IN DETAIL

[[00 - START HERE|Back to start]] · Previous: [[10 - WORD LIST]] · Next: [[12 - SCREENS BY ROLE]]

## What this covers

One page for each part of the system that has behaviour worth writing down — what it does, who may use it, what information it handles, what it checks before it agrees, and how it behaves when something goes wrong. The order these get built in is not here. That argument lives in [[07 - DEVELOPMENT ROADMAP]], and nothing on these pages repeats it.

Six of the fifteen parts named in [[05 - SYSTEM ARCHITECTURE]] have a page. The other nine are listed below with the reason they do not.

## The parts with a page

| # | The part | What it is for | Built by | Status | Page |
|---|---|---|---|---|---|
| P-01 | The booking maker | Checking the four rules a stay must pass, and recording the ones that pass | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-02]], [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-05]], [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-06]], [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-13]] | 🔵 Already there | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]] |
| P-02 | The canceller | Deciding whether a cancellation is allowed, and then marking it | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-03]], [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-09]], [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-11]], [[07 - DEVELOPMENT ROADMAP#Phase 6 — The Hotel Can See Itself\|R-18]] | 🔵 Already there | [[PARTS/P-02 - THE CANCELLER\|P-02]] |
| P-03 | The arrivals and departures desk | Marking a guest as staying or finished, and moving the room with them | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-07]], [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-14]] | 🔵 Already there | [[PARTS/P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] |
| P-04 | The room book | Holding which rooms exist, what type each is, and what state each is in | [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-13]], [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-15]] | 🔵 Already there | [[PARTS/P-04 - THE ROOM BOOK\|P-04]] |
| P-05 | The messenger | Telling the guest, by email, that their booking stands | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-04]], [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-05]] | ⭕ Not started | [[PARTS/P-05 - THE MESSENGER\|P-05]] |
| P-06 | The rate table | Holding what a night costs, by room type | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-10]] | 🔵 Already there | [[PARTS/P-06 - THE RATE TABLE\|P-06]] |

## Parts without a page

Named in [[05 - SYSTEM ARCHITECTURE]], with nothing behind them worth a page of its own.

| The part | Why not |
|---|---|
| The overlap check | Answers one question and decides nothing. The rule it serves — no two stays overlap on a room — is written down on [[PARTS/P-01 - THE BOOKING MAKER\|P-01]], where it is enforced. |
| The list of bookings | A place where things are kept, not a part that behaves. What it holds is on [[PARTS/P-01 - THE BOOKING MAKER\|P-01]]. |
| The level look-up | Reads four names typed into a file and hands back a word. It holds no rule of its own — what each level may do is on the page of the part that should be enforcing it. |
| The lasting store | Proposed, and nobody has yet said what it should be — [[00 - START HERE#Open questions\|Q-02]]. |
| The permission check | Proposed, and it holds no rules of its own. Every rule it would enforce belongs to the part that enforces it and is written on that part's page. It earns a page once it exists and turns out to do more than answer questions. |
| Guest accounts | The specification requires them and says nothing else — [[00 - START HERE#Open questions\|Q-04]]. A page would be invention from the first line down. |
| The charge record | Cannot be described until somebody says how the money is actually taken — [[00 - START HERE#Open questions\|Q-03]]. |
| The occupancy report | Nobody has said where the figure appears or how far back it goes — [[00 - START HERE#Open questions\|Q-08]]. |
| The confirmation sender as it stands | The empty shell at `booking_service.py`, lines 89–90. What it was meant to do is on [[PARTS/P-05 - THE MESSENGER\|P-05]]; there is nothing else to say about the shell. |

Five of the nine are absent for the same reason: an unanswered question. That is the honest state of this system, and filling those pages in would hide it.

## How these pages relate to the rest

Four files say four different things about the same parts, and none repeats another.

[[05 - SYSTEM ARCHITECTURE]] says where each part sits and what it hands to what. [[07 - DEVELOPMENT ROADMAP]] says when each gets built and why in that order. These pages say what each one actually does. [[12 - SCREENS BY ROLE]] says which of them each level of person can reach, and through what.

The test for which file a fact belongs in: if the answer would change when the build order changes, it belongs in the roadmap. If it stays the same no matter when the part gets built, it belongs here.

## What writing these turned up

Two things that none of the earlier files noticed.

**The plan cannot be built in the order it says.** [[PARTS/P-05 - THE MESSENGER|P-05]] sits in Phase 2, and it needs a guest's email address. Nothing in any of the four supplied files holds an email address, and the likeliest home for one is a guest account — which the roadmap puts in Phase 5. So [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-04]] cannot finish where the roadmap places it unless an address comes from somewhere else first. This is recorded rather than quietly corrected, because deciding where the address comes from is the hotel's to make, not this analysis's. The note against R-04 in [[08 - ROADMAP TRACKER]] says so.

**Nobody can resend a confirmation.** The specification requires an email on every confirmed booking and says nothing about sending one again — no rule for a receptionist doing it, none for a guest asking. A front desk that cannot resend a confirmation is the sort of thing that surfaces in the first week. Raised as [[00 - START HERE#Open questions|Q-10]].

Both came out of the same narrow question — *what information does this one part handle* — asked one part at a time. None of files `02` through `08` had cause to ask it.
