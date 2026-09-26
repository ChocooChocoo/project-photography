# Can a new portal and a new access area be added, and how?

Date: 2026-09-23
Question owner: product/engineering
Status: answered in writing. No code was changed.

## Where this document lives

`docs/answers/` did not exist. The repository already has `docs/` as the
documentation home. It holds `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/`
and `docs/docs/visual-plans/`. There is no existing "answers" folder to
follow, so this file was created at the requested path,
`docs/answers/2026-09-23-new-portal-and-access-area.md`, inside the
existing `docs/` tree.

## Short answer

Yes. You can add both.

- A new **access area** is data. It is a `tbl_permissions` row with a
  portal, a resource, and an action. You add it by seeding. No migration
  is needed, because the columns already exist.
- A new **portal** is code. It needs a role string, a middleware, a route
  group, a layout, and a dashboard. You also need seed rows that give the
  new portal its roles and permissions.

Nothing in the twelve reported defects depends on this work. The question
is answered in writing and the code is left unchanged.

## Claims checked against the code

### Claim: access areas and their actions live in data, so adding one is a seed operation and needs no migration

Verified. A permission row carries a portal, a resource, and an action.

- `database/migrations/2026_03_25_233321_update_tbl_permissions_for_resource_action_protocol.php`
  adds `resource`, `action`, and `permission_string` to `tbl_permissions`.
- `database/migrations/2026_04_05_100000_add_portal_and_studio_scope_to_rbac_tables.php`
  (lines 37-41) adds the `portal` column to `tbl_permissions`.
- `database/seeders/StudioOwnerPermissionsSeeder.php` (lines 18-85)
  writes rows with `portal`, `resource`, `action`, and
  `permission_string`, using `updateOrInsert` (lines 94-106). This is an
  idempotent seed, not a schema change.

The base table created in
`database/migrations/2026_03_20_174219_create_tbl_permissions_table.php`
only has `name`, `description`, and `status`. The portal, resource, and
action columns were added later and are present now. So a new access area
needs no migration today.

### Claim: `app/Models/PermissionModel.php` holds `PORTAL_LABELS` and `KNOWN_PORTALS` around line 33

Partly wrong. Correction below.

- The path is wrong. The file is
  `app/Models/StudioOwner/PermissionModel.php`. There is no
  `app/Models/PermissionModel.php`.
- `PORTAL_LABELS` is at line 18, not around line 33. It has four entries:
  `owner`, `studio-hr`, `studio-finance`, `studio-photographer`.
- `KNOWN_PORTALS` is at line 33. It has seven entries: `owner`,
  `studio-hr`, `studio-finance`, `studio-photographer`, `admin`,
  `client`, `freelancer`.

Note: `PORTAL_LABELS` has fewer entries than `KNOWN_PORTALS`. The three
that are missing (`admin`, `client`, `freelancer`) fall back to a
humanized label through `getPortalLabel()` at line 313.

### Claim: the role string on `UserModel` selects the portal

Verified. `app/Models/UserModel.php` line 543 defines
`getPortalName()`, and it returns `$this->role` (line 545). Every portal
middleware also checks that same role string. For example,
`app/Http/Middleware/StudioHRMiddleware.php` line 26 calls
`$user->isStudioHr()`, which is `$this->role === 'studio-hr'`
(`UserModel.php` line 297).

### Claim: `bootstrap/app.php` lines 35-42 hold one middleware alias per portal

Verified. Line 35 opens `$middleware->alias([`. The portal aliases are
lines 36-42:

- `admin` -> `AdminMiddleware`
- `client` -> `ClientMiddleware`
- `owner` -> `OwnerMiddleware`
- `freelancer` -> `FreelancerMiddleware`
- `studio.photographer` -> `StudioPhotographerMiddleware`
- `studio.hr` -> `StudioHRMiddleware`
- `studio.finance` -> `StudioFinanceMiddleware`

Non-portal aliases continue from line 43.

### Claim: `routes/web.php` holds one route group per portal

Verified. There is one group for each portal:

- `admin` at line 85 (a `guest` login group also exists at line 31)
- `owner` at line 152
- `studio-hr` at line 356
- `studio-finance` at line 458
- `freelancer` at line 515
- `studio-photographer` at line 577
- `client` at line 646

Each group uses its portal middleware. The `owner`, `studio-hr`,
`studio-finance`, and `studio-photographer` groups also use
`subscription.access:manage`.

### Claim: a Blade shell/layout and a dashboard route per portal

Verified.

