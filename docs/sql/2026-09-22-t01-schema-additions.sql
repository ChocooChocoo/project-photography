-- =====================================================================
-- Platinum - T-01 schema additions
-- Use when you cannot run `php artisan migrate` on the production host.
--
-- HOW TO RUN (phpMyAdmin or any MySQL client):
--   1. Export a backup of the database first (phpMyAdmin > Export > Go).
--   2. Open the SQL tab on the application database.
--   3. Run section 0 first. It tells you what already exists.
--   4. Run sections 1 to 4, then section 6.
--   5. Run the companion file 2026-09-22-permission-string-repair.sql.
--   6. Run section 7 to verify.
--
-- RUN ORDER: 0 -> 1 -> 2 -> 3 -> 4 -> 6 -> companion file -> 7.
-- Sections 1, 2, 3, 4 and 6 are safe to re-run. Each one checks the
-- catalog before it changes anything, so a second run does nothing.
-- The companion file is safe to re-run too.
--
-- The permission string repair (data only) lives in the companion file:
--   docs/sql/2026-09-22-permission-string-repair.sql
-- Run that file after this one.
--
-- Target: MySQL 8.0+ / MariaDB 10.4+.
-- `ADD COLUMN IF NOT EXISTS` is MariaDB only, so the guards below use
-- information_schema plus a prepared statement. That works on both.
-- Tables must be InnoDB, otherwise the foreign keys are ignored.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 0. PRE-CHECK - what does this database already have?
-- Expect `present = 1` for an object that exists, `0` for one that is
-- still missing. Read all three result sets before you change anything.
-- ---------------------------------------------------------------------

-- 0.1 Expected columns.
SELECT e.obj AS table_name, e.member AS column_name,
       IF(c.COLUMN_NAME IS NULL, 0, 1) AS present
FROM (
  SELECT 'tbl_studio_online_gallery' AS obj, 'approval_status' AS member UNION ALL
  SELECT 'tbl_studio_online_gallery', 'rejection_reason' UNION ALL
  SELECT 'tbl_studio_online_gallery', 'submitted_by' UNION ALL
  SELECT 'tbl_studio_online_gallery', 'submitted_at' UNION ALL
  SELECT 'tbl_studio_online_gallery', 'approved_by' UNION ALL
  SELECT 'tbl_studio_online_gallery', 'approved_at' UNION ALL
  SELECT 'tbl_studio_online_gallery', 'rejected_by' UNION ALL
  SELECT 'tbl_studio_online_gallery', 'rejected_at' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'approval_status' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'rejection_reason' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'submitted_by' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'submitted_at' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'approved_by' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'approved_at' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'rejected_by' UNION ALL
  SELECT 'tbl_freelancer_online_gallery', 'rejected_at' UNION ALL
  SELECT 'tbl_users', 'remember_token'
) e
LEFT JOIN information_schema.COLUMNS c
  ON c.TABLE_SCHEMA = DATABASE()
 AND c.TABLE_NAME = e.obj
 AND c.COLUMN_NAME = e.member
ORDER BY e.obj, e.member;

-- 0.2 Expected table.
SELECT 'sessions' AS object_name,
       IF(t.TABLE_NAME IS NULL, 0, 1) AS present
FROM (SELECT 'sessions' AS TABLE_NAME) x
LEFT JOIN information_schema.TABLES t
  ON t.TABLE_SCHEMA = DATABASE()
 AND t.TABLE_NAME = x.TABLE_NAME;

-- 0.3 Migration bookkeeping rows. A row with `present = 1` is already
-- recorded, so the matching step can be skipped.
SELECT e.migration, IF(m.migration IS NULL, 0, 1) AS present
FROM (
  SELECT '2026_09_22_090100_add_gallery_approval_columns' AS migration UNION ALL
  SELECT '2026_09_22_090200_normalize_permission_strings' UNION ALL
  SELECT '2026_09_22_120510_create_sessions_table' UNION ALL
  SELECT '2026_09_22_130000_add_remember_token_to_tbl_users'
) e
LEFT JOIN `migrations` m ON m.migration = e.migration
ORDER BY e.migration;

-- 0.4 Last five applied batches, for context only.
SELECT migration, batch FROM `migrations` ORDER BY id DESC LIMIT 5;


