-- =====================================================================
-- Platinum - Phase 5 remove minimum and maximum budget
--
-- WHAT THIS CHANGES
-- Client budgets now use only preferred_budget. This script:
--   1. fills any empty preferred_budget from the old maximum, then minimum,
--   2. drops the minimum/maximum index and columns,
--   3. makes preferred_budget required.
--
-- HOW TO RUN
--   1. Backup the database first.
--   2. Run 1, then 2 (it checks the schema first), then 3, then 4 (check).
--   Step 2 is a prepared statement so it is safe to re-run.
--
-- Target: MySQL 8.0+ / MariaDB 10.4+.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. Backfill preferred_budget (skips if the old columns are already gone)
-- ---------------------------------------------------------------------
SET @has_cols = (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_client_budget'
                   AND COLUMN_NAME IN ('minimum_budget', 'maximum_budget'));
SET @sql = IF(@has_cols = 2,
    'UPDATE tbl_client_budget SET preferred_budget = COALESCE(maximum_budget, minimum_budget, 0) WHERE preferred_budget IS NULL',
    'UPDATE tbl_client_budget SET preferred_budget = 0 WHERE preferred_budget IS NULL');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------
-- 2. Drop the index and columns (each guarded)
-- ---------------------------------------------------------------------
SET @has_idx = (SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_client_budget'
                  AND INDEX_NAME = 'tbl_client_budget_minimum_budget_maximum_budget_index');
SET @sql = IF(@has_idx > 0,
    'ALTER TABLE tbl_client_budget DROP INDEX tbl_client_budget_minimum_budget_maximum_budget_index',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_min = (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_client_budget' AND COLUMN_NAME = 'minimum_budget');
SET @sql = IF(@has_min > 0, 'ALTER TABLE tbl_client_budget DROP COLUMN minimum_budget', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_max = (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_client_budget' AND COLUMN_NAME = 'maximum_budget');
SET @sql = IF(@has_max > 0, 'ALTER TABLE tbl_client_budget DROP COLUMN maximum_budget', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------
-- 3. Make preferred_budget required
-- ---------------------------------------------------------------------
ALTER TABLE tbl_client_budget
    MODIFY preferred_budget DECIMAL(10,2) NOT NULL COMMENT 'Preferred/ideal budget amount';


-- ---------------------------------------------------------------------
-- 4. Check: expect no minimum/maximum columns and preferred_budget IS_NULLABLE = NO
-- ---------------------------------------------------------------------
SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_client_budget'
  AND COLUMN_NAME IN ('minimum_budget', 'maximum_budget', 'preferred_budget');
