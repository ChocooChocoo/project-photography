-- =====================================================================
-- Platinum - T-01 permission string repair (data only, no schema change)
--
-- Companion file to 2026-09-22-t01-schema-additions.sql.
-- Run that one first: it creates tables and columns.
--
-- WHAT THIS FIXES
-- The role screens store permissions as "portal.resource.action"
-- (for example owner.online-gallery.manage). When a stored value uses a
-- different shape, a saved role stops matching what the permission
-- middleware checks. This repair rewrites the stored value into the
-- canonical form.
--
-- HOW TO RUN
--   1. Backup the database first.
--   2. Run 5.1. It lists every row whose stored value differs.
--   3. Run 5.2. It writes the canonical value.
--   4. Run 5.3. If it returns rows, stop and send the ids to the
--      developer before deleting anything.
--   5. Run 5.4 to record the migration row.
--
-- Safe to re-run: a second run finds canonical values and updates nothing.
-- Target: MySQL 8.0+ / MariaDB 10.4+ (uses REGEXP_REPLACE).
--
-- PROD ENVIRONMENT (.env) REQUIRED BY THIS FIX SET
--   SESSION_DRIVER=database     needs the `sessions` table
--   SESSION_BLOCK=true          one request holds the session lock
-- Both values are already in .env.example. The `sessions` table SQL is in
-- 2026-09-22-t01-schema-additions.sql, section 1.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 5.1 PREVIEW - nothing is written. Read the `canonical` column.
-- ---------------------------------------------------------------------
SELECT p.id,
       p.portal,
       p.resource,
       p.action,
       p.permission_string AS stored_value,
       c.canonical