-- ---------------------------------------------------------------------
-- 1. SESSION TABLE
-- Required because the application uses SESSION_DRIVER=database.
-- Without this table every request starts a new session.
-- Safe to re-run: CREATE TABLE IF NOT EXISTS.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2. REMEMBER ME
-- Without this column a login with "Remember me" ticked returns HTTP 500.
-- Safe to re-run: the column is added only when it is absent.
-- ---------------------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'remember_token');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_users` ADD COLUMN `remember_token` varchar(100) DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;


-- ---------------------------------------------------------------------
-- 3. GALLERY OWNER-APPROVAL COLUMNS - studio galleries
-- Safe to re-run: every column and foreign key is added only when absent.
-- Order matters: approval_status is added before rejection_reason.
-- ---------------------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'approval_status');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `approval_status` enum(''pending'',''approved'',''rejected'',''cancelled'') DEFAULT NULL AFTER `gallery_status`',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'rejection_reason');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `rejection_reason` text AFTER `approval_status`',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'submitted_by');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `submitted_by` bigint unsigned DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'submitted_at');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `submitted_at` timestamp NULL DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'approved_by');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `approved_by` bigint unsigned DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'approved_at');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `approved_at` timestamp NULL DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'rejected_by');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `rejected_by` bigint unsigned DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND COLUMN_NAME = 'rejected_at');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD COLUMN `rejected_at` timestamp NULL DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND CONSTRAINT_NAME = 'tbl_studio_online_gallery_submitted_by_foreign');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD CONSTRAINT `tbl_studio_online_gallery_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND CONSTRAINT_NAME = 'tbl_studio_online_gallery_approved_by_foreign');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD CONSTRAINT `tbl_studio_online_gallery_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_studio_online_gallery' AND CONSTRAINT_NAME = 'tbl_studio_online_gallery_rejected_by_foreign');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_studio_online_gallery` ADD CONSTRAINT `tbl_studio_online_gallery_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;


-- ---------------------------------------------------------------------
-- 4. GALLERY OWNER-APPROVAL COLUMNS - freelancer galleries
-- Safe to re-run: every column and foreign key is added only when absent.
-- Order matters: approval_status is added before rejection_reason.
-- ---------------------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'approval_status');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `approval_status` enum(''pending'',''approved'',''rejected'',''cancelled'') DEFAULT NULL AFTER `gallery_status`',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'rejection_reason');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `rejection_reason` text AFTER `approval_status`',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'submitted_by');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `submitted_by` bigint unsigned DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'submitted_at');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `submitted_at` timestamp NULL DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'approved_by');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `approved_by` bigint unsigned DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'approved_at');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `approved_at` timestamp NULL DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'rejected_by');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `rejected_by` bigint unsigned DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND COLUMN_NAME = 'rejected_at');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD COLUMN `rejected_at` timestamp NULL DEFAULT NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND CONSTRAINT_NAME = 'tbl_freelancer_online_gallery_submitted_by_foreign');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD CONSTRAINT `tbl_freelancer_online_gallery_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND CONSTRAINT_NAME = 'tbl_freelancer_online_gallery_approved_by_foreign');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD CONSTRAINT `tbl_freelancer_online_gallery_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_freelancer_online_gallery' AND CONSTRAINT_NAME = 'tbl_freelancer_online_gallery_rejected_by_foreign');
SET @s := IF(@c = 0,
  'ALTER TABLE `tbl_freelancer_online_gallery` ADD CONSTRAINT `tbl_freelancer_online_gallery_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL',
  'DO 0');
PREPARE w7 FROM @s; EXECUTE w7; DEALLOCATE PREPARE w7;


-- ---------------------------------------------------------------------
-- 5. PERMISSION STRING REPAIR - moved to the companion file
-- File: docs/sql/2026-09-22-permission-string-repair.sql
-- It holds the preview, the repair, the duplicate check, and its own
-- migration bookkeeping row. Run it after this file.
-- ---------------------------------------------------------------------


-- ---------------------------------------------------------------------
-- 6. MIGRATION BOOKKEEPING
-- Without these rows a later `php artisan migrate` tries to run the same
-- files again and fails with "duplicate column".
-- Safe to re-run: each row is inserted only when it is absent.
-- The row for 2026_09_22_090200_normalize_permission_strings is inserted
-- by the companion file, section 5.4. Do not insert it here.
-- ---------------------------------------------------------------------
SET @next_batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_090100_add_gallery_approval_columns', @next_batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_090100_add_gallery_approval_columns');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_120510_create_sessions_table', @next_batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_120510_create_sessions_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_130000_add_remember_token_to_tbl_users', @next_batch FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_130000_add_remember_token_to_tbl_users');


-- ---------------------------------------------------------------------
-- 7. POST-CHECK
-- Compare the result against the migration shape: approval_status is an
-- enum of four values, the *_at columns are timestamp, the *_by columns
-- are bigint unsigned, and everything is nullable.
-- ---------------------------------------------------------------------
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'remember_token')
    OR (TABLE_NAME = 'sessions')
    OR (TABLE_NAME IN ('tbl_studio_online_gallery', 'tbl_freelancer_online_gallery')
        AND COLUMN_NAME IN ('approval_status', 'rejection_reason', 'submitted_by', 'submitted_at',
                            'approved_by', 'approved_at', 'rejected_by', 'rejected_at')))
ORDER BY TABLE_NAME, COLUMN_NAME;

SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('tbl_studio_online_gallery', 'tbl_freelancer_online_gallery')
  AND REFERENCED_TABLE_NAME = 'tbl_users'
ORDER BY TABLE_NAME, COLUMN_NAME;

SELECT migration, batch FROM `migrations` WHERE migration LIKE '2026_09_22%' ORDER BY batch, migration;
