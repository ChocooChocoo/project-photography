# Photographer Cancellation Contingency Reference

> **In plain terms:** This is the full analysis of cancellation options and decisions. It is not an approved implementation plan.
>
> **Status:** Historical detail retained in the new System Analysis format. For the current normalized status, see the [progress tracker](../05-progress/tracker.md).

---


# Photographer-Initiated Cancellation of a Paid Booking

> **Status: analysis only. Nothing here is implemented, and no policy has been chosen.**
> Source brief: [`prompt/tasks/07.md`](../../../prompt/tasks/07.md). Task report:
> [`prompt/output/07.md`](../../../prompt/output/07.md).
>
> This document exists to make a decision possible. It describes what the platform does today when
> an assigned photographer cancels a booking the client has already paid for, sets out **nine
> resolution options** rather than one, and lists the decisions (**D1–D9**) that must be answered
> before any of them can be built. Roadmap entries derived from it live in
> [`CAPSTONE B IMPLEMENTATION ROADMAP.md`](../05-roadmap/roadmap.md) Phase 9.
>
> **Start here if you only read one section:** §5 covers the case that defines the problem — the event
> date cannot move — where most of the option set is useless and only substitution helps. §6 gives a
> recommended build set (nine methods in three tiers) as a default for the decision to argue with.

---

## 1. The scenario

A client books a studio, pays, and the studio owner assigns a photographer. The photographer accepts.
Some time before the session, the photographer cancels — illness, emergency, double-booking, or simply
backing out.

The client's money is with the platform. The event date is fixed and often unmovable (a wedding, a
birthday, a graduation). The client did not choose the photographer and has no relationship with them.
From the client's side, nothing visibly happens at all.

### 1.1 What the code actually does today

