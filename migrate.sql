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

-- 3. Add general_objectives column (Section 4b)
ALTER TABLE `submissions`
  ADD COLUMN IF NOT EXISTS `general_objectives` LONGTEXT DEFAULT NULL
  AFTER `specific_objectives`;

-- 4. Email log table for paper calls
CREATE TABLE IF NOT EXISTS `paper_call_email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paper_call_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_msg` text DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `paper_call_id` (`paper_call_id`),
  CONSTRAINT `fk_pcel_pc` FOREIGN KEY (`paper_call_id`) REFERENCES `paper_calls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
