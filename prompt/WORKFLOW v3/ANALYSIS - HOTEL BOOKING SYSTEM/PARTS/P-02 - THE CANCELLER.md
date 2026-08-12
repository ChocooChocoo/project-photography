# P-02 - THE CANCELLER

[[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE|Back to start]] · [[ANALYSIS - HOTEL BOOKING SYSTEM/11 - PARTS IN DETAIL|All parts]] · Previous: [[P-01 - THE BOOKING MAKER]] · Next: [[P-03 - THE ARRIVALS AND DEPARTURES DESK]]

**What it is for:** Deciding whether a cancellation is allowed, and then marking the booking cancelled.
**Where it sits:** [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#The parts]]
**Built by:** [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 2 — The Rules Match What Was Promised|R-03]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights|R-09]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights|R-11]], [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 6 — The Hotel Can See Itself|R-18]]
**Status:** 🔵 Already there — found working in `booking_service.py`, lines 63–72. Four roadmap items still change it, and it is the most wrong part in the system.

## Why it exists

Guests change their minds, and the hotel would rather have the room back than an empty bed. `Requirements.md`, *Rules* item 3 sets the terms — free until a cut-off, one night charged inside it. *Rules* item 8 gives the front desk a wider power, to cancel anything without charging.

This part is where both of those rules should live. One of them is half here and the other is not here at all.

## What it does

| # | What it does | Who asks for it | Where the need came from |
|---|---|---|---|
| 1 | Works out how many days remain before arrival | Whoever is cancelling | `booking_service.py`, line 64 |
| 2 | For a guest, refuses a booking that is not theirs | A guest | `Requirements.md`, *Rules* item 3 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-04]] |
| 3 | For a guest inside the cut-off, works out one night's charge — and then discards it | A guest | `booking_service.py`, lines 68–70 — [[ANALYSIS - HOTEL BOOKING SYSTEM/03 - CODE FINDINGS#What is unfinished, switched off, or unused\|U-02]] |
| 4 | Marks the booking cancelled | Anybody who reaches it | `booking_service.py`, line 71 |

## Who may use it

This is the point of the page, and the table is written twice on purpose — once for what the documents say, once for what the working file does. They do not match.

**What the documents say:**

| Who they are | What they may do here | What they may not do | Where the rule came from |
|---|---|---|---|
| A guest | Cancel their own booking, free of charge outside the cut-off | Cancel anybody else's booking. Cancel inside the cut-off without paying one night. | `Requirements.md`, *Rules* item 3 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-04]] |
| A receptionist | Cancel any booking, with no charge to the guest | The material sets no limit | `Requirements.md`, *Rules* item 8 — [[ANALYSIS - HOTEL BOOKING SYSTEM/02 - DOCUMENT FINDINGS#What the documents say the system must do\|D-09]] |
| The manager | Nothing is written down. Managers are described as doing everything a receptionist does and more, so the same power is the plainest reading. | — | *Drawn from* `Requirements.md`, *Who uses it* |
| The cleaning staff | **Nothing.** No document gives them any power over a booking. | Cancel anything | *Drawn from* the absence of any such rule in either document |

**What the working file actually does:**

| Who they are | What they can do today |
|---|---|
| A guest | Cancel their own booking, at any time, charge or no charge. The cut-off stops nothing. |
| A receptionist | Cancel anything. Correct by accident. |
| The manager | Cancel anything. Probably correct, and by the same accident. |
| The cleaning staff | **Cancel any booking in the hotel.** |

The cause is one line. `booking_service.py`, line 65 asks *is this person a guest*, and everybody who is not falls straight past both tests to line 71. Nobody decided the cleaning staff should have this. It is recorded as [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Where they flatly contradict each other|K-02]], and what the cleaning staff *should* be able to do is [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-09]].

## The information it handles

| What it handles | What it is for | Where it is kept | Where this came from |
|---|---|---|---|
| Who is asking | Deciding which rules apply | Nowhere — a name handed in, checked against four names in the file | `booking_service.py`, lines 15–20 and 63 |
| Whose booking it is | The ownership test, for guests only | With the booking, as a name | `booking_service.py`, lines 51 and 66 |
| The arrival date | Measuring the days remaining | With the booking | `booking_service.py`, lines 53 and 64 |
| The cut-off | The line between free and charged | Fixed in the working file at 24 hours | `booking_service.py`, line 9 — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-01]] |
| The nightly rate | Working out the one-night charge | Copied onto the booking when it was made | `booking_service.py`, lines 56 and 69 |
| The charge itself | Nothing. It is worked out and thrown away. | Nowhere | `booking_service.py`, lines 69–70 |

The last row is the whole of [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing|G-05]]. A number the hotel is owed is calculated correctly and then lost, every time.

## How it behaves, step by step

**Somebody asks to cancel a booking**

1. They ask, naming themselves and the booking — `booking_service.py`, line 63.
2. The days remaining before arrival are worked out — line 64.
3. The level look-up is asked what level this person is — line 65.
4. **If the answer is anything but guest, jump to step 7.**
5. If the booking belongs to somebody else, refuse — lines 66 to 67.
6. If the cut-off has passed, work out one night's charge and discard it — lines 68 to 70.
7. Mark the booking cancelled — line 71.
8. Hand the booking back — line 72.

