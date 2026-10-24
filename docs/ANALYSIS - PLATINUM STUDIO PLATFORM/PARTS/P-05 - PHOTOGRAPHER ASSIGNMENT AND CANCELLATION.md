# P-05 - PHOTOGRAPHER ASSIGNMENT AND CANCELLATION

[Back to start](../00%20-%20START%20HERE.md) · [All parts](../11%20-%20PARTS%20IN%20DETAIL.md) · [Roadmap](../07%20-%20DEVELOPMENT%20ROADMAP.md)

**What it is for:** Moves assigned work through availability, status, and exceptional cancellation decisions.
**Where it sits:** [05 - SYSTEM ARCHITECTURE](../05%20-%20SYSTEM%20ARCHITECTURE.md#the-parts)
**Built by:** R-06, R-20 in [the roadmap](../07%20-%20DEVELOPMENT%20ROADMAP.md)
**Status:** Evidence-backed status is recorded in the parts index.

## Why it exists

Moves assigned work through availability, status, and exceptional cancellation decisions. It is a distinct part because its rules, people, or state can change without changing every other part. Source: StudioOwner/BookingController.php; photographer requests; frozen cancellation reference.

## What it does

| # | Job | Who asks for it | Source |
|---|---|---|---|
| 1 | Owner availability and assignment actions; photographer status; paid-booking recovery, replacement, client response, deadline, and manual-refund actions. | The role or workflow that owns the action | StudioOwner/BookingController.php; BookingCancellationRecoveryService.php; photographer requests |
| 2 | Keeps the resulting state available to the next portal or workflow. | The next responsible part | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |

## Who may use it

| Who they are | What they may do | What they may not do | Evidence |
|---|---|---|---|
| The role named by the current portal | Use the action explicitly exposed to that role. | No action outside its role, permission, or studio scope. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |
| Administrator or owner where the routes explicitly allow it | Review or manage the records in scope. | No undocumented platform-wide override. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |

The material does not authorize a broader permission merely because it would be convenient. Any missing action boundary is Q-09 or the question named below.

## The information it handles

| Information | Purpose | Where it is kept | Source |
|---|---|---|---|
| Identity, role, or workflow record | Connects the action to a person and scope. | Relational application records. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |
| Current state and history | Shows what happened and what can happen next. | The part’s application records. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |
| Notification or external reference when present | Returns the responsible person to the next action. | Notification or provider records. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |

## How it behaves, step by step

**Main journey**

1. A permitted person opens the relevant portal action.
2. The application checks identity, scope, input, and current state.
3. The record is created or changed.
4. The next responsible part or person reads the new state.

**Failure journey**

1. A check fails or an outside dependency does not answer.
2. The application refuses, preserves, or falls back according to the current code.
3. If the material does not define the business result, the action remains an open question instead of an invented rule.

## The states things move through

Unassigned → assigned → accepted or changed → completed or cancelled; paid photographer cancellation → recovery → replacement proposed → client accepted, or refund pending → refunded

## What it checks before it agrees

| # | Check | If it fails | Evidence |
|---|---|---|---|
| 1 | The person is signed in and allowed for this portal. | Refuse or redirect the action. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |
| 2 | The record belongs to the permitted user, studio, or workflow. | Refuse the action. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |
| 3 | The input and current state allow the requested transition. | Leave the record unchanged and report the refusal. | StudioOwner/BookingController.php; photographer requests; frozen cancellation reference |

## When something goes wrong

The current source exposes validation, authorization, state checks, provider fallbacks, or error responses where they exist. It does not supply a universal recovery policy for every partial failure. That limit is recorded in [combined findings](../04%20-%20COMBINED%20FINDINGS.md) rather than filled with a generic assumption.

## What it leans on, and what leans on it

**It cannot work without:**

| Part | Need |
|---|---|
| Accounts and access | A known person and allowed scope. |
| The relevant shared record | A studio, booking, request, subscription, or conversation to act on. |

**These need it:**

| Part | What it takes |
|---|---|
| The next responsible portal part | The current permitted state and its history. |
| Notifications | A message target when follow-up is required. |

## How you know it is finished

- A permitted role can complete the documented main journey.
- An unpermitted or out-of-scope role is refused.
- The state and responsible next action are visible after success or failure.

## What the material does not say

| # | Unclear point | Why it matters |
|---|---|---|
| Q-01 and Q-11 | ~~What remedy, financial state, deadline, and record are required?~~ **Resolved by the approved Phase 6 design and implementation on 12 August 2026.** | Same-studio replacement, client response, `min(now + 24 hours, event start − 2 hours)` Manila deadline, and admin manual full-refund evidence per succeeded payment are implemented. |

## T02 changes (24 October 2026)

Source: [T-13](../09%20-%20TASK%20TRACKER.md) and the [T02 plan](../../implementation-plan/t02-client-defects/phase-9.md). Verified locally; production is unverified.

| # | Shipped behavior | Tests |
|---|---|---|
| 1 | Approving a reschedule or offering other dates is refused when the assigned photographer is busy, the slot is taken, or the studio is closed that day. Assigned photographers are notified when a booking moves. | `tests/Feature/Booking/RescheduleRequestTest.php` |
| 2 | The assigned photographer's modal shows the booking's equipment and a "Confirm equipment" action. | `tests/Feature/Booking/EquipmentAssignmentTest.php`, `BookingPreparationTest.php` |
| 3 | A freelancer can refund only a fully paid booking. A downpayment-only booking is refused with "A downpayment is non-refundable. Only fully paid bookings can be refunded." | `tests/Feature/Booking/ClientCancellationRefundFlowTest.php` |
| 4 | The photographer's assignment view lists the photo editing the client chose. | `tests/Feature/Booking/PhotoEditingPricingTest.php`, `FreelancerAccessTest.php` |
| 5 | An owner can invite freelancers to any studio they own, not only the first. The member pages have a studio picker, and an invite names only a studio the owner owns. | `tests/Feature/Owner/MemberInviteStudioTest.php` |
| 6 | A fallback freelancer keeps booking-scoped access after the event, so they can deliver the gallery. Access ends when their work or the booking is completed or cancelled. Thirty days after the event end is only a safety cap. Re-run in the browser on 24 October 2026 ([Phase 10](../../implementation-plan/t02-client-defects/phase-10.md)). | `tests/Feature/Booking/FreelancerAccessTest.php`, `tests/Feature/Chat/ChatTest.php` |

Open question from the 24 October re-run: a freelancer booking can be completed once fully paid, with no gallery delivered. Studio bookings must first have a published gallery and delivered printed photos.
