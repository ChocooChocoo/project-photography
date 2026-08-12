# Riverside Hotel — Booking System Requirements

Version 0.4 · 3 August 2026 · written by the duty manager

## Purpose

Guests should be able to book a room on our website instead of telephoning. The front desk should be able to run arrivals and departures from the same system. Housekeeping should be able to record which rooms are ready. Right now all of this lives in a paper diary and a spreadsheet, and the two disagree most weeks.

## Who uses it

| Level | Who they are | What they come here to do |
|---|---|---|
| Guest | Anybody booking a room | Book a stay, see their own bookings, cancel one |
| Receptionist | Front desk staff | Take bookings by telephone, check guests in and out, see the day's arrivals |
| Housekeeping | Cleaning staff | See which rooms need doing, mark a room ready |
| Manager | The duty manager and the owner | Everything above, plus rates and reports |

## Rules

1. A room may not be double-booked. No two stays may overlap on the same room for any night.
2. A stay must be at least one night and no more than fourteen nights.
3. A guest may cancel free of charge up to 48 hours before the day they arrive. Inside 48 hours, one night at the room's rate is charged.
4. Check-in is from 2pm. Check-out is by 11am.
5. Only a manager may change a nightly rate.
6. A room must be marked clean before a guest may be checked into it.
7. Every confirmed booking is emailed to the guest.
8. A receptionist may take a booking on a guest's behalf, and may cancel any booking with no charge to the guest.
9. Payment is taken at the desk on arrival.

## Decisions

| Decision | Why | When |
|---|---|---|
| Guest accounts are required | So a returning guest is recognised and does not retype everything | 12 July 2026 |
| Rates are set per room type per night, not per room | Rooms of the same type are the same room to us | 19 July 2026 |
| Twenty-four rooms — ten Standard, ten Double, four Suite | This is the hotel as it stands | 19 July 2026 |

## Not doing

- **No online payment.** We have a card machine at the desk and it works.
- **No group bookings of more than three rooms in one go.** Anything larger goes through the telephone, as it does today.
- **No connection to the booking websites** in the first version. We know we will want it. Not yet.
