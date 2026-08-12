# rooms.py - rooms, room types, and nightly rates
# Riverside Hotel booking system, first attempt

ROOM_TYPES = ["Standard", "Double", "Suite"]

# 24 rooms in total. See Requirements.md, Decisions.
ROOMS = {}
for n in range(101, 111):
    ROOMS[n] = {"type": "Standard", "state": "clean"}
for n in range(201, 211):
    ROOMS[n] = {"type": "Double", "state": "clean"}
for n in range(301, 305):
    ROOMS[n] = {"type": "Suite", "state": "clean"}

# Nightly rates, per room type.
RATES = {
    "Standard": 95.00,
    "Double": 130.00,
    "Suite": 210.00,
}

# A room is always in one of these states.
ROOM_STATES = ["clean", "occupied", "dirty", "out_of_service"]


def rate_for(room_number):
    room = ROOMS[room_number]
    return RATES[room["type"]]


def set_rate(room_type, new_rate):
    # TODO: only a manager should be allowed to do this. Nothing checks.
    RATES[room_type] = new_rate


def mark_clean(room_number):
    ROOMS[room_number]["state"] = "clean"


def mark_dirty(room_number):
    ROOMS[room_number]["state"] = "dirty"


def mark_out_of_service(room_number):
    # Asked for in the handover meeting. Never written into Requirements.md.
    ROOMS[room_number]["state"] = "out_of_service"


def rooms_of_type(room_type):
    return [n for n, r in ROOMS.items() if r["type"] == room_type]
