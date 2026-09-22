-- =====================================================================
-- Platinum - T-01 schema additions
-- Use when you cannot run `php artisan migrate` on the production host.
--
-- HOW TO RUN (phpMyAdmin or any MySQL client):
--   1. Export a backup of the database first (phpMyAdmin > Export > Go).
--   2. Open the SQL tab on the application database.
--   3. Run section 0 first. It tells you what already exists.
--   4. Run sections 1 to 4, then section 6.
--   5. Run section 7 to verify.
--
-- The permission string repair (data only) lives in the companion file:
--   docs/sql/2026-09-22-permission-string-repair.sql
-- Run that file after this one.
--
-- Target: MySQL 8.0+ / MariaDB 10.4+.
-- Tables must be InnoDB, otherwise the foreign keys are ignored.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 0. PRE-CHECK - what does this database already have?
-- ---------------------------------------------------------------------
SELECT 'sessions table' AS item,
       COUNT(*) AS present
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sessions'
UNION ALL
SELECT 'tbl_users.remember_token',
       COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_users' AND COLUMN_NAME = 'remember_token'
UNION ALL
SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME),
       COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('tbl_studio_online_gallery', 'tbl_freelancer_online_gallery')
  AND COLUMN_NAME IN ('approval_status', 'rejection_reason', 'submitted_by', 'submitted_at',
                      'approved_by', 'approved_at', 'rejected_by', 'rejected_at')
GROUP BY TABLE_NAME, COLUMN_NAME;

SELECT migration, batch FROM migrations ORDER BY id DESC LIMIT 5;


-- ---------------------------------------------------------------------
-- 1. SESSION TABLE
-- Required because the application uses SESSION_DRIVER=database.
-- Without this table every request starts a new session.
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
-- ---------------------------------------------------------------------
ALTER TABLE `tbl_users`
  ADD COLUMN `remember_token` varchar(100) DEFAULT NULL;


-- ---------------------------------------------------------------------
-- 3. GALLERY OWNER-APPROVAL COLUMNS - studio galleries
-- ---------------------------------------------------------------------
ALTER TABLE `tbl_studio_online_gallery`
  ADD COLUMN `approval_status` enum('pending','approved','rejected','cancelled') DEFAULT NULL AFTER `gallery_status`,
  ADD COLUMN `rejection_reason` text AFTER `approval_status`,
  ADD COLUMN `submitted_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `submitted_at` timestamp NULL DEFAULT NULL,
  ADD COLUMN `approved_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `approved_at` timestamp NULL DEFAULT NULL,
  ADD COLUMN `rejected_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `rejected_at` timestamp NULL DEFAULT NULL,
  ADD CONSTRAINT `tbl_studio_online_gallery_submitted_by_foreign`
      FOREIGN KEY (`submitted_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tbl_studio_online_gallery_approved_by_foreign`
      FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tbl_studio_online_gallery_rejected_by_foreign`
      FOREIGN KEY (`rejected_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;


-- ---------------------------------------------------------------------
-- 4. GALLERY OWNER-APPROVAL COLUMNS - freelancer galleries
-- ---------------------------------------------------------------------
ALTER TABLE `tbl_freelancer_online_gallery`
  ADD COLUMN `approval_status` enum('pending','approved','rejected','cancelled') DEFAULT NULL AFTER `gallery_status`,
  ADD COLUMN `rejection_reason` text AFTER `approval_status`,
  ADD COLUMN `submitted_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `submitted_at` timestamp NULL DEFAULT NULL,
  ADD COLUMN `approved_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `approved_at` timestamp NULL DEFAULT NULL,
  ADD COLUMN `rejected_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `rejected_at` timestamp NULL DEFAULT NULL,
  ADD CONSTRAINT `tbl_freelancer_online_gallery_submitted_by_foreign`
      FOREIGN KEY (`submitted_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tbl_freelancer_online_gallery_approved_by_foreign`
      FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tbl_freelancer_online_gallery_rejected_by_foreign`
      FOREIGN KEY (`rejected_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;


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
-- The batch number continues from the current highest batch.
-- The row for 2026_09_22_090200_normalize_permission_strings is inserted
-- by the companion file, section 5.4. Do not insert it here.
-- ---------------------------------------------------------------------
SET @next_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_09_22_090100_add_gallery_approval_columns', @next_batch),
  ('2026_09_22_120510_create_sessions_table', @next_batch),
  ('2026_09_22_130000_add_remember_token_to_tbl_users', @next_batch);


-- ---------------------------------------------------------------------
-- 7. POST-CHECK
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

SELECT migration, batch FROM migrations WHERE migration LIKE '2026_09_22%' ORDER BY batch, migration;
