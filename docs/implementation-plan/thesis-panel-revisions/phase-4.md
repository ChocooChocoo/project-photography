← [Previous: Phase 3](./phase-3.md) | [Next: Phase 5](./phase-5.md) →

# Phase 4: Block Re-Selecting a Photographer Who Rejected

**Status:** ✅ Done

**Goal:** A photographer who rejected an assignment cannot be chosen again for that same assignment.

## Dependencies
Phase 1.

## Tasks
- [x] A rejection is stored as status `cancelled` with `cancelled_at` and `cancellation_reason` (`app/Http/Controllers/StudioPhotographer/AssignedBookingController.php:341-353`).
- [x] Owner removal deletes the row (`StudioOwner/BookingController@removePhotographerAssignment`, `:837`), so remaining `cancelled` rows are photographer rejections.
- [x] In `StudioOwner/BookingController@getAvailablePhotographers` (`:299-450`), mark photographers with a `cancelled` row for this booking as unavailable with the reason "Rejected this assignment".
- [x] In `assignPhotographers` (`:455`, checks near `:526-597`) reject any user who has a `cancelled` row for the booking, server side.
- [x] Update the owner UI filter in `resources/views/owner/view-bookings.blade.php:1125-1277` to show the rejected state and disable selection.
- [x] Keep the replacement flow in `BookingCancellationRecoveryService` working: confirm a different photographer can still be assigned.
- [x] Tests in `tests/Feature/Booking/AssignmentLifecycleTest.php`: the list flags the rejecting photographer, and a direct assign request returns 422.

## Done when
- [x] The rejecting photographer is not selectable for that booking in the UI.
- [x] A forged request to assign them is rejected.
- [x] Other photographers remain assignable.

← [Previous: Phase 3](./phase-3.md) | [Next: Phase 5](./phase-5.md) →
