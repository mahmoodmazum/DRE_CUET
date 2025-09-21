-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2025 at 04:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cuet_apps`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_info`
--

CREATE TABLE `bank_info` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `routing_number` varchar(50) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `committee_pool`
--

CREATE TABLE `committee_pool` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `added_by` int(11) DEFAULT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_pool`
--

INSERT INTO `committee_pool` (`id`, `user_id`, `added_by`, `added_at`) VALUES
(2, 4, 3, '2025-09-19 19:12:49');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(2, 'Civil Engineering (CE)'),
(1, 'Computer Science and Engineering (CSE)');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-th',
  `url` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `name`, `slug`, `description`, `icon`, `url`, `is_active`) VALUES
(1, 'DRE Module', 'dre', 'Departmental Review & Evaluation', 'fa-chalkboard', 'http://localhost:8081/DRE/sso/verify.php', 1),
(2, 'Transport Module', 'transport', 'Transport Management', 'fa-bus', 'https://transport.example.com/sso/verify.php', 1);

-- --------------------------------------------------------

--
-- Table structure for table `paper_calls`
--

CREATE TABLE `paper_calls` (
  `id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `deadline_date` date NOT NULL,
  `review_deadline` date DEFAULT NULL,
  `message` text NOT NULL,
  `signature` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paper_calls`
--

INSERT INTO `paper_calls` (`id`, `issue_date`, `deadline_date`, `review_deadline`, `message`, `signature`, `created_by`, `created_at`) VALUES
(2, '2025-09-19', '2025-09-18', '2025-09-20', 'testkhlh\r\n', 'Regards,DRE', 3, '2025-09-19 19:14:30'),
(3, '2025-09-20', '2025-09-22', '2025-09-25', 'test', 'Regards,\r\nDRE', 3, '2025-09-20 01:28:23');

-- --------------------------------------------------------

--
-- Table structure for table `paper_call_attachments`
--

CREATE TABLE `paper_call_attachments` (
  `id` int(11) NOT NULL,
  `paper_call_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paper_call_attachments`
--

INSERT INTO `paper_call_attachments` (`id`, `paper_call_id`, `file_path`, `original_name`, `uploaded_at`) VALUES
(3, 2, 'uploads/paper_calls/pc_2_68cd5736737e3.txt', '045CNF.txt', '2025-09-19 19:14:30'),
(4, 2, 'uploads/paper_calls/pc_2_68cd5736740c5.docx', 'Registration Form(1) (2).docx', '2025-09-19 19:14:30'),
(5, 3, 'uploads/paper_calls/pc_3_68cdaed77e69a.pdf', 'AB Tender Submission Document_Edited 1.pdf', '2025-09-20 01:28:23');

-- --------------------------------------------------------

--
-- Table structure for table `reviewer_pool`
--

CREATE TABLE `reviewer_pool` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `external_name` varchar(191) DEFAULT NULL,
  `external_email` varchar(191) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviewer_pool`
--

