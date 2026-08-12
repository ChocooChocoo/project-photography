# P-10 - SUBSCRIPTIONS

[[ANALYSIS - PLATINUM STUDIO PLATFORM/00 - START HERE|Back to start]] · [[ANALYSIS - PLATINUM STUDIO PLATFORM/11 - PARTS IN DETAIL|All parts]] · [[ANALYSIS - PLATINUM STUDIO PLATFORM/07 - DEVELOPMENT ROADMAP|Roadmap]]

**What it is for:** Controls studio plan state, expiry, grace, notices, and commercial access.
**Where it sits:** [[ANALYSIS - PLATINUM STUDIO PLATFORM/05 - SYSTEM ARCHITECTURE#The parts]]
**Built by:** R-19 in [[ANALYSIS - PLATINUM STUDIO PLATFORM/07 - DEVELOPMENT ROADMAP|the roadmap]]
**Status:** Evidence-backed status is recorded in the parts index.

## Why it exists

Controls studio plan state, expiry, grace, notices, and commercial access. It is a distinct part because its rules, people, or state can change without changing every other part. Source: Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php.

## What it does

| # | Job | Who asks for it | Source |
|---|---|---|---|
| 1 | Trial and paid records, expiry, seven-day grace, notifications, retained history, and blocked commercial writes. | The role or workflow that owns the action | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |
| 2 | Keeps the resulting state available to the next portal or workflow. | The next responsible part | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |

## Who may use it

| Who they are | What they may do | What they may not do | Evidence |
|---|---|---|---|
| The role named by the current portal | Use the action explicitly exposed to that role. | No action outside its role, permission, or studio scope. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |
| Administrator or owner where the routes explicitly allow it | Review or manage the records in scope. | No undocumented platform-wide override. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |

The material does not authorize a broader permission merely because it would be convenient. Any missing action boundary is Q-09 or the question named below.

## The information it handles

| Information | Purpose | Where it is kept | Source |
|---|---|---|---|
| Identity, role, or workflow record | Connects the action to a person and scope. | Relational application records. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |
| Current state and history | Shows what happened and what can happen next. | The part’s application records. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |
| Notification or external reference when present | Returns the responsible person to the next action. | Notification or provider records. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |

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

Trial → active or grace → expired; reactivation is not yet implemented

## What it checks before it agrees

| # | Check | If it fails | Evidence |
|---|---|---|---|
| 1 | The person is signed in and allowed for this portal. | Refuse or redirect the action. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |
| 2 | The record belongs to the permitted user, studio, or workflow. | Refuse the action. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |
| 3 | The input and current state allow the requested transition. | Leave the record unchanged and report the refusal. | Subscription controllers; expiry commands; EnforceStudioSubscriptionAccess.php |

## When something goes wrong

The current source exposes validation, authorization, state checks, provider fallbacks, or error responses where they exist. It does not supply a universal recovery policy for every partial failure. That limit is recorded in [[ANALYSIS - PLATINUM STUDIO PLATFORM/04 - COMBINED FINDINGS|combined findings]] rather than filled with a generic assumption.

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
| Q-02 | What are renewal, failed-payment, cancellation, card-on-file, and reactivation rules? | The part cannot safely promise this behavior until the decision is answered. |
