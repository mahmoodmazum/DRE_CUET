-- ============================================================
-- DRE Portal Migration — Run this in phpMyAdmin or MySQL CLI
-- ============================================================

-- 1. Add 'methodology' type to submission_attachments
ALTER TABLE `submission_attachments`
  MODIFY COLUMN `type`
  ENUM('l_rev','appendA','appendB','appendC','methodology')
  DEFAULT NULL;

-- 2. Add confirm_name column to submissions (stores full name declaration)
ALTER TABLE `submissions`
  ADD COLUMN `confirm_name` VARCHAR(255) DEFAULT NULL
  AFTER `ip_ownership`;

-- ============================================================
-- Notes on data migration:
-- • Existing submissions: staff_costs and direct_expenses stored
--   with old {category, year, amount} structure are still read
--   correctly by the updated view pages (backward-compatible).
-- • New submissions will use {category, year1, year2, year3} structure.
-- • The 'l_rev' attachment type now maps to Section 5c
--   (Literature Review & Related Research file upload).
-- • The 'methodology' attachment type is new — Section 14 file.
-- ============================================================