INSERT INTO `reviewer_pool` (`id`, `user_id`, `external_name`, `external_email`, `added_by`, `added_at`) VALUES
(1, 3, NULL, NULL, 3, '2025-09-19 19:09:43'),
(3, NULL, 'Golam Mahmood', 'mahmoodmazum9@gmail.com', 3, '2025-09-19 21:06:13');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `submission_id`, `reviewer_id`, `comments`, `created_at`, `updated_at`) VALUES
(4, 5, 1, 'okkkkk', '2025-09-21 13:51:21', '2025-09-21 13:51:21'),
(5, 5, 3, 'okkkkk', '2025-09-21 13:51:21', '2025-09-21 13:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `review_marks`
--

CREATE TABLE `review_marks` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `criterion_index` int(11) NOT NULL,
  `allocated_marks` int(11) NOT NULL,
  `evaluated_marks` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `paper_call_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `phase` varchar(100) DEFAULT NULL,
  `project_title` varchar(500) DEFAULT NULL,
  `pi` varchar(500) DEFAULT NULL,
  `co_pi` varchar(500) DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `specific_objectives` longtext DEFAULT NULL,
  `background` longtext DEFAULT NULL,
  `project_status` enum('New','Modification','Extension') DEFAULT 'New',
  `literature_review` longtext DEFAULT NULL,
  `related_research` longtext DEFAULT NULL,
  `research_type` enum('Scientific','Technology','Product') DEFAULT 'Scientific',
  `beneficiaries` longtext DEFAULT NULL,
  `outputs` longtext DEFAULT NULL,
  `transfer` longtext DEFAULT NULL,
  `organizational_outcomes` longtext DEFAULT NULL,
  `national_impacts` longtext DEFAULT NULL,
  `external_org` longtext DEFAULT NULL,
  `project_team` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_team`)),
  `methodology` longtext DEFAULT NULL,
  `activities` longtext DEFAULT NULL,
  `milestones` longtext DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `staff_costs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`staff_costs`)),
  `direct_expenses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`direct_expenses`)),
  `total_cost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`total_cost`)),
  `other_grants` longtext DEFAULT NULL,
  `contractual_obligations` longtext DEFAULT NULL,
  `ip_ownership` longtext DEFAULT NULL,
  `acknowledgement` tinyint(1) DEFAULT 0,
  `status` enum('reviewed','submitted') DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `user_id`, `paper_call_id`, `department_id`, `year`, `phase`, `project_title`, `pi`, `co_pi`, `keywords`, `specific_objectives`, `background`, `project_status`, `literature_review`, `related_research`, `research_type`, `beneficiaries`, `outputs`, `transfer`, `organizational_outcomes`, `national_impacts`, `external_org`, `project_team`, `methodology`, `activities`, `milestones`, `start_date`, `duration_months`, `staff_costs`, `direct_expenses`, `total_cost`, `other_grants`, `contractual_obligations`, `ip_ownership`, `acknowledgement`, `status`, `created_at`, `updated_at`) VALUES
(5, 3, 3, 2, 5765, 'kjbkgh', 'hgjhg', 'hjgjhg', 'jhgf', 'jhfjf', 'jhf', NULL, 'New', NULL, 'k.gjhgjh', 'Scientific', 'jhgjhgjhg', 'hgjhg', 'jhgfj', 'fjh', 'fjf', 'jgf', '[{\"name\":\"kjgkjg\",\"org\":\"kgjhg\",\"mm\":\"90\"}]', 'nbmnbjh', 'hgjhgjhg', 'hgjgjh', '2025-09-21', 89, '[{\"category\":\"Salaried Paid\",\"year\":2024,\"amount\":\"80\"}]', '[]', NULL, 'kjgkghjh', 'hjgjhgjhf', 'jhfjfjgf', 1, 'submitted', '2025-09-21 19:44:42', '2025-09-21 19:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `submission_attachments`
--

CREATE TABLE `submission_attachments` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `type` enum('l_rev','appendA','appendB','appendC') DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_attachments`
--

INSERT INTO `submission_attachments` (`id`, `submission_id`, `type`, `file_path`, `original_name`, `uploaded_at`) VALUES
(6, 5, 'l_rev', 'uploads/submissions/5/1758462282_601213635d58_AB_Tender_Submission_Document_Edited_1.pdf', 'AB Tender Submission Document_Edited 1.pdf', '2025-09-21 19:44:42'),
(7, 5, 'appendA', 'uploads/submissions/5/1758462282_239bfff2461d_BDRAILWAY_TICKET5142136014-114115-411142-1321711-11103017313321011.pdf', 'BDRAILWAY_TICKET5142136014-114115-411142-1321711-11103017313321011.pdf', '2025-09-21 19:44:42'),
(8, 5, 'appendB', 'uploads/submissions/5/1758462282_00db1faf74a5_july_25_officers_Part27.pdf', 'july_25_officers_Part27.pdf', '2025-09-21 19:44:42'),
(9, 5, 'appendC', 'uploads/submissions/5/1758462282_31f6e6408fd2_kha_14_07_2025.pdf', 'kha_14_07_2025.pdf', '2025-09-21 19:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `picture` varchar(500) DEFAULT NULL,
  `role` enum('teacher','officer','admin','dre_admin','none') NOT NULL DEFAULT 'none',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `picture`, `role`, `status`, `created_at`, `updated_at`) VALUES
