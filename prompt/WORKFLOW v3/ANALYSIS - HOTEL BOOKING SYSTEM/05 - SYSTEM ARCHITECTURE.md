# 05 - SYSTEM ARCHITECTURE

[[00 - START HERE|Back to start]] · Previous: [[04 - COMBINED FINDINGS]] · Next: [[06 - DIAGRAMS]]

## How to read this note

The first half describes the arrangement as it stands today. The second half describes the arrangement the material calls for but which does not exist yet. Every part is marked either 🔵 already there or ⭕ proposed, so the two are never confused. The same picture in diagram form is in [[06 - DIAGRAMS]].

## The arrangement today

### The parts

| Part | Status | What it is for | Who or what it serves |
|---|---|---|---|
| The booking maker | 🔵 Already there | Checking four rules and recording a stay that passes them | Whoever asks for a room |
| The overlap check | 🔵 Already there | Answering whether a room is free across every night of a stay | The booking maker |
| The list of bookings | 🔵 Already there | Holding every booking made since the program started | The booking maker, the overlap check, the canceller, the desk |
| The canceller | 🔵 Already there | Deciding whether a cancellation is allowed, then marking it cancelled | Guests, and everybody else without asking |
| The level look-up | 🔵 Already there | Saying what level a person is, from a list of four names | The canceller, and nothing else |
| The arrivals and departures desk | 🔵 Already there | Marking a guest as staying or finished, and moving the room with them | Receptionists, in principle — nothing checks |
| The room book | 🔵 Already there | Which rooms exist, what type each is, what state each is in | The rate table, the desk |
| The rate table | 🔵 Already there | What a night costs, by room type | The booking maker |
| The messenger | 🔵 Already there, but empty | Meant to email the guest their confirmation | Nothing — nobody calls it |

### How work passes between them

Everything about a booking begins at the booking maker. It is handed four things: who is asking, which room, the arrival date, and the departure date. It asks four questions in turn — is the stay long enough, is it short enough, is the arrival in the future, and is the room free. Only the last needs help, and for that it turns to the overlap check, which reads down the whole list of bookings looking for a stay that clashes on any night.

If all four are answered the right way, the booking maker asks the rate table what a night costs for that room's type, copies the answer onto the booking, writes it into the list, and hands the finished booking back. If any question fails it stops at once and says why, and nothing is written down.

The canceller works differently, and this is where the arrangement is at its weakest. It asks the level look-up what level the person is. If the answer is *guest*, it applies two tests — is this your booking, and is there still time. If the answer is anything else, it applies neither and cancels. The level look-up itself reads a list of four names written into the working file; anybody not on that list is a guest.

The arrivals and departures desk stands almost apart. It is handed a booking and moves it to staying or finished, moving the room to occupied or dirty at the same time. It asks nothing of anything, and nothing asks anything of it.

The room book holds the rooms and their states. Three parts can change a room's state — the desk, on arrival and departure, and two pieces of the room book itself that nothing ever calls. The overlap check never reads room state at all, which is why a room taken out of service is still sold.

The messenger is joined to nothing. No part of the arrangement reaches for it.

### Where things are kept

Everything is held in the program's own memory and nowhere else — bookings, rooms and their states, the rates, and the four staff names.

