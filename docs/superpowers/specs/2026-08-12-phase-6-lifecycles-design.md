# Phase 6 — Cancellation and Subscription Lifecycles

Status: approved implementation design, 2026-08-12.

## Scope

Deliver the two independent lifecycle slices R-19 and R-20 using the existing Laravel, Stripe PHP SDK, booking, subscription, notification, permission, and revenue records. The implementation must preserve the existing seven-day subscription grace behavior and studio scope.

Explicitly out of scope: upgrades, partial refunds, credits, rescheduling, freelancer rescue or subscriptions, penalties, and automated refund APIs.

## R-19 — recurring studio subscriptions

- Paid checkout uses Stripe Checkout `subscription` mode with inline recurring `price_data`; trials remain card-free.
- Signed `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, and `customer.subscription.deleted` events are authoritative. The subscription webhook has its own configured signing secret.
- Persist Stripe customer, subscription, invoice, scheduled-cancellation, and first-failure identifiers/timestamps. Invoice identifiers are unique for idempotency. Store one local subscription period and one revenue row per unique paid invoice.
- A first failed renewal enters the existing seven-day grace period once. Stripe retries do not extend the local deadline; a recovered payment creates the next active period.
- Owners may cancel at any time. Cancellation sets Stripe period-end cancellation, gives no refund, and keeps access through the paid period. Owners may resume before termination.
- Reactivation after termination creates a new Stripe subscription using the previous plan when still available, otherwise the current catalog. Freelancer plans are excluded.

## R-20 — photographer cancellation recovery

- A paid studio booking cancelled by its photographer creates one recovery record. The cancellation deadline is `min(now + 24 hours, event start − 2 hours)` in `Asia/Manila`.
- Cancelled assignments no longer count toward required package staffing. The owner may propose one available replacement from the same studio; the replacement must confirm before the client receives accept/decline controls.
- Client acceptance resumes normal work. Client rejection, owner escalation, or deadline expiry cancels the booking and queues one full refund. The detailed photographer reason is owner-only; client messaging is neutral.
- Refunds are manual and admin-controlled. For each succeeded payment, the admin records a unique provider refund reference. Only after that evidence is recorded are payment, booking payment state, recovery state, and matching revenue marked refunded. The captured amount is returned to the client; platform revenue and studio earnings are reversed locally. Processor fees remain notes only.
- Add `BookingModel::PAYMENT_REFUND_PENDING` and `cancelled_by=photographer`; do not change ordinary cancellation semantics beyond the required collision guard.

## Interfaces

- Add nullable Stripe lifecycle columns and the `tbl_booking_cancellation_recoveries` record with original/replacement assignments, status, deadline, response/resolution timestamps, and outcome reason.
- Add payment refund reference, notes, and refund timestamp; assignment requests may reference a recovery.
- Add `POST /webhook/stripe/subscriptions`, owner resume, replacement response, owner escalation, admin refund queue, and admin refund completion routes.
- Schedule `bookings:escalate-photographer-cancellations` hourly with overlap protection.
- Reuse current in-app notifications and role/studio authorization; do not introduce a general event framework or new package.

## Acceptance evidence

Focused red-green tests cover signed webhook ordering and idempotency, renewal/failure/grace/cancel/resume/reactivation, studio isolation, single and multi-photographer recovery, replacement decisions, deadline races, authorization, refund evidence, revenue integrity, and regression behavior. Run the full Laravel, formatting, route/schedule, view-cache, frontend-build, and diff checks. Browser flows may be verified with Playwright; no live Stripe CLI renewal run is claimed because Stripe CLI is unavailable.