FROM tbl_permissions p
JOIN (
  SELECT d.id,
    CASE
      WHEN segr <> '' AND sega <> '' THEN
        CONCAT(
          CASE
            WHEN segp <> '' THEN segp
            WHEN SUBSTRING_INDEX(full_norm, '.', 1) IN ('owner','studio-hr','studio-finance','studio-photographer')
              THEN SUBSTRING_INDEX(full_norm, '.', 1)
            ELSE 'owner'
          END,
          '.', segr, '.', sega)
      WHEN SUBSTRING_INDEX(full_norm, '.', 1) = 'portal' THEN
        CONCAT(
          COALESCE(NULLIF(segp, ''), 'owner'),
          '.',
          SUBSTRING(full_norm, CHAR_LENGTH(SUBSTRING_INDEX(full_norm, '.', 1)) + 2))
      WHEN SUBSTRING_INDEX(full_norm, '.', 1) IN ('owner','studio-hr','studio-finance','studio-photographer') THEN full_norm
      ELSE CONCAT('portal.', full_norm)
    END AS canonical
  FROM (
    SELECT id, portal, resource, action, permission_string,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(portal,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS segp,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(resource,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS segr,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(action,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS sega,
      TRIM(BOTH '.-' FROM REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(REPLACE(LOWER(TRIM(COALESCE(permission_string,''))), ':', '.'), '[^a-z0-9.]+', '-'), '-+', '-'), '[.]+', '.')) AS full_norm
    FROM tbl_permissions
  ) d
) c ON c.id = p.id
WHERE p.permission_string <> c.canonical;


-- ---------------------------------------------------------------------
-- 5.2 REPAIR - writes the canonical value shown by 5.1.
-- Keep this statement byte-identical to 5.1 except for the outer command,
-- so the preview and the write use the same rule.
-- ---------------------------------------------------------------------
UPDATE tbl_permissions p
JOIN (
  SELECT d.id,
    CASE
      WHEN segr <> '' AND sega <> '' THEN
        CONCAT(
          CASE
            WHEN segp <> '' THEN segp
            WHEN SUBSTRING_INDEX(full_norm, '.', 1) IN ('owner','studio-hr','studio-finance','studio-photographer')
              THEN SUBSTRING_INDEX(full_norm, '.', 1)
            ELSE 'owner'
          END,
          '.', segr, '.', sega)
      WHEN SUBSTRING_INDEX(full_norm, '.', 1) = 'portal' THEN
        CONCAT(
          COALESCE(NULLIF(segp, ''), 'owner'),
          '.',
          SUBSTRING(full_norm, CHAR_LENGTH(SUBSTRING_INDEX(full_norm, '.', 1)) + 2))
      WHEN SUBSTRING_INDEX(full_norm, '.', 1) IN ('owner','studio-hr','studio-finance','studio-photographer') THEN full_norm
      ELSE CONCAT('portal.', full_norm)
    END AS canonical
  FROM (
    SELECT id, portal, resource, action, permission_string,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(portal,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS segp,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(resource,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS segr,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(action,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS sega,
      TRIM(BOTH '.-' FROM REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(REPLACE(LOWER(TRIM(COALESCE(permission_string,''))), ':', '.'), '[^a-z0-9.]+', '-'), '-+', '-'), '[.]+', '.')) AS full_norm
    FROM tbl_permissions
  ) d
) c ON c.id = p.id
SET p.permission_string = c.canonical
WHERE p.permission_string <> c.canonical;


-- ---------------------------------------------------------------------
-- 5.3 DUPLICATE CHECK - two rows that resolve to the same canonical
-- string must be merged by hand: move the role links, then delete the
-- extra row. Do not delete anything before reporting the ids.
-- Expect no rows on a clean database.
-- ---------------------------------------------------------------------
SELECT c.canonical, COUNT(*) AS rows_with_same_string, GROUP_CONCAT(c.id ORDER BY c.id) AS permission_ids
FROM (
  SELECT d.id,
    CASE
      WHEN segr <> '' AND sega <> '' THEN
        CONCAT(
          CASE
            WHEN segp <> '' THEN segp
            WHEN SUBSTRING_INDEX(full_norm, '.', 1) IN ('owner','studio-hr','studio-finance','studio-photographer')
              THEN SUBSTRING_INDEX(full_norm, '.', 1)
            ELSE 'owner'
          END,
          '.', segr, '.', sega)
      WHEN SUBSTRING_INDEX(full_norm, '.', 1) = 'portal' THEN
        CONCAT(
          COALESCE(NULLIF(segp, ''), 'owner'),
          '.',
          SUBSTRING(full_norm, CHAR_LENGTH(SUBSTRING_INDEX(full_norm, '.', 1)) + 2))
      WHEN SUBSTRING_INDEX(full_norm, '.', 1) IN ('owner','studio-hr','studio-finance','studio-photographer') THEN full_norm
      ELSE CONCAT('portal.', full_norm)
    END AS canonical
  FROM (
    SELECT id, portal, resource, action, permission_string,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(portal,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS segp,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(resource,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS segr,
      TRIM(BOTH '-' FROM REGEXP_REPLACE(REGEXP_REPLACE(LOWER(TRIM(COALESCE(action,''))), '[^a-z0-9]+', '-'), '-+', '-')) AS sega,
      TRIM(BOTH '.-' FROM REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(REPLACE(LOWER(TRIM(COALESCE(permission_string,''))), ':', '.'), '[^a-z0-9.]+', '-'), '-+', '-'), '[.]+', '.')) AS full_norm
    FROM tbl_permissions
  ) d
) c
GROUP BY c.canonical
HAVING COUNT(*) > 1;


-- ---------------------------------------------------------------------
-- 5.4 BOOKKEEPING - run only after 5.2. Without this row a later
-- `php artisan migrate` tries to run the same repair again.
-- ---------------------------------------------------------------------
SET @next_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_09_22_090200_normalize_permission_strings', @next_batch);


-- ---------------------------------------------------------------------
-- 5.5 POST-CHECK - expect zero rows.
-- ---------------------------------------------------------------------
SELECT id, portal, resource, action, permission_string
FROM tbl_permissions
WHERE permission_string NOT REGEXP '^[a-z0-9-]+(\\.[a-z0-9-]+)*$'
   OR permission_string LIKE '.%' OR permission_string LIKE '%.';
