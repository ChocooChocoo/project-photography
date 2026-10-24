← [Previous: Phase 1](./phase-1.md) | [Next: Phase 3](./phase-3.md) →

# Phase 2: Staff Limit Enforcement (High)

**Status:** ✅ Complete (photographer-form and invitation-accept guards untested)

**Goal:** Block every path that adds staff to a studio once the studio's plan limit is reached (5 on the ₱27,900/year plan), so paid limits cannot be bypassed.

## Dependencies
Phase 1 (stable admin and subscription pages).

## Tasks
- [x] `staff_limit` column exists on `tbl_subscription_plans` (`database/migrations/2026_02_19_094538_add_additional_fields_to_tbl_subscription_plans.php:17`).
- [x] A limit pattern exists to copy: `app/Http/Middleware/CheckStudioRegistrationLimit.php:36-105` and `StudiosModel::currentAccessSubscription()` (`:121`).
- [x] Confirm in the live data which plan the studio in the report uses. The seed names the ₱27,900 plan "Studio Growth Annual" (`database/seeders/Fresh/FreshMarketplaceSeeder.php:146`), while "Meridian" is a studio name. **Finding:** that plan has `staff_limit` 5. Seeded Meridian (catalog index 0) gets Studio Starter (`staff_limit` 3), and already holds more than 3 staff. Live DB not inspected.
- [x] Add integer casts for `staff_limit` and `max_studio_photographers` in `SubscriptionPlanModel`.
- [x] Add one shared check (a small service, for example `StudioStaffLimit`) that returns used, limit, and whether one more is allowed. Decide and document what counts as staff: employees, studio photographers, and approved freelancer members.
- [x] Apply the check to every creator path:
  - [x] `StudioOwner/EmployeeController@store` (`:132-250`)
  - [x] `StudioHR/EmployeeController@store` (`:151`)
  - [x] `StudioOwner/StudioPhotographersController@store` (`:94`)
  - [x] Reactivation in `updateStatus` of both employee controllers (`routes/web.php:281, 395`)
  - [x] `Freelancer/MemberInvitationController@accept` (`:72-96`) if freelancers count as staff
  - [x] `StudioOwner/RoleController@updateUserRoles` (`:57`) when it attaches studio roles to a new person — no guard needed: it only syncs roles for users already in the studio (`getStudioUserIds` intersect), so it adds no staff
- [x] Return a clear 422 message naming the plan limit. Show remaining slots on the owner and HR employee pages.
- [x] Tests in `tests/Feature/StudioEmployee/`: staff 1 to 5 succeed, the 6th is rejected on each path, reactivation over the limit is rejected, and a plan with a higher limit allows more.

## Implementation notes
- Staff = active users that are studio employees (`tbl_user_roles`), studio photographers, or approved freelancer members. Null `staff_limit` = unlimited. Studios with no current paid plan are not limited (same as the registration middleware).
- Shared check: `app/Services/StudioStaffLimit.php` (`summary`, `violation`). Guards return JSON 422. Slot badges sit on both employee list pages.
- Tests: `tests/Feature/StudioEmployee/EmployeeProvisioningTest.php` (owner/HR 6th rejected, higher limit allows, reactivation).

## Done when
- [x] The sixth staff account is rejected from the owner portal, HR portal, and photographer form.
- [x] Reactivating a deactivated staff account over the limit is rejected.
- [x] Tests for all paths pass.

← [Previous: Phase 1](./phase-1.md) | [Next: Phase 3](./phase-3.md) →
