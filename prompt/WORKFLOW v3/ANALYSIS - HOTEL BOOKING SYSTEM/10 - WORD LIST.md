# 10 - WORD LIST

[[00 - START HERE|Back to start]] · Previous: [[09 - TASK TRACKER]] · Next: [[11 - PARTS IN DETAIL]]

Words that could not be avoided, because they are real names of real things. Each one explained as it is used here.

| Word | What it means here | Where it comes up |
|---|---|---|
| `Requirements.md` | The written specification for the hotel booking system — version 0.4, nine rules, three decisions, three things ruled out | [[02 - DOCUMENT FINDINGS]], [[04 - COMBINED FINDINGS]] |
| `Handover Notes.md` | Notes taken at a meeting on 21 July 2026 between the duty manager, the head of housekeeping, and the developer | [[02 - DOCUMENT FINDINGS]], [[04 - COMBINED FINDINGS]] |
| `booking_service.py` | One of the two working files — everything to do with making, cancelling, and running bookings | [[03 - CODE FINDINGS]], [[04 - COMBINED FINDINGS]], [[05 - SYSTEM ARCHITECTURE]] |
| `rooms.py` | The other working file — which rooms exist, what state each is in, and what each type costs a night | [[03 - CODE FINDINGS]], [[04 - COMBINED FINDINGS]], [[05 - SYSTEM ARCHITECTURE]] |
| The booking maker | The part of `booking_service.py` that checks the four rules and records a stay that passes them. Named here for convenience — the working file has no name for it. | [[03 - CODE FINDINGS]], [[05 - SYSTEM ARCHITECTURE]], [[PARTS/P-01 - THE BOOKING MAKER\|P-01]] |
| The overlap check | The part that answers whether a room is free across every night of a stay | [[03 - CODE FINDINGS]], [[05 - SYSTEM ARCHITECTURE]] |
| The canceller | The part that decides whether a cancellation is allowed and then marks it | [[03 - CODE FINDINGS]], [[PARTS/P-02 - THE CANCELLER\|P-02]] |
| The level look-up | The part that answers what level a person is, by reading a list of four names written into the working file | [[03 - CODE FINDINGS]], [[05 - SYSTEM ARCHITECTURE]] |
| The arrivals and departures desk | The part that marks a guest as staying or finished and moves the room's state with them | [[03 - CODE FINDINGS]], [[PARTS/P-03 - THE ARRIVALS AND DEPARTURES DESK\|P-03]] |
| The room book | The part holding which rooms exist, what type each is, and what state each is in | [[03 - CODE FINDINGS]], [[PARTS/P-04 - THE ROOM BOOK\|P-04]] |
| The rate table | The part holding what a night costs, by room type | [[03 - CODE FINDINGS]], [[PARTS/P-06 - THE RATE TABLE\|P-06]] |
| The messenger | The part that would send confirmation emails. It exists with the right name and does nothing. | [[03 - CODE FINDINGS]], [[PARTS/P-05 - THE MESSENGER\|P-05]] |
| The lasting store | Somewhere bookings, room states, and rates could be kept that survives the program being switched off. Nobody has yet said what it should be. | [[05 - SYSTEM ARCHITECTURE]], [[07 - DEVELOPMENT ROADMAP]] |
| The permission check | The part that would answer what a person of a given level is allowed to do. Does not exist yet. | [[05 - SYSTEM ARCHITECTURE]], [[07 - DEVELOPMENT ROADMAP]] |
| The charge record | Somewhere to write down that a late cancellation owes one night, so the desk can collect it. Does not exist yet. | [[05 - SYSTEM ARCHITECTURE]], [[07 - DEVELOPMENT ROADMAP]] |
| The occupancy report | The part that would tell the manager how full the hotel has been. Does not exist yet. | [[05 - SYSTEM ARCHITECTURE]], [[07 - DEVELOPMENT ROADMAP]] |
| Out of service | One of the four states a room can be in, meaning it cannot be sold — a burst pipe, say. The word comes from the working file and the handover meeting, not from the specification. | [[03 - CODE FINDINGS]], [[PARTS/P-04 - THE ROOM BOOK\|P-04]] |
| Line numbers, as in "lines 27–35" | Files of working instructions are read in numbered lines, like a numbered list. A line number is simply where in the file to look. | Throughout [[03 - CODE FINDINGS]] |
| Mermaid | The way the pictures in this set are written down. Obsidian turns that writing into the drawings you see. | [[06 - DIAGRAMS]] |

## How this list was built

Any technical word left in these notes had to earn its place by being a real name — a file, a tool, or a part this analysis had to give a name to in order to talk about it. Everything else was replaced with everyday words.

Twelve of the entries above are names invented by this analysis rather than by the hotel or the working files. They are marked as such. Invented names are used only where something needed talking about and had no name of its own; if the hotel already calls these parts something else, their words should win.

If a word here is still unclear, that is a fault in this list, not in the reader.
