# 08 - ROADMAP TRACKER

[[00 - START HERE|Back to start]] · Previous: [[07 - DEVELOPMENT ROADMAP]] · Next: [[09 - TASK TRACKER]]

**Last checked:** 8 August 2026

## Where everything stands

| Status | How many |
|---|---|
| ✅ Finished | 0 |
| 🟨 Being worked on | 0 |
| ⭕ Not started | 11 |
| ❌ Blocked | 7 |
| 🔵 Already there | 0 |
| ⬜ Dropped | 0 |
| ❓ Unclear | 1 |
| **Total** | **19** |

Nothing has been started, and seven of the nineteen items cannot be started at all until somebody answers a question. Those seven questions are all in [[00 - START HERE#Open questions]], and answering them is the single most useful thing anyone could do with this plan today. Not one item is blocked by other building work.

## Phase 1 — Bookings And Rooms Survive

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-01 | A lasting place to keep bookings, room states, and rates | ❌ Blocked | Nobody has said where any of it should be kept — [[00 - START HERE#Open questions\|Q-02]]. |
| R-02 | Point the booking maker, the overlap check, the canceller, and the arrivals desk at that lasting place | ⭕ Not started | Waits on R-01. |

## Phase 2 — The Rules Match What Was Promised

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-03 | Settle the cancellation cut-off and put one agreed number in one place | ❌ Blocked | Two documents disagree and the working file has already chosen — [[00 - START HERE#Open questions\|Q-01]]. A one-number change once settled. |
| R-04 | Fill in the messenger, so confirmation emails actually go out | ⭕ Not started | Cannot finish here as the plan stands: it needs a guest's email address, and nothing holds one until [[07 - DEVELOPMENT ROADMAP#Phase 5 — Guests Are Recognised\|R-16]]. See [[11 - PARTS IN DETAIL#What writing these turned up]]. |
| R-05 | Have the booking maker ask the messenger to send | ⭕ Not started | Waits on R-04. |
| R-06 | Turn the five refusals into wording a guest would understand | ⭕ Not started | |
| R-07 | Enforce check-in from 2pm and check-out by 11am | ⭕ Not started | |

## Phase 3 — Every Level Has The Right Rights

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-08 | Build the permission check | ⭕ Not started | Will lean on the four names in `booking_service.py`, lines 15–20 until Phase 5 replaces them. |
| R-09 | Put the permission check in front of cancelling | ⭕ Not started | Waits on R-08. Closes [[04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-02]]. |
| R-10 | Put the permission check in front of changing a rate | ⭕ Not started | Waits on R-08. |
| R-11 | Decide what the cleaning staff may do, and set it | ❌ Blocked | Nobody has said what they should be able to do — [[00 - START HERE#Open questions\|Q-09]]. |
| R-12 | Keep guest names off anything the cleaning staff see | ⭕ Not started | Waits on R-08. |

## Phase 4 — Rooms Are Only Sold When They Are Fit

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-13 | Have the overlap check read a room's state and refuse anything not fit to sell | ⭕ Not started | |
| R-14 | Refuse a check-in into a room that is not marked clean | ⭕ Not started | |
| R-15 | Give the cleaning staff a way to mark a room clean, take one out of service, and put one back | ❌ Blocked | Nobody has said who puts a room back, or what happens to a booking already on it — [[00 - START HERE#Open questions\|Q-07]]. |

## Phase 5 — Guests Are Recognised

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-16 | Guest accounts | ❌ Blocked | The specification says accounts are required and nothing else — [[00 - START HERE#Open questions\|Q-04]]. |
| R-17 | Replace the four staff names written into the working file with real accounts | ❓ Unclear | Whether this is one job or several depends entirely on the answer to [[00 - START HERE#Open questions\|Q-04]]. |

## Phase 6 — The Hotel Can See Itself

| # | What gets built | Status | Notes |
|---|---|---|---|
| R-18 | Record what a late cancellation owes, so the desk can collect it | ❌ Blocked | The specification promises a charge and rules out holding the means to take it — [[00 - START HERE#Open questions\|Q-03]]. |
| R-19 | Show the manager how full the hotel has been, by week | ❌ Blocked | Nobody said where the figure should appear or how far back — [[00 - START HERE#Open questions\|Q-08]]. |

## What is blocked

Pulled out of the tables above so nothing hides in a long list.

| # | What gets built | What is stopping it | What would clear it |
|---|---|---|---|
| R-01 | A lasting place to keep bookings, room states, and rates | Nobody has decided where any of it should be kept | The hotel says where, and whoever builds it agrees it will work |
| R-03 | Settle the cancellation cut-off | The specification says 48 hours, the handover meeting said 24, and the working file chose 24 without telling anybody | The hotel confirms one number, and the specification is updated to match |
| R-11 | Decide what the cleaning staff may do | The documents describe what the cleaning staff need added, never what they should be prevented from doing | The hotel says what the cleaning staff should and should not be able to reach |
| R-15 | Give the cleaning staff a way to take a room out of service and put one back | Nobody said who puts a room back, or what becomes of a booking already on that room | The hotel answers both |
| R-16 | Guest accounts | Nothing says what an account holds or what signing in should check | The hotel says what recognising a returning guest is actually for |
| R-18 | Record what a late cancellation owes | The specification promises to charge a late canceller and separately rules out holding card details. Both cannot be true. | The hotel decides whether the charge is collected at the desk, or whether card details are held after all |
| R-19 | The occupancy figure | The handover notes say in their own words that nobody said where it should appear or how far back it should go | The manager says both |

Seven blocked items, seven unanswered questions, one for one.

## Promises already kept

Not part of the plan and not counted above. These are things the documents asked for that the working files already do, recorded so nobody sets out to build them twice.

| What was promised | Status | Where it is |
|---|---|---|
| No two stays overlap on the same room, on any night | 🔵 Already there | `booking_service.py`, lines 27–35 and 46 — [[04 - COMBINED FINDINGS#Promised and built\|M-01]] |
| A stay is between one and fourteen nights | 🔵 Already there | `booking_service.py`, lines 7–8 and 40–43 — [[04 - COMBINED FINDINGS#Promised and built\|M-02]] |
| Rates are set per room type, not per room | 🔵 Already there | `rooms.py`, lines 16–20 and 26–28 — [[04 - COMBINED FINDINGS#Promised and built\|M-03]] |
| Twenty-four rooms, ten Standard, ten Double, four Suite | 🔵 Already there | `rooms.py`, lines 8–13 — [[04 - COMBINED FINDINGS#Promised and built\|M-04]] |
| A booking for a date in the past is refused | 🔵 Already there | `booking_service.py`, lines 44–45. Not asked for by either document — a sensible rule somebody added. |
| A cancelled booking frees its room again | 🔵 Already there | `booking_service.py`, lines 31–32 — [[04 - COMBINED FINDINGS#Built but never written down\|X-04]] |

## Deliberately not built

Not part of the plan and not counted above. Kept on the record so nobody proposes them again without knowing they were already turned down.

| What | Status | Why |
|---|---|---|
| Taking payment online | ⬜ Dropped | The hotel has a card machine at the desk and takes payment there — `Requirements.md`, *Not doing* |
| Group bookings of more than three rooms at once | ⬜ Dropped | Handled by telephone, as they are today — `Requirements.md`, *Not doing* |
| Connecting to the outside booking websites | ⬜ Dropped | Wanted later, ruled out of the first version — `Requirements.md`, *Not doing* |

## Status legend

| Emoji | Means |
|---|---|
| ✅ | Finished — built, checked, working |
| 🟨 | Being worked on right now |
| ⭕ | Not started — waiting its turn |
| ❌ | Blocked — something is stopping it |
| 🔵 | Already there — found in the supplied material, built before this plan |
| ⬜ | Dropped — decided against, kept for the record |
| ❓ | Unclear — the material does not say |
