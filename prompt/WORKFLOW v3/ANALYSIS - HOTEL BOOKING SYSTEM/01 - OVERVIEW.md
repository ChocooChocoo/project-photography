# 01 - OVERVIEW

[[00 - START HERE|Back to start]] · Next: [[02 - DOCUMENT FINDINGS]]

## What it is

A booking system for a twenty-four room hotel called the Riverside. Guests book a room on the website instead of telephoning. The front desk runs arrivals and departures from the same system, and the cleaning staff record which rooms are ready. Today all of this lives in a paper diary and a spreadsheet that disagree with each other most weeks — `Requirements.md`, under *Purpose*.

## Who uses it

Four levels of person, and the differences between them are the most demanding thing about this system.

| Who they are | What they come here to do |
|---|---|
| A guest | Book a stay, look at their own bookings, cancel one |
| A receptionist at the front desk | Take bookings over the telephone, check guests in and out, see who is arriving today |
| A member of the cleaning staff | See which rooms need doing, mark a room ready |
| The duty manager or the owner | Everything above, and set the nightly rates, and see how full the hotel has been |

Taken from `Requirements.md`, under *Who uses it*.

## What it does

**Booking a room.** Somebody asks for a room between two dates. The system refuses stays shorter than one night or longer than fourteen, refuses dates in the past, and refuses a room already taken for any night of the stay. Anything that passes is written down and confirmed.

**Cancelling.** A guest may cancel their own booking. Free of charge if they are far enough ahead, and with one night charged if they are not — how far ahead is disputed, and covered below.

**Arrivals and departures.** The front desk marks a guest as staying when they arrive and finished when they leave. A room goes to occupied on arrival and dirty on departure.

**Keeping the rooms straight.** Every room is one of clean, occupied, dirty, or out of service, and carries a type — Standard, Double, or Suite. Rates are set per type, not per room.

## What state it is in

Half-built, and the half that is built is the easy half.

The parts that decide whether a booking is allowed are done and sound. Rooms are never double-booked, the length limits are enforced, and a cancelled booking correctly frees its room again — that last one is right in the working files without any document ever asking for it.

Everything that separates one kind of person from another is missing. There are no guest accounts. In their place sits a list of four staff names written into the working file. Nothing checks that only a manager changes a rate. Cancelling has a rule for guests and nothing else, which means the cleaning staff can cancel any booking in the hotel — see [[04 - COMBINED FINDINGS#Where they flatly contradict each other|K-02]].

Beyond that: no confirmation email is ever sent, a room is never checked for cleanliness before somebody is put in it, and no booking survives the program being switched off.

## What it does not do

Stated in `Requirements.md`, under *Not doing*:

- No payment online. There is a card machine at the desk and it works.
- No group bookings of more than three rooms at once. Those go by telephone.
- No connection to the outside booking websites in this first version.

One of these has already caused an argument. The specification says the late-cancellation charge is taken from the guest, and also says no card details are held — `Handover Notes.md`, under *Left open*, records the developer pointing this out and nobody settling it. It is [[00 - START HERE#Open questions|Q-03]].

## Where the details are

For what each part is and how work passes between them, [[05 - SYSTEM ARCHITECTURE]] and the pictures in [[06 - DIAGRAMS]]. For the gap between what was written down and what was built, [[04 - COMBINED FINDINGS]] — the most useful file here. For what to build and in what order, [[07 - DEVELOPMENT ROADMAP]]. For what any single part actually does, [[11 - PARTS IN DETAIL]]. For what each level of person sees, [[12 - SCREENS BY ROLE]].
