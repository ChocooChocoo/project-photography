← [Previous: Phase 2](./phase-2.md) | [Next: Phase 4](./phase-4.md) →

# Phase 3: HR Online Gallery Permissions (High / Critical)

**Status:** ✅ Done (live-DB check pending, see findings)

**Goal:** Make the permissions the owner sets for the HR Manager match real access. Unchecked actions return 403 and hide controls. Checked actions work.

## Dependencies
Phase 1.

## Tasks
- [x] HR keys `studio-hr.online-gallery.view` and `.manage` exist (`database/seeders/StudioOnlineGalleryPermissionSeeder.php:26-27`).
- [x] The photographer portal already checks view, create, update, and delete per route (`routes/web.php:624-631`). Use it as the pattern.
- [x] Add HR keys `studio-hr.online-gallery.create`, `.update`, `.delete` to the seeder and to the normalizer (`app/Models/StudioOwner/PermissionModel.php:149-216`).
- [x] Change the HR gallery routes (`routes/web.php:444-454`) so each action checks its own key (view, create/upload, update/publish/submit, delete, delete-image). Keep `owner.online-gallery.manage` as an allowed alternative only where intended.
- [x] Pass permission flags from `StudioHR/OnlineGalleryController@index` (`:34-73`) to `resources/views/studio-hr/view-online-gallery.blade.php` and hide delete, upload and create controls the user may not use.
- [x] Owner role editor: group permissions by portal as well as resource in `resources/views/owner/view-roles.blade.php:564-578`, so "Online Gallery" for HR, owner and photographer are not mixed in one list.
- [x] Investigate why checked Manage, Create and View do not grant access in the live system. Check: the `studio-hr.online-gallery.*` rows exist in live `tbl_permissions`, `EnforceStudioSubscriptionAccess` (`:72-94`) does not block HR writes, and `tbl_user_roles.studio_id` is set. Record findings here.
  - Findings (code, 2026-10-07): (1) HR routes gated every write on `.manage`, so an unchecked Delete could not block anything. (2) No HR create/update/delete rows existed; the role picker mixed HR, owner and photographer "Online Gallery" rows in one list, so owners ticked photographer-portal Create/Delete that HR routes never read. (3) `EnforceStudioSubscriptionAccess` does not name HR gallery routes; HR writes pass only when the studio subscription is accessible. Not verifiable from code: live `tbl_permissions` rows and `tbl_user_roles.studio_id`. Run `docs/sql/2026-10-07-hr-gallery-permissions-repair.sql` section 3 on live and confirm.
- [x] Add a SQL repair script under `docs/sql/` that inserts missing HR gallery permissions and links them to `studio-hr-manager` only when the owner enabled them.
- [x] Extend `tests/Feature/Rbac/CrossPortalGalleryPermissionTest.php`: delete without the key returns 403, the controls are absent in the view, and manage, create and view grant full use.

## Done when
- [x] With "Delete Online Gallery" unchecked, an HR delete request returns 403 and no delete button renders.
- [x] With Manage, Create and View checked, the HR Manager can list, create, upload and publish galleries.
- [x] Tests pass.

← [Previous: Phase 2](./phase-2.md) | [Next: Phase 4](./phase-4.md) →