| Step | What happens | Where |
|---|---|---|
| Client pays | `PaymentModel` rows with `status = succeeded`; `BookingModel::updatePaymentStatus()` sets `payment_status = paid`; `SystemRevenueModel` records the platform's cut | [`PaymentModel::markAsPaid()`](../../../app/Models/PaymentModel.php#L82) |
| Owner assigns | `tbl_booking_assigned_photographers` row, `status = assigned`, 24h `response_deadline` | [`BookingController::assignPhotographers()`](../../../app/Http/Controllers/StudioOwner/BookingController.php#L416) |
| Photographer accepts | assignment → `confirmed`, **and the booking itself flips to `in_progress`** | [`AssignedBookingController`](../../../app/Http/Controllers/StudioPhotographer/AssignedBookingController.php#L172) |
| Photographer cancels | assignment → `cancelled`, `cancelled_at` and `cancellation_reason` written | [`AssignedBookingController`](../../../app/Http/Controllers/StudioPhotographer/AssignedBookingController.php#L275) |
| …and then | **nothing else.** No booking status change, no notification to the owner, no notification to the client, no payment action, no reassignment prompt, no record against the photographer | — |

Cancellation is permitted only while the assignment is `assigned` or `confirmed`
([`canCancel()`](../../../app/Models/StudioOwner/BookingAssignedPhotographerModel.php#L331)). Once the
photographer marks themselves on-site, they are locked in. That guard is sound. The problem is
everything downstream of a cancellation that *is* allowed.

### 1.2 The deadlock

This is the finding that matters most, and it is not obvious from reading either file alone.

Accepting an assignment sets `booking.status = 'in_progress'`. Both owner recovery actions are gated on
that exact status:

- [`getAvailablePhotographers()`](../../../app/Http/Controllers/StudioOwner/BookingController.php#L288) —
  *"Cannot assign photographers to a booking that is in progress or already completed."*
- [`removePhotographerAssignment()`](../../../app/Http/Controllers/StudioOwner/BookingController.php#L816) —
  *"Cannot remove photographer from a booking that is in progress or already completed."*

So a photographer who accepts and then cancels leaves the booking **`in_progress` with zero active
photographers, and the owner locked out of both fixes**. The guards exist to protect a shoot that is
genuinely under way; they cannot tell that state apart from a shoot nobody is going to.

The only escape available to the owner today is to cancel the whole booking
([`updateStatus()`](../../../app/Http/Controllers/StudioOwner/BookingController.php#L841)) — which is a
studio-side cancellation on a client who did nothing wrong, and which sets
`payment_status = 'refund_pending'`, a flag with no queue behind it (§2.3).

A booking that was never accepted — photographer cancels while still `assigned` — does not deadlock,
because the booking is still `pending`/`confirmed` and reassignment works. So severity depends on
whether the photographer accepted first, and the system does not distinguish the two cases anywhere.

---

## 2. The operational gap

### 2.1 Nobody is told

Photographer cancellation triggers no notification of any kind. The owner discovers it by opening the
booking. The client never discovers it — they arrive on the day, or they don't, and either way the
platform's records still say the shoot is in progress.

`Notifiable` already carries 18 notification methods including `notifyBookingCancelledByStudio`,
`notifyPhotographerAssigned`, and `notifyBookingExpired`
([`app/Traits/Notifiable.php`](../../../app/Traits/Notifiable.php)). The absence here is a missing call,
not a missing capability.

### 2.2 No state expresses "paid, scheduled, unstaffed"

`BookingModel` has five statuses — `pending`, `confirmed`, `in_progress`, `completed`, `cancelled`
([`BookingModel.php`](../../../app/Models/BookingModel.php#L18)) — and none of them describes a paid
booking that has lost its photographer. The transition map
([`canTransitionTo()`](../../../app/Models/BookingModel.php#L427)) has no route backwards out of
`in_progress` except to `completed` or `cancelled`.

### 2.3 The money has nowhere to go

- **Neither payment gateway wrapper can refund.** `PaymongoService` and `StripeService` expose
  checkout/charge creation and verification only. There is no refund call anywhere in `app/`.
- **`refund_pending` is a bare string.** Set at
  [`BookingController.php#L894`](../../../app/Http/Controllers/StudioOwner/BookingController.php#L894) with
  the honest comment *"No auto-refund yet — that's a later automation phase."* Nothing reads it. It is
  not in `getPaymentStatusBadgeClass()`
  ([`BookingModel.php`](../../../app/Models/BookingModel.php#L496)), so it renders as a grey unlabelled
  badge. There is no admin or finance queue listing bookings awaiting refund.
- **`PAYMENT_REFUNDED` is declared and never assigned** on the booking path
  ([`BookingModel.php`](../../../app/Models/BookingModel.php#L31)).
- **The freelancer-side refund handler is a stub** — `handleCancellationRefund()` writes a `Log::info`
  under a comment listing the three things a real system would do
  ([`Freelancer/BookingController.php`](../../../app/Http/Controllers/Freelancer/BookingController.php#L275)).
- **Revenue reversal is not wired to bookings.** `SystemRevenueModel::markAsRefunded()` exists
  ([`SystemRevenueModel.php`](../../../app/Models/SystemRevenueModel.php#L290)) and subscription
  cancellation already uses that pattern
  ([`SubscriptionController.php#L758`](../../../app/Http/Controllers/StudioOwner/SubscriptionController.php#L758)),
  but no booking cancellation path calls it. Refunding a booking today would leave the platform's
  recorded revenue overstated.

### 2.4 There is no reschedule path

`event_date`, `start_time`, and `end_time` are written when the booking is created and never changed by
any controller. Every option involving a new date needs this built from scratch, including the
knock-on availability re-check and the client's consent to the new slot.

### 2.5 There is no credit or wallet concept

`tbl_client_budget` is a planning tool — the client sets a cap and `spent_amount` tracks against it. It
holds no balance the platform owes the client. Any "booking credit" remedy needs a new ledger.

### 2.6 The photographer bears no recorded consequence

The cancellation reason is stored on the assignment row and read nowhere. There is no count, no rate, no
flag on `tbl_studio_photographers`, and nothing surfaces to the owner at assignment time. A photographer
who cancels every third booking looks identical to one who has never cancelled.

### 2.7 A collision to be aware of

The client-side cancel path overwrites `payment_status` with the string `'cancelled'` regardless of
whether the client had paid
([`MyBookingsController.php#L279`](../../../app/Http/Controllers/Client/MyBookingsController.php#L279)),
destroying the record that money was received. Whatever policy is chosen here will have to reconcile
with that, because both paths write the same column and only one of them can be right.

---

## 3. Resolution options

Seven options. They are not mutually exclusive — a real policy will likely be a decision tree over
several of them (for instance: try A, fall back to C, then D). Each is described on its own so the
trade-offs stay visible.

Every option assumes the cross-cutting work in §7, which is common to all of them.

---


### Option A — Substitute photographer, same date and time

**Client experience.** Told promptly that their photographer changed, shown who the replacement is, and
asked to acknowledge. The session happens as booked.

**Paid booking.** Untouched. No money moves, no refund, no revenue reversal. The package snapshot in
`tbl_booking_packages` still describes what was bought, and it is still being delivered.

**Scheduled session.** Proceeds unchanged — the strongest outcome for session continuity, and the only
option that preserves a date that cannot be moved.

**What must be built.**
1. Lift the `in_progress` block for the specific case of a booking whose assignments are all cancelled
   (§7.1) — otherwise substitution is impossible in exactly the case that needs it.
2. A "find replacement" action on the owner's booking view, filtered to genuinely free photographers.
3. Client notification naming the replacement, plus an acknowledgment window (D2).
4. An escalation when no replacement exists inside the studio.

**Reuses.**
[`PhotographerAvailabilityService::getAvailabilityMapForBooking()`](../../../app/Services/PhotographerAvailabilityService.php#L33)
already checks approved leave and overlapping assignments for the booking's date and time window and
returns a ready-made reason string per photographer. Substitution needs no new matching logic — the
owner's assignment modal already renders exactly this payload. `notifyPhotographerAssigned()` already
exists.

**Cost and risk.** The cheapest option by a wide margin, and the only one that needs no money movement
and no new schema beyond an audit trail. Risk: it can silently downgrade what the client paid for — a
lead photographer with ten years' experience replaced by an assistant is the same booking on paper and
a different product in practice. If the client rejects the substitute, the policy still needs a fallback
(D2 decides whether they may). And a small studio on a Saturday may simply have nobody free, so A can
never be the whole answer.

---

### Option B — Reschedule with the original photographer

**Client experience.** Told the photographer cannot make the booked date, offered alternative dates when
that same photographer is free, and chooses one.

**Paid booking.** Untouched — the payment carries to the new date. No refund, no revenue reversal.

**Scheduled session.** Moves. Delivered by the person originally assigned, which preserves the
photographer-client fit that Option A gives up.

**What must be built.**
1. A reschedule flow: propose date/time, client accepts or declines, `event_date`/`start_time`/`end_time`
   updated, availability re-checked against the new slot, package and pricing snapshot re-validated.
2. A record of the original date — nothing today survives an overwrite of `event_date`.
3. Guard rails on how many times and how late a booking may be moved.
4. Client refusal handling — declining a reschedule must land somewhere, not dead-end.

**Reuses.** `PhotographerAvailabilityService` for the new slot. The client booking calendar built in
roadmap 2.4 is the natural surface for date selection.

**Cost and risk.** Only viable when the photographer's problem is the date, not the booking — which
rules out the most common cancellation causes. Useless for a fixed-date event: a wedding cannot move,
so B is inapplicable to a large share of real cases. Meaningful build (a reschedule path is a feature,
not a patch), and it hands the client the disruption while the photographer keeps the job.

---

### Option C — Reschedule with a replacement photographer

**Client experience.** Both changes at once: new date, new photographer.

**Paid booking.** Untouched.

**Scheduled session.** Moves, and is staffed by someone else.

**What must be built.** The union of A and B. Nothing additional, but nothing shared away either.

**Reuses.** Everything A and B reuse.

**Cost and risk.** The catch-all fallback when the original date cannot be staffed and the original
photographer cannot return — genuinely useful as the third branch of a decision tree, and it keeps the
money with the studio. But it is the maximum-disruption "we kept your money" outcome, and asking a
client to accept two changes at once is where goodwill runs out. Should never be offered before A and B
have been tried.

---

### Option D — Cancel and refund in full

**Client experience.** Told the booking cannot be fulfilled, and all money is returned.

**Paid booking.** Reversed. Every `succeeded` payment refunded, `payment_status` moved to a real
refunded state, `SystemRevenueModel` rows marked refunded so platform revenue is not overstated.

**Scheduled session.** Does not happen. The client re-books elsewhere, usually at short notice and
usually at a higher price.

**What must be built.**
1. **A refund capability in both gateway wrappers** — this does not exist at all (§2.3), and it is the
   single largest technical prerequisite in this document.
2. Refund records: partial-vs-full, gateway reference, failure handling when the gateway declines.
3. Revenue reversal on every affected `SystemRevenueModel` row.
4. `payment_status` states that mean something end to end — including a badge mapping, which
   `refund_pending` never got.
5. A finance or admin queue for refunds that need manual handling, and reconciliation for gateway
   refunds that fail asynchronously.
6. Settlement policy against the studio: the studio may already have been paid out (D4).

**Reuses.** [`SystemRevenueModel::markAsRefunded()`](../../../app/Models/SystemRevenueModel.php#L290) and
the reversal pattern in
[`SubscriptionController`](../../../app/Http/Controllers/StudioOwner/SubscriptionController.php#L758).
The existing `refund_pending` flag becomes the entry point of a real queue rather than a dead end.

**Cost and risk.** The fairest outcome and the one a client would consider the floor — but the most
expensive to build and the worst for session continuity. It also cannot stand alone: refunding money
does not get the client photographed on their wedding day. Where money actually leaves the platform,
this must be correct before it is convenient; a half-built refund path is worse than a manual one.

---

### Option E — Cancel and refund in part

**Client experience.** Booking cancelled, some money returned, some retained — for example a
non-refundable deposit, or a deduction for work already delivered.

**Paid booking.** Partially reversed. Requires deciding what the retained portion is *for*.

**Scheduled session.** Does not happen.

**What must be built.** Everything in D, plus a defensible split rule, plus per-payment partial-refund
records, plus the disclosure of that rule to the client **at booking time** rather than at cancellation
time.

**Reuses.** `deposit_policy` and `down_payment` already exist on `tbl_bookings`
([`BookingModel.php`](../../../app/Models/BookingModel.php#L52)) and are the natural anchor for a split
rule.

**Cost and risk.** Hard to justify in this specific scenario and worth stating plainly: the client did
nothing wrong, so retaining their money because the *provider* cancelled is difficult to defend and is
the most likely source of disputes and bad reviews. Partial refunds make sense for client-initiated
cancellation, which is a different policy. Included here for completeness, and because there is a narrow
case where it fits — a multi-part booking where some sessions were genuinely delivered before the
photographer withdrew.

---

### Option F — Booking credit toward a future session

**Client experience.** Booking cancelled, value returned as credit usable on a future booking, possibly
with a bonus for the inconvenience.

**Paid booking.** Converted, not reversed. Money stays on the platform; the platform now owes a service.

**Scheduled session.** Does not happen; a later one is pre-funded.

**What must be built.**

1. **A credit ledger that does not exist** — balance, issuance, expiry, redemption at checkout, refund
   of unused credit, and the accounting treatment of a liability rather than revenue.
2. Rules: expiry, transferability, whether credit is studio-scoped or platform-wide, what happens if the
   studio leaves the platform.
3. Booking checkout changes to spend credit against a total.

**Reuses.** Nothing directly. `tbl_client_budget` is a spending planner, not stored value, and should
not be repurposed.

**Cost and risk.** The largest build of the seven and the only one that creates a standing financial
liability with regulatory and accounting weight. Attractive to the business — the money stays — which is
exactly why it should not be the *only* remedy offered; credit-instead-of-refund when the fault was the
provider's reads as coercive. Realistically viable only as an opt-in alongside D, usually sweetened, and
only after D exists.

---

### Option G — Structured manual escalation, no automation

**Client experience.** Told promptly that the platform is arranging a replacement, and told again when
it is resolved. A human at the studio does the arranging; the platform tracks the clock.

**Paid booking.** Held. No automated money movement. If nothing is resolved inside the window, the case
escalates to a manual refund decision.

**Scheduled session.** Depends on the studio, but the client is at least never left uninformed.

**What must be built.**
1. Cascade the assignment cancellation onto the booking so its state stops lying (§7.1).
2. Notify owner and client immediately (§7.2).
3. A resolution deadline with escalation, modelled on the `response_deadline` pattern from roadmap 2.3
   ([`isPastDeadline()`](../../../app/Models/StudioOwner/BookingAssignedPhotographerModel.php#L148)) and the
   scheduled-command pattern from `bookings:expire-pending`.
4. A visible queue of unresolved cancellations for the owner.

**Reuses.** `Notifiable`, the existing hourly scheduler in `routes/console.php`, the deadline pattern
from 2.3, the auto-expiry command pattern from 2.7.

**Cost and risk.** The honest baseline. It is what the system is *pretending* to do today, minus the
pretence — and it removes the single worst property of the current behaviour, which is silence. It fixes
nothing about money and does not guarantee the session happens, so it is a floor rather than a policy.
Its real value is that every other option needs items 1–3 anyway, so building G first is not wasted work
whichever way D1–D9 are decided.

---

### Option H — Widen the substitution pool beyond the studio

**Client experience.** Identical to Option A — they are told their photographer changed and who is
coming instead. They do not need to care that the replacement is not on the studio's payroll.

**Paid booking.** Untouched, with one addition: the replacement has to be paid, so the studio's margin
on that booking absorbs the cost. No client-facing money movement.

**Scheduled session.** Proceeds on the original date. This is the option that makes Option A work when
the studio's own roster is empty — a small studio on a Saturday very often has nobody free, and A alone
dead-ends there.

**What must be built.** An escalating search, each step widening the pool only when the previous one
comes back empty:

1. Other active photographers at the same studio (this is Option A).
2. The studio's own off-duty staff, offered the job as overtime.
3. **Platform freelancers** — same city, same category, free on that date.
4. Another studio on the platform.

**Reuses.** Steps 1–2 need `PhotographerAvailabilityService` and the existing overtime request flow
(`tbl_overtime_requests`), both already built. Step 3 is the significant one: the platform already
carries freelancers with categories, schedules, and packages, and they are entirely disconnected from
studio bookings today. The supply exists and has never been pointed at this problem.

**Cost and risk.** Medium build, and by some distance the best return per unit of work in this document —
it converts "we're sorry, here's your money" into "we fixed it" precisely in the case where money is
useless. Step 4 (studio-to-studio) needs a revenue-split agreement that does not exist and should be
treated as a later addition, not part of the first build. Risks: quality control over a photographer the
studio has not worked with, who carries the liability if the replacement underdelivers, and how the
freelancer is paid when the client paid the studio. Step 3 should be opt-in for freelancers, not an
automatic draft.

---

### Option I — Refund the value gap on a downgraded substitution

**Client experience.** The shoot happens with a replacement, and the difference in value comes back to
them without their having to ask.

**Paid booking.** Partially reversed, in proportion to what was actually delivered against what was
bought.

**Scheduled session.** Proceeds. This option does not stand alone — it is a modifier on A, C, or H.

**What must be built.**
1. A comparability rule: when is a replacement equal, better, or worse? Position, years of experience,
   and specialization are already recorded on `tbl_studio_photographers` and are the obvious anchor.
2. Equal or better → no adjustment. Worse → refund the difference automatically.
3. Requires the refund machinery from Option D to exist first.

**Reuses.** Option D's refund path. `tbl_studio_photographers` already stores position, years of
experience, and specialization.

**Cost and risk.** Small *once Option D exists*, and it is what makes substitution fair rather than
merely convenient. Without it, the platform can quietly hand a client a ten-year lead photographer's
package delivered by a first-year assistant and call the matter closed. Risk: the comparability rule is
a judgment call dressed as arithmetic, and clients will contest it. Simplest defensible version is a
fixed percentage per experience tier, published in advance.

---

## 4. Comparison

| | Client disruption | Session likely to proceed | Money moves | Needs new schema | Needs gateway refund | Build size | Gated by |
|---|---|---|---|---|---|---|---|
| **A** Substitute, same slot | Low | **Yes** | No | Audit only | No | **S** | D1, D2 |
| **B** Reschedule, same photographer | Medium | Yes, later | No | Reschedule history | No | M | D1 |
| **C** Reschedule + replacement | High | Yes, later | No | Reschedule history | No | M | D1, D2 |
| **D** Full refund | High | **No** | **Out** | Refund records | **Yes** | **L** | D3, D4 |
| **E** Partial refund | High | No | Out, partly | Refund records + split rule | Yes | L | D3, D4, D6 |
| **F** Booking credit | Medium | Not this one | Held as liability | **Credit ledger** | Partly | **XL** | D3, D5 |
| **G** Manual escalation + SLA | Low–High | Maybe | No | Case/audit record | No | **S** | none |
| **H** Widen the substitution pool | Low | **Yes** | Studio margin only | Cross-provider assignment | No | M | D8 |
| **I** Value-gap refund on downgrade | Low | Yes | Out, partly | Comparability rule | Yes (needs D) | S after D | D4, D8 |

Read together: **A, G, and H are the ones that actually save the session** — G makes the failure
visible, A resolves it inside the studio, H resolves it when the studio is empty. **D is the expensive
prerequisite** that gives the policy a floor when substitution fails outright, and nothing that returns
money can ship before it. **I is what stops substitution from quietly shortchanging the client.**
**F is a separate project** wearing a remedy's clothing.

---

## 5. The primary problem: the date cannot move

Sections 3 and 4 treat all cancellations alike. They are not alike. The case that defines this whole
document is narrower and harder:

> The client has prepared for months. The venue is booked, the guests are invited, the dress is bought.
> The event happens **on that day**. The photographer cancels with days — or hours — to go.

In that case most of the option set evaporates:

- **B, C — reschedule: inapplicable.** A wedding does not move.
- **D, E — refund: technically correct, practically useless.** Money back on the morning of the event
  does not get anyone photographed. Nobody rebooks a wedding photographer at 7am on a Saturday.
- **F — credit: worse.** It offers a future shoot to replace an occasion that will not happen twice.

**Only substitution helps.** That reduces the design question from *which remedy* to **how wide the net
goes, and how fast.** Everything else in this scenario is damage control after that search fails.

### 5.1 The escalation ladder


Widen the pool one step at a time, only when the previous step comes back empty:

| Step | Pool | Feasible today? |
|---|---|---|
| 1 | Another active photographer at the same studio | Yes — `PhotographerAvailabilityService` already computes this |
| 2 | Studio's off-duty staff, offered as overtime | Yes — `tbl_overtime_requests` and the approval flow already exist |
| 3 | **Platform freelancers** — same city, same category, free that date | **Yes, and unused.** The supply is on the platform and has never been connected to studio bookings |
| 4 | Another studio on the platform | Needs a revenue-split agreement that does not exist. Later addition |
| 5 | Nobody available | Refund plus compensation. The event goes unphotographed |

Step 3 is where this stops being an apology and becomes a fix. A marketplace that already carries
freelancers with schedules, categories, and packages is sitting on exactly the supply this emergency
needs.

### 5.2 How close the event is changes what the client is owed

The same cancellation demands three different responses depending on the clock. A single policy cannot
cover all three, and a system that treats them identically will be wrong in at least two of them.

| Notice | Response | Client's protection |
|---|---|---|
| **Weeks out** | Owner proposes a replacement; client reviews their profile and may decline | **Choice.** Declining falls back to a refund, and there is time to rebook elsewhere |
| **Days out** | Owner assigns a replacement; client is notified immediately; declining is still possible but refund is the only remaining fallback | **Choice, narrowing** |
| **Hours out / day of** | No time for consent. Owner assigns whoever is qualified and available, and tells the client who is coming | **Money, not choice** — protection shifts to Option I and to compensation |

The acknowledgment window from D2 therefore cannot be a fixed number. It has to scale with the gap
between now and the event, in the same way the resolution SLA in §7.3 does.

### 5.3 A substitute alone does not make the client whole

A ten-year lead photographer replaced by a first-year assistant is the same booking on paper and a
different product in reality. Substitution needs money attached to be fair:

- **Equal or better replacement** → no adjustment. The shoot proceeds as sold.
- **Lesser replacement** → refund the difference automatically (Option I). The client gets both the
  shoot and the value they paid for.
- **Nobody found** → full refund, and the platform should add something on top. The client lost their
  photographs, not merely their money, and a bare refund treats those as the same loss.

### 5.4 The honest limit

Sometimes the ladder terminates in failure. No photographer in the studio, none off-duty, no freelancer
free, no partner studio — and the event happens anyway, unphotographed. Any policy that pretends this
outcome does not exist will handle it badly when it arrives.

It should be a named, designed outcome: full refund, compensation on top, an apology that does not read
as boilerplate, and a record against the photographer who caused it. Designing for the failure case is
what separates a policy from a marketing promise.

### 5.5 Prevention outranks every remedy here

By the morning of the event, every available option is bad. The leverage sits earlier, and it is cheap:

- **P1 — Restrict late cancellation.** Today a photographer can self-cancel with one click at any moment
  up until they mark themselves on-site. A cancellation three weeks out and one twelve hours out are not
  the same act and should not share an interface. Late cancellation should require owner approval or
  carry a defined consequence. This is the smallest change in this document with the largest effect,
  because the best fix for an event-morning cancellation is fewer event-morning cancellations.
- **P2 — Backup photographer on high-value bookings.** A named second on the assignment from the start.
  Costs nothing until it is needed, and collapses the entire escalation ladder into a single step when
  it is.

---

## 6. A recommended implementation set

> **This is a recommendation, not a decision.** The brief requires that options be evaluated before a
> policy is finalised, and D1–D9 in §8 remain open. What follows is the shape the evidence points to,
> offered so the decision has a default to argue with.

Nine methods, in three tiers. Four of them make the system functional; the rest make it good.

### Tier 1 — Required. Without these the system is broken, not merely limited. (4)

| # | Method | Roadmap | Blocked by a decision? |
|---|---|---|---|
| 1 | Cascade + unlock owner recovery | 9.1 | **No — start now** |
| 2 | Notify owner and client, with a resolution clock | 9.2 | **No — start now** |
| 3 | Substitution inside the studio (Option A) | 9.3 | D1, D2 |
| 4 | Refund execution (Option D) | 9.5 | D3, D4 |

1 and 2 are small and gated by nothing. 3 is small because the availability matching already exists.
4 is the one real project — refund capability must be built from zero — and it is the prerequisite for
anything that returns money.

Stop after Tier 1 and no paid client is ever stranded silently again. Everything below is improvement
rather than repair.

### Tier 2 — High value once Tier 1 exists. (3)

| # | Method | Roadmap | Why |
|---|---|---|---|
| 5 | Freelancer emergency pool (Option H) | 9.8 | Best return per unit of work. Makes substitution succeed when the studio's own roster is empty — the exact case Tier 1 cannot solve |
| 6 | Value-gap refund (Option I) | 9.9 | Makes substitution fair instead of merely convenient. Cheap once item 4 exists |
| 7 | Photographer cancellation record | 9.7 | Feedback loop. Owners cannot route around a pattern they cannot see |

### Tier 3 — Prevention. Cheap, and worth more than any remedy. (2)

| # | Method | Roadmap | Why |
|---|---|---|---|
| 8 | Late-cancellation restriction (P1) | 9.10 | Smallest change, largest effect |
| 9 | Backup photographer on high-value bookings (P2) | 9.11 | Costs nothing until needed |

### Deliberately not recommended (2)

| Method | Why not |
|---|---|
| **Booking credit ledger** (Option F, roadmap 9.6) | Largest build in the document, creates a standing financial liability with accounting weight, and credit-instead-of-cash when the *provider* cancelled reads as coercive. Refunds cover the same ground honestly. Revisit only if the business specifically wants stored value as a product. |
| **Reschedule path** (Options B/C, roadmap 9.4) | Only helps when the date can move — which is precisely the case that was never urgent. A booking weeks out can be cancelled and rebooked. Build it because clients want to reschedule in general, not as an answer to this problem. |

### Order of work

```
1. Cascade + unlock            9.1   ── no decision needed
2. Notify + resolution clock   9.2   ── no decision needed
3. Late-cancellation limit     9.10  ── cheap prevention, do it early
4. Substitution in-studio      9.3   ── needs D1, D2
5. Refund execution            9.5   ── needs D3, D4; the one real project
6. Value-gap refund            9.9   ── needs 9.5
7. Freelancer emergency pool   9.8   ── needs D8
8. Cancellation record         9.7   ── needs D7
9. Backup photographer         9.11  ── independent, any time
```

---

## 7. Cross-cutting requirements

Needed by every option, and therefore the first thing to specify regardless of which is chosen.

### 7.1 Cascade rule

When an assignment is cancelled, the booking must react. Minimum: if no active assignment remains, the
booking must leave `in_progress` so that owner recovery actions unblock. Whether that means a new status
(`awaiting_reassignment`), a boolean flag, or reverting to `confirmed` is a design decision — but the
current behaviour, where the booking claims to be in progress with nobody working on it, cannot stand
under any option. Note that `canTransitionTo()` currently permits no backwards move out of
`in_progress`.

### 7.2 Notification

At minimum: owner on cancellation, client on cancellation, client on resolution. Copy has to state what
happens to their money even when the answer is "nothing, your booking is unaffected" — silence about
money is what turns a recoverable incident into a dispute. Extend `Notifiable` in place; do not
introduce a parallel mechanism.

### 7.3 Resolution SLA

A cancellation must have a clock on it, scaled to how close the event is — a cancellation three weeks out
and one twelve hours out are not the same emergency. Both the deadline pattern (roadmap 2.3) and the

scheduled-command pattern (roadmap 2.7, `bookings:expire-pending`) already exist to copy.

### 7.4 Audit trail

Who cancelled, when, why, what was offered, what the client chose, what was paid or returned. Today the
reason string on the assignment row is the only artefact and nothing reads it. Any money movement makes
this mandatory, not optional.

### 7.5 Revenue integrity

Any option that returns money must reverse the matching `SystemRevenueModel` rows via
[`markAsRefunded()`](../../../app/Models/SystemRevenueModel.php#L290). Skipping this overstates platform
revenue and corrupts the owner income report added in roadmap 2.2.

### 7.6 Photographer record

Cancellations should accumulate against the photographer and be visible to the owner at assignment time.
Without it there is no feedback loop and no way to distinguish an emergency from a pattern. Whether it
carries a consequence is D7.

---

## 8. Decisions required before implementation

Each decision unblocks specific options. None of them is a technical question.

| # | Decision | Unblocks | Notes |
|---|---|---|---|
| **D1** | Who chooses the remedy — studio owner, client, or owner-proposes/client-accepts? | A, B, C | Owner-only is simplest and fastest; client-choice is fairest. The middle path (owner proposes, client accepts within a window) is the most likely answer and is also the most work. |
| **D2** | May the client reject a substitute photographer, and inside what window? | A, C | If yes, needs a rejection path that lands somewhere — probably D. Ties directly to checklist Part 4 item 1 (photographer identity revealed only after payment). |
| **D3** | Automated gateway refunds, or a manual finance queue first? | D, E, F | Manual is shippable now and matches how `refund_pending` was already scoped. Automated needs gateway refund support that does not exist yet. |
| **D4** | Who absorbs the platform fee and any studio payout already made? | D, E | Determines whether refunds are clean reversals or require clawback. Affects `SystemRevenueModel` handling. |
| **D5** | Is credit ever offered, and may it be offered *instead of* cash rather than alongside? | F | "Instead of" is a materially different product — and a materially harder one to defend when the studio caused the cancellation. |
| **D6** | Are partial refunds ever acceptable when the provider cancelled? | E | The default answer is probably no. If no, E drops out and the doc simplifies. |
| **D7** | What consequence, if any, attaches to a cancelling photographer? | §7.6 | Ranges from a visible counter, to assignment restrictions, to nothing. Interacts with HR (leave requests already integrate with availability). |
| **D8** | How wide does the substitution net go — studio only, studio + overtime, or platform freelancers too? | H, I | The decisive question for the fixed-date case in §5. Freelancers are the platform's existing unused supply; using them raises who pays them, who carries the liability, and whether they opt in. Step 4 (another studio) needs a revenue split and should be deferred. |
| **D9** | Should late cancellation be restricted, and from what point? | P1 (§5.5) | Today a photographer can self-cancel with one click until they mark themselves on-site. Options: owner approval required inside N hours, a recorded penalty, or no change. Cheapest lever in this document — the best fix for an event-morning cancellation is fewer of them. |

**Decision dependency:** D1, D2, D8, D9 shape the fixed-date response and gate the options that actually
save the session. D3, D4, D6 gate money movement. D5 gates credit only, and D5 can be answered "no" with
no loss of coverage. D7 stands alone.

---

## 9. Open questions and unknowns

1. **Gateway refund support is unverified.** PayMongo and Stripe both document refund APIs, but neither
   wrapper in this codebase implements one and no refund has ever been attempted here. PayMongo's rules
   for GCash refunds specifically (window, partial support, settlement timing) need confirming against
   the live account before D3 can be answered — this is the largest unknown in the document.
2. **`payment_status` is an unconstrained string** on `tbl_bookings` — the migration declares
   `string(...)->default('unpaid')`, not an enum — so new states cost nothing to store and are trivially
   easy to introduce without anything rendering them. `refund_pending` is the existing proof: it is
   written, and it is invisible.
3. **The client-cancel `payment_status` collision** (§2.7) must be resolved as part of whatever is built,
   or the two cancellation paths will keep writing incompatible values into the same column.
4. **Freelancer bookings are a parallel case.** A freelancer *is* the photographer, so there is no
   substitution option at all — the same brief applied to `booking_type = 'freelancer'` collapses to
   B/D/F. That path also has its own stubbed refund handler. Worth a separate pass. Note the asymmetry
   with Option H: freelancers are proposed as the *rescue* for studio bookings, but have no rescue of
   their own.
5. **Multi-photographer bookings.** Packages can require several photographers. One of four cancelling is
   a partial-staffing problem, not a cancellation — none of the nine options above is written for it,
   and the `allPhotographersCompleted()` logic assumes every assignment eventually completes.
6. **Whether the client should ever see the cancellation reason.** "Photographer unavailable" and the
   photographer's actual free-text reason are different disclosures with different consequences.
7. **How a freelancer drafted into a studio booking gets paid** (Option H, step 3). The client paid the
   studio; the platform recorded its cut; the freelancer is not on the studio's payroll. Whether this is
   a studio expense, a platform-brokered split, or a direct payout is unresolved and gates D8.
8. **Whether compensation on total failure (§5.4) is funded by the studio or the platform.** The
   photographer caused it, the studio employed them, and the platform sold the booking. There is no
   precedent in the codebase for platform-funded goodwill.

---

## 10. What this changes today

Nothing. No code, schema, or workflow was modified for this analysis. Related documentation:

- [`../01-ANALYSIS/REVISION CHECKLIST AND RECOMMENDATIONS.md`](../02-analysis/revision-checklist.md) — Part 4 item 11
- [`CAPSTONE B IMPLEMENTATION ROADMAP.md`](../05-roadmap/roadmap.md) — Phase 9
- [`../03-PROGRESS/ROADMAP PROGRESS.md`](../05-progress/delivery-history.md) — Phase 9, all items documented and not started
