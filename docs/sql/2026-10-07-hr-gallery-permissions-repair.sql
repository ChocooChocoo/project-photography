-- =====================================================================
-- Platinum - Phase 3 HR online gallery permissions repair (data only)
--
-- WHAT THIS FIXES
-- The HR gallery routes now check one key per action:
--   view, create (upload), update (edit/publish/submit), delete.
-- Live databases only hold studio-hr.online-gallery.view and .manage.
-- This script adds the missing create, update and delete rows, and links
-- create and update to every role that already has the HR manage grant.
-- Delete is never linked here: the owner must tick Delete Online Gallery
-- in the role editor.
--
-- HOW TO RUN
--   1. Backup the database first.
--   2. Run 1 (insert rows), then 2 (link), then 3 (check).
--
-- Safe to re-run: every insert is guarded by NOT EXISTS.
-- Target: MySQL 8.0+ / MariaDB 10.4+.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. Insert the missing permission rows
-- ---------------------------------------------------------------------
INSERT INTO tbl_permissions (name, portal, resource, action, permission_string, description, status, created_at, updated_at)
SELECT v.name, 'studio-hr', 'online-gallery', v.action, v.permission_string, v.description, 'active', NOW(), NOW()
FROM (
    SELECT 'studio_hr_create_online_gallery' AS name, 'create' AS action, 'studio-hr.online-gallery.create' AS permission_string, 'Create galleries and upload images for the assigned studio.' AS description
    UNION ALL
    SELECT 'studio_hr_update_online_gallery', 'update', 'studio-hr.online-gallery.update', 'Update, publish, and submit online galleries for the assigned studio.'
    UNION ALL
    SELECT 'studio_hr_delete_online_gallery', 'delete', 'studio-hr.online-gallery.delete', 'Delete online galleries and gallery images for the assigned studio.'
) AS v
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_permissions p
    WHERE p.permission_string = v.permission_string AND p.deleted_at IS NULL
)
AND NOT EXISTS (
    SELECT 1 FROM tbl_permissions p WHERE p.name = v.name
);


-- ---------------------------------------------------------------------
-- 2. Link create and update to roles that already hold HR manage
--    (mirrors what the owner already enabled; delete is not linked)
-- ---------------------------------------------------------------------
INSERT INTO tbl_role_permissions (role_id, permission_id, created_at, updated_at)
SELECT rp.role_id, np.id, NOW(), NOW()
FROM tbl_role_permissions rp
JOIN tbl_permissions mp
    ON mp.id = rp.permission_id
   AND mp.permission_string = 'studio-hr.online-gallery.manage'
   AND mp.deleted_at IS NULL
JOIN tbl_permissions np
    ON np.permission_string IN ('studio-hr.online-gallery.create', 'studio-hr.online-gallery.update')
   AND np.deleted_at IS NULL
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_role_permissions x
    WHERE x.role_id = rp.role_id AND x.permission_id = np.id
);


-- ---------------------------------------------------------------------
-- 3. CHECK - expect one row each for view, manage, create, update and
--    delete, and copies = 1 on every row.
-- ---------------------------------------------------------------------
SELECT permission_string, COUNT(*) AS copies
FROM tbl_permissions
WHERE permission_string LIKE 'studio-hr.online-gallery.%' AND deleted_at IS NULL
GROUP BY permission_string;
