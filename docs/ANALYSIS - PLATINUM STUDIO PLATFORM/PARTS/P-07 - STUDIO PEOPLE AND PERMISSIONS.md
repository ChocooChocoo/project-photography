# P-07 - STUDIO PEOPLE AND PERMISSIONS

[Back to start](../00%20-%20START%20HERE.md) · [All parts](../11%20-%20PARTS%20IN%20DETAIL.md) · [Roadmap](../07%20-%20DEVELOPMENT%20ROADMAP.md)

**What it is for:** Manages staff membership, roles, permissions, and studio-specific responsibility.
**Where it sits:** [05 - SYSTEM ARCHITECTURE](../05%20-%20SYSTEM%20ARCHITECTURE.md#the-parts)
**Built by:** R-09, R-12 in [the roadmap](../07%20-%20DEVELOPMENT%20ROADMAP.md)
**Status:** Evidence-backed status is recorded in the parts index.

## Why it exists

Manages staff membership, roles, permissions, and studio-specific responsibility. It is a distinct part because its rules, people, or state can change without changing every other part. Source: UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles.

## What it does

| # | Job | Who asks for it | Source |
|---|---|---|---|
| 1 | Invites, employees, roles, permissions, and studio assignment. | The role or workflow that owns the action | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |
| 2 | Keeps the resulting state available to the next portal or workflow. | The next responsible part | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |

## Who may use it

| Who they are | What they may do | What they may not do | Evidence |
|---|---|---|---|
| The role named by the current portal | Use the action explicitly exposed to that role. | No action outside its role, permission, or studio scope. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |
| Administrator or owner where the routes explicitly allow it | Review or manage the records in scope. | No undocumented platform-wide override. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |

The material does not authorize a broader permission merely because it would be convenient. Any missing action boundary is Q-09 or the question named below.

## The information it handles

| Information | Purpose | Where it is kept | Source |
|---|---|---|---|
| Identity, role, or workflow record | Connects the action to a person and scope. | Relational application records. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |
| Current state and history | Shows what happened and what can happen next. | The part’s application records. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |
| Notification or external reference when present | Returns the responsible person to the next action. | Notification or provider records. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |

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

Invited → active or cancelled; role active → inactive

## What it checks before it agrees

| # | Check | If it fails | Evidence |
|---|---|---|---|
| 1 | The person is signed in and allowed for this portal. | Refuse or redirect the action. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |
| 2 | The record belongs to the permitted user, studio, or workflow. | Refuse the action. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |
| 3 | The input and current state allow the requested transition. | Leave the record unchanged and report the refusal. | UserModel.php; RoleModel.php; role/member controllers; tbl_user_roles |

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
| Q-09 | Which role variants may perform each cross-portal action? | The part cannot safely promise this behavior until the decision is answered. |
