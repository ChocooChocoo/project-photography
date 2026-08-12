# booking_service.py - making, cancelling, and running bookings
# Riverside Hotel booking system, first attempt

from datetime import date
import rooms

MIN_NIGHTS = 1
MAX_NIGHTS = 14
CANCEL_HOURS = 24   # Requirements.md says 48. Handover notes say 24. Left at 24 for now.

BOOKINGS = []       # emptied every time the program starts

# Stand-in until real accounts exist.
# Anyone not listed here is treated as a guest.
STAFF = {
    "amina": "receptionist",
    "tomas": "receptionist",
    "greta": "housekeeping",
    "olu": "manager",
}


def role_of(username):
    return STAFF.get(username, "guest")


def is_free(room_number, arrive, depart):
    for b in BOOKINGS:
        if b["room"] != room_number:
            continue
        if b["state"] == "cancelled":
            continue
        if arrive < b["depart"] and depart > b["arrive"]:
            return False
    return True


def create_booking(username, room_number, arrive, depart):
    nights = (depart - arrive).days
    if nights < MIN_NIGHTS:
        return "too short"
    if nights > MAX_NIGHTS:
        return "too long"
    if arrive < date.today():
        return "past date"
    if not is_free(room_number, arrive, depart):
        return "not free"

    booking = {
        "id": len(BOOKINGS) + 1,
        "guest": username,
        "room": room_number,
        "arrive": arrive,
        "depart": depart,
        "nights": nights,
        "rate": rooms.rate_for(room_number),
        "state": "confirmed",
    }
    BOOKINGS.append(booking)
    return booking


def cancel_booking(username, booking):
    days_left = (booking["arrive"] - date.today()).days
    if role_of(username) == "guest":
        if booking["guest"] != username:
            return "not yours"
        if days_left * 24 < CANCEL_HOURS:
            fee = booking["rate"]
            # fee is worked out and then nothing happens to it
    booking["state"] = "cancelled"
    return booking


def check_in(booking):
    # TODO: refuse if the room has not been cleaned. Not done.
    # TODO: refuse before 2pm. Not done.
    booking["state"] = "staying"
    rooms.ROOMS[booking["room"]]["state"] = "occupied"
    return booking


def check_out(booking):
    booking["state"] = "finished"
    rooms.mark_dirty(booking["room"])
    return booking


def notify_guest(booking):
    pass