The list of bookings begins empty every time the program starts, and so does every room state and every rate. This is the most consequential fact in this note. Only the booking list carries a note admitting it, `booking_service.py`, line 11, which makes the same problem in the room book easy to miss — see [[04 - COMBINED FINDINGS#Built but never written down|X-01]].

### Where it touches the outside world

Nowhere. Neither file talks to another program or service, and there is no email arrangement of any kind. The confirmation email the specification promises would be this system's first connection to anything beyond itself.

### What holds the arrangement together

**Checks come before writing.** All four booking rules are tested before anything is recorded, so a half-made booking cannot exist. Worth keeping as the arrangement grows.

**One part knows the booking rules.** The booking maker holds all four. At this size that is right — a rule is found in one place rather than hunted for.

**Levels are asked about, but only in one place.** The level look-up exists and works. Exactly one part consults it, and the question it asks is *is this person a guest* rather than *is this person allowed*. That single phrasing is the cause of [[04 - COMBINED FINDINGS#Where they flatly contradict each other|K-02]], and it is the difference between a permission arrangement and a coincidence.

**Room state and room availability are strangers.** A room has a state, and whether a room can be booked is decided without ever looking at it. Nothing in the material explains this, and it defeats a rule the handover meeting specifically asked for — [[04 - COMBINED FINDINGS#Promised but missing|G-08]].

## The arrangement being proposed

### What changes and why

| Part | Status | What it is for | Why it is being added or changed |
|---|---|---|---|
| A lasting store | ⭕ Proposed | Keeping bookings, room states, and rates somewhere that survives the program stopping | Everything is lost on stopping today — [[04 - COMBINED FINDINGS#Built but never written down\|X-01]] |
| The permission check | ⭕ Proposed | Deciding what a person of a given level may do, in one place, for every part that needs it | Four levels are promised and one lopsided test exists — [[04 - COMBINED FINDINGS#Where they flatly contradict each other\|K-02]] |
| Guest accounts | ⭕ Proposed | Recognising a returning guest, and giving the permission check something solid to stand on | Required by `Requirements.md`, *Decisions* — [[04 - COMBINED FINDINGS#Promised but missing\|G-07]] |
| The charge record | ⭕ Proposed | Recording that a late cancellation owes one night, so the desk can collect it | Promised and discarded today — [[04 - COMBINED FINDINGS#Promised but missing\|G-05]] |
| The occupancy report | ⭕ Proposed | Telling the manager how full the hotel has been, by week | Asked for in `Handover Notes.md` — [[04 - COMBINED FINDINGS#Promised but missing\|G-10]] |

Three parts that already exist also change rather than being replaced. The messenger is filled in and the booking maker starts calling it. The overlap check begins reading room state, so an out-of-service room stops being sold. The arrivals and departures desk gains the two checks its own notes admit are missing — a clean room, and the hour of the day.

### How work would pass between them

The booking maker keeps its place at the centre and keeps its four checks. Three things change around it.

It writes into the lasting store instead of a list that vanishes, and the overlap check reads from there too, so a booking made last week still blocks a room today. The overlap check also starts asking the room book what state the room is in, and refuses anything not fit to sell.

Having written the booking down, the booking maker hands it to the messenger. The booking is not held up waiting for the email to leave; if the message fails, the booking still stands and the failure is recorded rather than thrown away.

Cancelling gains a proper step in front of it. Instead of asking *is this person a guest*, the canceller asks the permission check *may this person cancel this booking*, and the permission check answers from the level and the booking together. The same part answers the same kind of question for the rate table, which today lets anybody change a price, and for the cleaning list, which today shows everything to everybody.

Guest accounts sit at the front of all of it, turning a person into the level and the name the rest of the arrangement already expects. This is the least defined of the proposals, because the material says accounts are required and nothing else — [[00 - START HERE#Open questions|Q-04]].

### What it would take to get there

The lasting store comes first and everything waits behind it. Its arrival changes where four existing parts read and write, so doing it later means touching the same parts twice. It is also the one step that cannot be quietly undone once real bookings are sitting in it.

Correcting the existing rules — the cut-off, the confirmation email, the check-in and check-out hours — needs only the store, and can run alongside the work on levels.

The permission check must come before guest accounts, not after. It is the first thing in the whole arrangement that needs a real answer to *who is this*, and until it exists nothing is asking the question. It will need a temporary way of telling people apart in the meantime, which is what the four names in the file already are.

Making rooms unsellable when they are not fit needs the permission check first, because housekeeping has to be given the lever, and today housekeeping's rights are an accident.

### What is being given up

**Speed of building, for reliability.** A lasting store is more work than a list in memory, and it brings a second thing that can go wrong — the store being unreachable.

**Simplicity, for correctness.** Today cancelling is one step that cannot refuse anybody who is not a guest. Afterwards it is two steps and the second can say no — including to people who can cancel freely today.

**Somebody's convenience, certainly.** The cleaning staff can currently cancel any booking in the hotel. Nobody decided that, and closing it is the right thing to do, but it is a power being taken away from people who may have started using it.

None of these trades is discussed anywhere in the material. All three are stated here because they are real, and because the hotel may reasonably want a say in them.
