# 12 - SCREENS BY ROLE

[[00 - START HERE|Back to start]] · Previous: [[11 - PARTS IN DETAIL]]

## Read this first

**Neither supplied document describes a single screen.** `Requirements.md` says what four levels of person need to do; `Handover Notes.md` adds three more needs and one thing housekeeping must not see. Neither says what anybody looks at, what is on it, or how one thing leads to another. The working files have no screens in them at all — they are the parts underneath.

So every screen below is *drawn from* a stated need, and the last column names the need it came from. Nothing here is a finding. This note is a proposal built out of the rules, and it should be read that way and argued with.

Where a screen exists only because a rule has to be shown somewhere, the note says so. Where the material genuinely gives no basis for a screen a working hotel would obviously need, that absence is recorded rather than filled — see *What is missing* at the end.

Screens are numbered `S-`. The numbers are for referring to them, not an order to build them in — that is in [[07 - DEVELOPMENT ROADMAP]].

## The four levels

| Level | What they come here to do | Where it says so |
|---|---|---|
| Guest | Book a stay, see their own bookings, cancel one | `Requirements.md`, *Who uses it* |
| Receptionist | Take bookings by telephone, check guests in and out, see the day's arrivals | `Requirements.md`, *Who uses it* |
| Housekeeping | See which rooms need doing, mark a room ready | `Requirements.md`, *Who uses it* |
| Manager | Everything above, plus rates and reports | `Requirements.md`, *Who uses it* |

A person sees the screens for their level and no others. Nothing in the working files does this today — [[04 - COMBINED FINDINGS#Promised but missing|G-09]].

## Guest

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-01 | Sign in | A way to prove who you are, and a way to make an account | S-02 | Guest accounts | `Requirements.md`, *Decisions* — accounts are required |
| S-02 | Find a room | Arrival and departure dates, and the three room types with a nightly rate against each | S-03, or a message saying nothing is free | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]], [[PARTS/P-06 - THE RATE TABLE\|P-06]] | *Drawn from* `Requirements.md`, *Purpose* — booking online instead of telephoning |
| S-03 | Confirm the booking | The room, the dates, the nights, the total, and the cancellation terms | S-04 on success. On refusal, back to S-02 with the reason. | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]], [[PARTS/P-05 - THE MESSENGER\|P-05]] | *Drawn from* `Requirements.md`, *Rules* items 1, 2, and 3 — four refusals and one charge all have to be shown somewhere |
| S-04 | My bookings | Every booking this guest has, with its state | S-05 | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]] | `Requirements.md`, *Who uses it* — a guest sees their own bookings |
| S-05 | Cancel a booking | What cancelling costs, given how far ahead it is, and a way to go through with it | Back to S-04 | [[PARTS/P-02 - THE CANCELLER\|P-02]] | `Requirements.md`, *Rules* item 3 |