- Layout folders exist under `resources/views/layouts/`: `admin`,
  `client`, `freelancer`, `owner`, `studio-finance`, `studio-hr`,
  `studio-photographer`. Each has `app.blade.php`, `sidebar.blade.php`,
  `theme.blade.php`, and `topbar.blade.php`. A `studio-staff` folder also
  exists.
- A dashboard route exists for each portal: `admin` line 91, `owner`
  line 158, `studio-hr` line 362, `studio-finance` line 469,
  `freelancer` line 521, `studio-photographer` line 583, and `client`
  line 652. Four portals also have `/dashboard/filter` and
  `/dashboard/export` routes.

## Steps to add a hypothetical "Marketing" portal

Order matters. Do the code first, then the data, then the tests.

### Code

1. **Add the role string.** In `app/Models/UserModel.php`, add a portal
   check method next to `isStudioHr()` (line 295), for example
   `isMarketing()` that returns `$this->role === 'marketing'`. Add
   `'marketing'` to `getEmployeeRoles()` (line 329) if the role is a
   studio employee role. Check `getUserTypeFromRole()` (line 172) if the
   role needs a user type other than the default `customer`.
2. **Add the middleware.** Create
   `app/Http/Middleware/MarketingMiddleware.php`, modeled on
   `StudioHRMiddleware.php`. It must check `Auth::check()`, then call the
   new role check, then pass the request on.
3. **Register the alias.** In `bootstrap/app.php`, add
   `'marketing' => \App\Http\Middleware\MarketingMiddleware::class,` to
   the alias list (after line 42).
4. **Add the dashboard controller.** Create the controller for the
   marketing dashboard, following the pattern of
   `app/Http/Controllers/StudioHR/DashboardController.php`.
5. **Add the route group.** In `routes/web.php`, add
   `Route::prefix('marketing')->middleware([MarketingMiddleware::class, ...])->group(function () { ... })`
   with the dashboard route. Follow the `studio-hr` group at line 356.
6. **Add the layout.** Create `resources/views/layouts/marketing/` with
   `app.blade.php`, `sidebar.blade.php`, `theme.blade.php`, and
   `topbar.blade.php`, following the `studio-hr` layout folder.
7. **Add dashboard and page views.** Create the marketing views under
   `resources/views/marketing/`, including the dashboard.

### Data (seed)

8. **Add the portal to the model lists.** In
   `app/Models/StudioOwner/PermissionModel.php`, add `marketing` to
   `KNOWN_PORTALS` (line 33) and, optionally, a friendly name to
   `PORTAL_LABELS` (line 18).
9. **Seed the permissions.** In
   `database/seeders/StudioOwnerPermissionsSeeder.php`, add one row per
   access area in the `$permissions` array (line 18). Each row needs
   `portal`, `resource`, `action`, `permission_string`, and
   `description`. This is the new access area step, and it needs no
   migration.
10. **Seed the role row.** Add a marketing role in
    `database/seeders/StudioOwnerRolesSeeder.php` with
    `portal = 'marketing'`, following the `studio-hr-manager` role.
11. **Bind permissions to the role.** In
    `database/seeders/StudioOwnerRolePermissionSeeder.php`, add a
    `marketing-...` key to `$rolePermissions` (line 18) that lists the
    marketing permission strings.
12. **Run the seed.** Run the seeder chain
    (`php artisan db:seed`, which calls `FreshSeedSeeder` ->
    `RbacSeeder`), or run the RBAC seeders directly with
    `php artisan db:seed --class=Database\\Seeders\\RbacSeeder`.

### Tests

13. **Add feature tests.** Add a test that a marketing user reaches the
    marketing dashboard, and a test that a non-marketing user is
    rejected. Put them under `tests/Feature/` in a folder that matches
    the portal.

## How long the change is

- **Code:** steps 1-7. These are the bulk of the work. A new portal
  needs a middleware, a controller, a route group, a layout, and views.
- **Data seeding:** steps 8-12. These are small edits to existing
  seeders. They are the only steps for a new access area inside an
  existing portal.
- **Tests:** step 13. Small, but required.

An access area alone is data plus tests. A full portal is code, data, and
tests.

## What could not be verified

- No claim in the question was left unchecked. All claims were read in
  the current code.
- This document does not verify runtime behavior of a new portal. It
  describes the files that a new portal must touch, based on the current
  code. No portal was created to test the steps.
- The `docs/answers/` path was chosen because it was requested. There is
  no prior "answers" convention in this repository.