Step 4 is the fault. Step 6 is the second one: both paths out of it lead to step 7, so being inside the cut-off changes nothing a guest would notice. The picture of this is [[ANALYSIS - HOTEL BOOKING SYSTEM/06 - DIAGRAMS#3. How a job gets done — cancelling a booking]].

Nothing checks what state the booking is in. A booking already cancelled can be cancelled again, and a guest who has already arrived can cancel the stay they are currently on.

## The states things move through

| From | To | What causes the move | Who can cause it | Can it go back? |
|---|---|---|---|---|
| Confirmed | Cancelled | This part marks it | Anybody who is not a guest, and any guest for their own booking | No — nothing in either file reverses it |
| Staying | Cancelled | This part marks it, because nothing stops it | The same people | No |

The second row should probably not exist. Nothing in either document contemplates cancelling a stay that has already begun, and nothing in the working file prevents it. Whether the desk may change a booking after check-in is exactly the matter left unsettled in `Handover Notes.md` — [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-05]].

## What it checks before it agrees

| # | What is checked | What happens when the check fails | Where the rule came from |
|---|---|---|---|
| 1 | Is the person asking a guest? | Nothing fails — a no skips every remaining check | *Drawn from* `booking_service.py`, line 65. No document asks the question this way round. |
| 2 | Is this the guest's own booking? | Refused with a short phrase — line 67 | `Requirements.md`, *Rules* item 3 |
| 3 | Is the cut-off still ahead? | **Nothing.** A charge is worked out and discarded, and the cancellation goes through. | `Requirements.md`, *Rules* item 3 — [[ANALYSIS - HOTEL BOOKING SYSTEM/04 - COMBINED FINDINGS#Promised but missing\|G-05]] |

Three checks that the documents would recognise are absent altogether: whether this person is allowed to cancel at all, whether the booking is in a state that can be cancelled, and whether a charge has been accepted before a late cancellation is allowed to complete. None has been written above, because none is in the material as a rule — they are what would be needed to keep the rules that *are*.

## When something goes wrong

**The cut-off is the wrong number, and nobody knows.** `Requirements.md` says 48 hours, the handover meeting agreed 24 and asked for the specification to be updated, and the working file quietly went with 24. A guest reading the website terms and a guest cancelling would get different answers. This is [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-01]], and it is the single cheapest thing on this page to put right.

**The charge cannot be collected as things stand.** `Requirements.md` promises to charge a late canceller and separately rules out holding card details. `Handover Notes.md` records the developer saying so and nobody answering. Until [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions|Q-03]] is settled, recording the charge is the most that can honestly be built.

**Closing the hole takes something away from people.** The cleaning staff can cancel bookings today. Whatever anybody intended, some of them may be using it. [[ANALYSIS - HOTEL BOOKING SYSTEM/05 - SYSTEM ARCHITECTURE#What is being given up]] says this plainly rather than letting it arrive as a surprise.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | What it needs from it | What happens if that part is not there yet |
|---|---|---|
| The level look-up | An answer to what level this person is | It is there, and it reads four names typed into the file. Anybody not listed is treated as a guest. |
| The permission check | An answer to whether this person may cancel this booking | It does not exist. Its absence is the whole fault on this page. Built as [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 3 — Every Level Has The Right Rights\|R-08]]. |
| [[P-01 - THE BOOKING MAKER\|P-01]] | The booking, its owner, its arrival date, and its rate | All four are written down when the booking is made |
| The charge record | Somewhere to put what a late cancellation owes | It does not exist, so the charge is discarded. Built as [[ANALYSIS - HOTEL BOOKING SYSTEM/07 - DEVELOPMENT ROADMAP#Phase 6 — The Hotel Can See Itself\|R-18]]. |

**These need it:**

| Part | What it takes from this one |
|---|---|
| The overlap check | Bookings marked cancelled, which it skips — so a cancelled booking frees its room |

## How you know it is finished

- A guest can cancel their own booking well ahead of arrival, free of charge.
- The same guest cancelling inside the cut-off leaves a record of one night owed that the desk can find.
- A guest cannot cancel somebody else's booking.
- A receptionist can cancel any booking, and no charge is recorded against the guest.
- **A member of the cleaning staff cannot cancel any booking at all.**
- The cut-off is one number, it matches the website, and it appears in one place.

## What the material does not say

| # | What is unclear | Why it matters here |
|---|---|---|
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-01]] | Is the cut-off 24 hours or 48? | It is the central number of this part, and three sources give two answers. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-03]] | How is the late charge actually taken? | The charge is the reason the cut-off exists, and the documents promise it while ruling out the means to collect it. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-05]] | May a booking be changed after check-in? | Today this part will cancel a stay in progress and nobody has said whether it should. |
| [[ANALYSIS - HOTEL BOOKING SYSTEM/00 - START HERE#Open questions\|Q-09]] | What may the cleaning staff do? | They can cancel anything. Nothing in either document even mentions the possibility. |

Four questions on one part, and it is fifteen lines long. That is the measure of how much of this system's difficulty is about people rather than rooms.