**S-03 and S-05 are where the disputed cut-off becomes visible to a guest.** Both must show a number, and three sources currently give two — [[00 - START HERE#Open questions|Q-01]]. Neither screen can be finished before that is settled.

S-05 also cannot be finished before [[00 - START HERE#Open questions|Q-03]]: if the charge is collected automatically, this screen needs a way to take money, and the specification rules out holding the means to do it.

## Receptionist

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-06 | Today | Who is arriving, who is leaving, and which rooms are ready for them | S-09 | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]], [[PARTS/P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]], [[PARTS/P-04 - THE ROOM BOOK\|P-04]] | `Requirements.md`, *Who uses it* — the desk sees the day's arrivals |
| S-07 | Take a booking | The same as S-02, with a place to put the guest's name and details | S-03 | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]] | `Requirements.md`, *Rules* item 8 — a receptionist books on a guest's behalf |
| S-08 | Find a booking | A way to search by guest name, room, or date | S-09, and to cancelling | [[PARTS/P-01 - THE BOOKING MAKER\|P-01]], [[PARTS/P-02 - THE CANCELLER\|P-02]] | *Drawn from* `Requirements.md`, *Rules* item 8 — cancelling any booking means first finding it |
| S-09 | Check in and check out | One booking, its room, that room's state, and a way to move the guest in or out | Back to S-06 | [[PARTS/P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]], [[PARTS/P-04 - THE ROOM BOOK\|P-04]] | `Requirements.md`, *Who uses it*, and *Rules* items 4 and 6 |

**S-09 is where two unenforced rules have to become visible.** A room that is not clean must refuse a check-in, `Requirements.md`, *Rules* item 6, and the hours must hold, *Rules* item 4. Both are missing from the working files — [[04 - COMBINED FINDINGS#Promised but missing|G-02]] and [[04 - COMBINED FINDINGS#Promised but missing|G-03]] — so this screen has nothing to show until [[08 - ROADMAP TRACKER|R-14]] and [[08 - ROADMAP TRACKER|R-07]] are built.

Whether S-08 can reach a booking after the guest has checked in is [[00 - START HERE#Open questions|Q-05]], which nobody in the handover meeting could answer.

## Housekeeping

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-10 | The cleaning list | Every room, its number, and its state. **No guest names anywhere.** | S-11 | [[PARTS/P-04 - THE ROOM BOOK\|P-04]] | `Requirements.md`, *Who uses it*, and `Handover Notes.md`, *What we agreed* |
| S-11 | One room | That room's number and state, a way to mark it done, and a way to take it out of service | Back to S-10 | [[PARTS/P-04 - THE ROOM BOOK\|P-04]] | `Handover Notes.md`, *What we agreed* — the burst pipe in 204 |

**S-10 carries the only rule in the whole system about what somebody may not *see*.** The head of housekeeping asked for it by name: the list shows room numbers and states and nothing about who is staying — `Handover Notes.md`. Every other permission in this material is about what somebody may *do*.

S-11 cannot be finished before [[00 - START HERE#Open questions|Q-07]]: the meeting asked for a way to take a room out of service and never said who puts one back, so half the buttons on this screen have no rule behind them.

**Two screens is all housekeeping gets, and that is the material's fault rather than a decision.** The documents give this level exactly two needs. Whether that is really the whole of their job is worth asking the head of housekeeping — it bears directly on [[00 - START HERE#Open questions|Q-09]].

## Manager

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-12 | Rates | The three room types with their nightly rate, and a way to change one | Back to itself | [[PARTS/P-06 - THE RATE TABLE\|P-06]] | `Requirements.md`, *Rules* item 5 — only a manager may change a rate |
| S-13 | How full we have been | Occupancy by week | Nowhere stated | The occupancy report | `Handover Notes.md`, *What we agreed* |
| S-14 | Staff and levels | Who works here and what level each is | Back to itself | Guest accounts, the permission check | *Drawn from* `booking_service.py`, lines 15–20 — [[04 - COMBINED FINDINGS#Built but never written down\|X-02]] |

**S-14 is the one screen on this page that no document asks for at all.** It is here because four staff names are currently typed into the working file, which means adding a receptionist today requires editing the program. Nobody wrote that down as a requirement; it is a consequence. Marked *drawn from* for that reason, and it is the first screen a reader should challenge.

S-13 cannot be drawn at all until [[00 - START HERE#Open questions|Q-08]] is answered — the meeting asked for the figure and, in its own words, nobody said where it should appear or how far back it should go.

The manager also reaches every screen above, per `Requirements.md`, *Who uses it*.

## Where the levels overlap

| Screen | Guest | Receptionist | Housekeeping | Manager |
|---|---|---|---|---|
| S-02 / S-07 Find a room and book | Own booking only | On a guest's behalf | No | Yes |
| S-04 / S-08 Find a booking | Own only | Any | **No — guest names** | Any |
| S-05 Cancel | Own, subject to the cut-off | Any, no charge | **No** | Any |
| S-09 Check in and out | No | Yes | No | Yes |
| S-10 The cleaning list | No | Read only | Yes | Yes |
| S-12 Rates | No | No | No | Yes |

Three cells in this table are the ones the working files get wrong today. Housekeeping can cancel anything — [[04 - COMBINED FINDINGS#Where they flatly contradict each other|K-02]]. Anybody can change a rate — [[04 - COMBINED FINDINGS#Promised but missing|G-04]]. And nothing at all keeps guest names off anything — [[04 - COMBINED FINDINGS#Promised but missing|G-09]].

The receptionist's read-only view of the cleaning list is *drawn from* `Requirements.md`, *Who uses it* — the desk cannot run arrivals without knowing which rooms are ready. No document grants it outright.

## What is missing

Screens a working hotel would obviously need, that this material gives no basis for. Recorded rather than invented.

| What is missing | Why it is not above |
|---|---|
| Anything a guest sees when they arrive without a booking | No document mentions walk-ins at all |
| Anywhere a no-show is recorded | There is no state for it — [[00 - START HERE#Open questions\|Q-06]] |
| Anywhere a confirmation email is resent | Nothing in the material contemplates sending one twice — [[00 - START HERE#Open questions\|Q-10]] |
| Anywhere a guest changes the dates of a booking | Neither document mentions changing a booking, only cancelling one |
| Anything at all for the owner, as distinct from the duty manager | `Requirements.md` names both and treats them as one level |

The fourth is the largest. A hotel that can only cancel and rebook, never amend, is a decision — and nobody appears to have made it.
