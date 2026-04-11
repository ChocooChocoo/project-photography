**Objective: Booking Assignment Validation for Studio Photographers**

1. On the owner side, when assigning a photographer to a booking, the system must validate whether the selected photographer is available before the assignment can be completed.

2. The assignment must be blocked if any of the following conditions are true:

* The photographer has an active leave record in their attendance.
* The photographer already has an ongoing booking.
* The photographer already has a reserved assignment for another booking.

**Expected Behavior:**
The system should only allow the owner to assign a photographer if the photographer is fully available and does not have any leave conflict, ongoing booking conflict, or reserved assignment conflict.
