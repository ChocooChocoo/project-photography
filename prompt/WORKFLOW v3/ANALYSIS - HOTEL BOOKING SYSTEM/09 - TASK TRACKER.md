# 09 - TASK TRACKER

[[00 - START HERE|Back to start]] · Previous: [[08 - ROADMAP TRACKER]] · Next: [[10 - WORD LIST]]

**Last checked:** 8 August 2026

Every task here names where it came from. A task with no traceable origin is not on this list.

## Where everything stands

| Status | How many |
|---|---|
| ✅ Finished | 0 |
| 🟨 Being worked on | 0 |
| ⭕ Not started | 17 |
| ❌ Blocked | 11 |
| 🔵 Already there | 0 |
| ⬜ Dropped | 0 |
| ❓ Unclear | 2 |
| **Total** | **30** |

## The tasks

| # | The task | Where it came from | What it refers to | Serves | Status |
|---|---|---|---|---|---|
| T-01 | Decide where bookings, room states, and rates should be kept | *Drawn from* `booking_service.py` line 11 and `rooms.py` lines 7–20 | Nothing outlives the program running, and no document ever mentions keeping anything anywhere | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-01]] | ❌ |
| T-02 | Build the lasting store and move the shape of a booking into it | *Drawn from* `booking_service.py`, lines 49–58 | The seven things a booking currently holds, which would need somewhere to live | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-01]] | ❌ |
| T-03 | Move the room list and room states into the lasting store | *Drawn from* `rooms.py`, lines 7–13 and 23 | Rooms are rebuilt from scratch every time the program starts, all of them marked clean | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-01]] | ❌ |
| T-04 | Point the overlap check at the lasting store | `booking_service.py`, lines 27–35 | The check that reads down the whole booking list looking for a clash | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-02]] | ⭕ |
| T-05 | Point the booking maker at the lasting store | `booking_service.py`, lines 49–60 | The step that writes a finished booking down | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-02]] | ⭕ |
| T-06 | Point the canceller and the arrivals desk at the lasting store | `booking_service.py`, lines 71 and 78–85 | The three places a booking's state is changed after it is made | [[07 - DEVELOPMENT ROADMAP#Phase 1 — Bookings And Rooms Survive\|R-02]] | ⭕ |
| T-07 | Ask the hotel whether the cancellation cut-off is 24 hours or 48 | `Requirements.md`, *Rules* item 3, and `Handover Notes.md`, *What we agreed* | The two documents give different numbers, and the newer document carries the older one | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-03]] | ❌ |
| T-08 | Put the agreed cut-off in one place and update the specification to match | `booking_service.py`, line 9 | The number sitting in the working file with a note beside it recording the disagreement | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-03]] | ❌ |
| T-09 | Build the part that actually sends an email | `Requirements.md`, *Rules* item 7 | The promise that every confirmed booking is emailed to the guest | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-04]] | ⭕ |
| T-10 | Decide what the confirmation email says | *Drawn from* `Requirements.md`, *Rules* item 7 | The rule requires that one is sent and says nothing about its contents | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-04]] | ⭕ |
| T-11 | Have the booking maker hand a confirmed booking to the messenger | `booking_service.py`, lines 59–60 and 89–90 | The empty part nothing calls, and the point in the journey where it should be called | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-05]] | ⭕ |
| T-12 | Rewrite the five refusals as sentences a guest would understand | `booking_service.py`, lines 41, 43, 45, 47, and 67 | Five short phrases meant for whoever built the program | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-06]] | ⭕ |
| T-13 | Refuse a check-in before 2pm and a check-out after 11am | `Requirements.md`, *Rules* item 4, and `booking_service.py`, lines 76–77 | A promise the working file itself admits, in a note, that it does not keep | [[07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised\|R-07]] | ⭕ |
| T-14 | Write down what each of the four levels may and may not do | `Requirements.md`, *Who uses it* and *Rules* items 5 and 8 | Four levels are named, and only two of them have any rule attached | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-08]] | ⭕ |
| T-15 | Build the permission check as one part everything else can ask | *Drawn from* `booking_service.py`, line 65 and `rooms.py`, lines 31–33 | Two places that need to know what somebody may do, neither of which asks properly | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-08]] | ⭕ |
| T-16 | Replace the is-this-a-guest test in the canceller with the permission check | `booking_service.py`, lines 65 and 71 | The test that lets everybody who is not a guest cancel anything at all | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-09]] | ⭕ |
| T-17 | Put the permission check in front of changing a rate | `rooms.py`, lines 31–33 | The piece with a note admitting that a manager check is missing | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-10]] | ⭕ |
| T-18 | Ask the hotel what the cleaning staff should and should not be able to do | *Drawn from* `booking_service.py`, line 65 and `Handover Notes.md`, *What we agreed* | The cleaning staff can cancel any booking, and the documents only ever say what they need added | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-11]] | ❌ |
| T-19 | Keep guest names off everything the cleaning staff can reach | `Handover Notes.md`, *What we agreed* | The head of housekeeping asking for this specifically | [[07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-12]] | ⭕ |
| T-20 | Have the overlap check ask the room book what state a room is in | `booking_service.py`, lines 27–35, and `rooms.py`, line 23 | The check that never looks at room state, and the four states it never looks at | [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-13]] | ⭕ |
| T-21 | Refuse a check-in into a room that is not marked clean | `Requirements.md`, *Rules* item 6, and `booking_service.py`, line 76 | A rule the working file admits, in a note, that it skips | [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-14]] | ⭕ |
| T-22 | Give the cleaning staff a way to reach the two pieces that already exist | `rooms.py`, lines 36–37 and 44–46 | Marking a room clean and taking one out of service, both built and both unreachable | [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-15]] | ❌ |
| T-23 | Build a way to put a room back into service, and decide who may | `Handover Notes.md`, *What we agreed* | The meeting asked for rooms to come out of service and never mentioned putting one back | [[07 - DEVELOPMENT ROADMAP#Phase 4 — Rooms Are Only Sold When They Are Fit\|R-15]] | ❌ |
| T-24 | Ask the hotel what a guest account holds and what signing in should check | `Requirements.md`, *Decisions* | Accounts are required, and that is the whole of what is said about them | [[07 - DEVELOPMENT ROADMAP#Phase 5 — Guests Are Recognised\|R-16]] | ❌ |
| T-25 | Replace the four staff names in the working file with real accounts | `booking_service.py`, lines 15–20 | The list that stands in for accounts, and treats anybody not on it as a guest | [[07 - DEVELOPMENT ROADMAP#Phase 5 — Guests Are Recognised\|R-17]] | ❓ |
| T-26 | Record what a late cancellation owes, somewhere the desk can find it | `booking_service.py`, lines 68–70 | The charge that is worked out and then discarded | [[07 - DEVELOPMENT ROADMAP#Phase 6 — The Hotel Can See Itself\|R-18]] | ❌ |
| T-27 | Count how full the hotel was, by week | `Handover Notes.md`, *What we agreed* | The manager asking for an occupancy figure | [[07 - DEVELOPMENT ROADMAP#Phase 6 — The Hotel Can See Itself\|R-19]] | ❌ |

## Tasks that serve no roadmap item

Work that came out of the material but does not sit under any phase. Kept here so it is not lost.

| # | The task | Where it came from | Why it is not on the roadmap | Status |
|---|---|---|---|---|
| T-28 | Settle whether a receptionist may change a booking after the guest has checked in | `Handover Notes.md`, *Left open* | Nobody in the meeting knew, so there is nothing to plan. Raised as [[00 - START HERE#Open questions\|Q-05]]. | ❓ |
| T-29 | Settle what happens to a booking when the guest never arrives | `Handover Notes.md`, *Left open* | The desk currently leaves it in the paper diary. No state exists for it and nobody has said one should. Raised as [[00 - START HERE#Open questions\|Q-06]]. | ⭕ |
| T-30 | Update `Requirements.md` so it stops contradicting the handover meeting | `Handover Notes.md`, *What we agreed* | Not building work — it is a document that needs correcting, and the meeting already said somebody should do it. | ⭕ |

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