(2, 'golam.mahmood@cuet.ac.bd', 'Golam Mahmood', 'https://lh3.googleusercontent.com/a/ACg8ocKjVKOmdBTUv9c_0NH-gy0OSLj8S8aWE8IuGZSnitOdjWDyJD4=s96-c', 'admin', 'active', '2025-08-19 00:35:42', '2025-09-19 15:51:13'),
(3, 'iict.admin.external@cuet.ac.bd', 'IICT External', 'https://lh3.googleusercontent.com/a/ACg8ocKop8kKsDRgPTuhaMIP5dg9kel2kSgE4XT3gA_hbnu6djMjnw=s96-c', 'teacher', 'active', '2025-09-19 16:13:50', '2025-09-21 19:55:13'),
(4, 'chinmoy.bhowmik@cuet.ac.bd', 'Chinmoy Bhowmik', NULL, 'teacher', 'active', '2025-09-19 19:07:33', '2025-09-19 19:07:33');

-- --------------------------------------------------------

--
-- Table structure for table `user_modules`
--

CREATE TABLE `user_modules` (
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_modules`
--

INSERT INTO `user_modules` (`user_id`, `module_id`) VALUES
(2, 1),
(3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_info`
--
ALTER TABLE `bank_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bank_review` (`review_id`);

--
-- Indexes for table `committee_pool`
--
ALTER TABLE `committee_pool`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `paper_calls`
--
ALTER TABLE `paper_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `paper_call_attachments`
--
ALTER TABLE `paper_call_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paper_call_id` (`paper_call_id`);

--
-- Indexes for table `reviewer_pool`
--
ALTER TABLE `reviewer_pool`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reviews_submission` (`submission_id`),
  ADD KEY `fk_reviews_reviewer` (`reviewer_id`);

--
-- Indexes for table `review_marks`
--
ALTER TABLE `review_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_marks_review` (`review_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `paper_call_id` (`paper_call_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `submission_attachments`
--
ALTER TABLE `submission_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_modules`
--
ALTER TABLE `user_modules`
  ADD PRIMARY KEY (`user_id`,`module_id`),
  ADD KEY `fk_um_module` (`module_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank_info`
--
ALTER TABLE `bank_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `committee_pool`
--
ALTER TABLE `committee_pool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `paper_calls`
--
ALTER TABLE `paper_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `paper_call_attachments`
--
ALTER TABLE `paper_call_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviewer_pool`
--
ALTER TABLE `reviewer_pool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `review_marks`
--
ALTER TABLE `review_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `submission_attachments`
--
ALTER TABLE `submission_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bank_info`
--
ALTER TABLE `bank_info`
  ADD CONSTRAINT `fk_bank_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `committee_pool`
--
ALTER TABLE `committee_pool`
  ADD CONSTRAINT `committee_pool_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `committee_pool_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paper_calls`
--
ALTER TABLE `paper_calls`
  ADD CONSTRAINT `paper_calls_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paper_call_attachments`
--
ALTER TABLE `paper_call_attachments`
  ADD CONSTRAINT `paper_call_attachments_ibfk_1` FOREIGN KEY (`paper_call_id`) REFERENCES `paper_calls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviewer_pool`
--
ALTER TABLE `reviewer_pool`
  ADD CONSTRAINT `reviewer_pool_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviewer_pool_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `reviewer_pool` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_marks`
--
ALTER TABLE `review_marks`
  ADD CONSTRAINT `fk_review_marks_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`paper_call_id`) REFERENCES `paper_calls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `submission_attachments`
--
ALTER TABLE `submission_attachments`
  ADD CONSTRAINT `submission_attachments_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_modules`
--
ALTER TABLE `user_modules`
  ADD CONSTRAINT `fk_um_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
