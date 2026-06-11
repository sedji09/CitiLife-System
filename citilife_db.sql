-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 08:04 AM
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
-- Database: `citilife_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `created_at`) VALUES
(1, 'Gapan', '2026-03-22 03:27:20'),
(2, 'Bongabon', '2026-03-22 03:27:20'),
(3, 'Peñaranda', '2026-03-22 03:27:20'),
(4, 'General Tinio', '2026-03-22 03:27:20'),
(5, 'Sto Domingo', '2026-03-22 03:27:20'),
(6, 'San Antonio', '2026-03-22 03:27:20'),
(7, 'Pantabangan', '2026-03-22 03:27:20');

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `exam_type` varchar(100) NOT NULL,
  `priority` enum('Normal','Priority','STAT','Urgent','Routine') NOT NULL DEFAULT 'Normal',
  `philhealth_status` enum('With PhilHealth Card','Without PhilHealth Card') NOT NULL,
  `philhealth_id` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Under Reading','Report Ready','Completed') NOT NULL DEFAULT 'Pending',
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `image_status` enum('Uploaded','—') NOT NULL DEFAULT '—',
  `image_path` varchar(255) DEFAULT NULL,
  `released` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `clinical_information` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `impression` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `radiologist_id` int(11) DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  `report_template` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_number`, `first_name`, `last_name`, `age`, `sex`, `contact_number`, `home_address`, `branch_id`, `created_at`) VALUES
(2, NULL, 'Jolina', 'Magdangal', 21, 'Female', '09153504355', NULL, 1, '2026-03-22 10:00:12'),
(3, NULL, 'Jiar', 'Maglaque', 21, 'Male', '09153504355', NULL, 3, '2026-03-22 12:19:30'),
(4, 'PAT-GAP-2026-001', 'Seigi', 'Pascual', 22, 'Male', '09676585644', NULL, 1, '2026-03-27 08:19:47'),
(6, 'PAT-GAP-2026-003', 'Sherlyn', 'Pascual', 24, 'Female', '09758291987', NULL, 1, '2026-03-27 08:53:34'),
(7, 'PAT-GAP-2026-004', 'Francheska Claire', 'Lopez', 21, 'Female', '09123456789', NULL, 1, '2026-03-27 08:54:42'),
(8, 'PAT-GAP-2026-005', 'Seigi', 'Pascual', 22, 'Male', '09676585644', NULL, 1, '2026-03-27 11:27:06'),
(9, 'PAT-GEN-2026-001', 'Sherlyn', 'Pascual', 24, 'Female', '09123456789', NULL, NULL, '2026-03-27 11:29:55'),
(14, 'PAT-GAP-2026-006', 'Sherlyn', 'Pascual', 24, 'Female', '09676585644', NULL, 1, '2026-03-27 11:46:59'),
(15, 'PAT-GAP-2026-007', 'Sedji', 'Pascual', 22, 'Male', '09676585644', NULL, 1, '2026-03-27 12:03:29'),
(16, 'PAT-GAP-2026-008', 'Merlyn', 'Pascual', 40, 'Female', '09676585644', NULL, 1, '2026-03-27 12:30:03'),
(17, 'PAT-GAP-2026-009', 'Seigi', 'Pascual', 22, 'Male', '091019191', NULL, 1, '2026-03-27 13:38:36'),
(18, 'PAT-GAP-2026-010', 'Seigi', 'Pascual', 22, 'Male', '09676585644', NULL, 1, '2026-03-27 13:42:30'),
(19, 'PAT-GAP-2026-011', 'Seigi', 'Pascual', 22, 'Male', '09676585644', NULL, 1, '2026-03-27 13:44:11'),
(20, 'PAT-GAP-2026-012', 'Seigi', 'Pascual', 22, 'Male', '09676585644', NULL, 1, '2026-03-27 14:52:40'),
(21, 'PAT-GAP-2026-013', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-27 16:01:43'),
(22, 'PAT-GAP-2026-014', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-28 02:40:30'),
(23, 'PAT-GAP-2026-015', 'Seiji', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-28 02:40:40'),
(24, 'PAT-GAP-2026-016', 'SEIGI', 'PASCUAL', 22, 'Male', '09857673975', NULL, 1, '2026-03-28 02:40:48'),
(25, 'PAT-GAP-2026-017', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-28 02:40:55'),
(26, 'PAT-GAP-2026-018', 'Seigi', 'Pascual', 2, 'Male', '09676585644', NULL, 1, '2026-03-28 02:41:09'),
(27, 'PAT-GAP-2026-019', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-28 02:41:17'),
(28, 'PAT-GAP-2026-020', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-28 09:18:46'),
(29, 'PAT-GAP-2026-021', 'Seigi', 'Pascual', 22, 'Male', '09176871675', NULL, 1, '2026-03-28 11:27:10'),
(30, 'PAT-GAP-2026-022', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-29 00:06:08'),
(31, 'PAT-BNG-2026-001', 'Franchisika', 'Lopez', 21, 'Female', '09128101978', NULL, 2, '2026-03-29 04:44:13'),
(32, 'PAT-BNG-2026-002', 'Sedji', 'Pascual', 23, 'Male', '09128101978', NULL, 2, '2026-03-29 06:59:30'),
(33, 'PAT-GAP-2026-023', 'Sedji', 'Pascual', 25, 'Male', '09128101978', NULL, 1, '2026-03-29 07:01:30'),
(34, 'PAT-GAP-2026-024', 'Margie', 'Manabat', 23, 'Female', '01917892136', NULL, 1, '2026-03-29 14:19:41'),
(35, 'PAT-GAP-2026-025', 'Merlyn', 'Pascual', 40, 'Female', '09676585644', NULL, 1, '2026-03-29 14:40:21'),
(36, 'PAT-GAP-2026-026', 'Seigi', 'Pascual', 22, 'Male', '09676585641', NULL, 1, '2026-03-30 03:31:04');

-- --------------------------------------------------------

--
-- Table structure for table `record_requests`
--

CREATE TABLE `record_requests` (
  `id` int(11) NOT NULL,
  `patient_no` varchar(50) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `exam_type` varchar(100) DEFAULT NULL,
  `request_branch` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Denied') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `record_requests`
--

INSERT INTO `record_requests` (`id`, `patient_no`, `patient_name`, `exam_type`, `request_branch`, `reason`, `branch_id`, `status`, `created_at`) VALUES
(1, 'asdasd', 'asdasd', 'asdasd', 'Gapan', 'asdasd', 1, 'Approved', '2026-03-22 12:21:04'),
(2, 'PAT-GAP-002-10', 'JUAN DELA CRUZ', 'X-RAY', 'Bongabon', 'wedret', 1, 'Pending', '2026-03-27 14:56:35'),
(3, 'CX-2026-0011', 'Jiar Maglaque', 'Chest PA', 'Bongabon', 'i need', 1, 'Approved', '2026-03-28 15:27:42'),
(4, 'CX-2026-0047', 'Franchisika Lopez', 'Chest PA', 'Bongabon', 'need kolang', 1, 'Approved', '2026-03-29 09:03:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Active','Rejected') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `branch_id`, `created_at`, `patient_id`, `status`) VALUES
(1, 'admin_central@citilife.com', '$2y$10$b5FpsRYu5fcHn9mZ37r78OR8WBrkuNX/WyE6ccZj9ml19A78h5hNm', 'admin_central', NULL, '2026-03-19 02:18:15', NULL, 'Active'),
(3, 'it_admin@citilife.com', '$2y$10$b5FpsRYu5fcHn9mZ37r78OR8WBrkuNX/WyE6ccZj9ml19A78h5hNm', 'it_admin', 1, '2026-03-19 02:18:15', NULL, 'Active'),
(5, 'radiologist@citilife.com', '$2y$10$b5FpsRYu5fcHn9mZ37r78OR8WBrkuNX/WyE6ccZj9ml19A78h5hNm', 'radiologist', 1, '2026-03-19 02:18:15', NULL, 'Active'),
(7, 'radtech_gapan@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 1, '2026-03-20 22:06:51', NULL, 'Active'),
(9, 'branch_admin_gapan@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 1, '2026-03-20 22:06:51', NULL, 'Active'),
(10, 'radtech_bongabon@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 2, '2026-03-20 22:06:51', NULL, 'Active'),
(12, 'branch_admin_bongabon@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 2, '2026-03-20 22:06:51', NULL, 'Active'),
(13, 'radtech_penaranda@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 3, '2026-03-20 22:06:51', NULL, 'Active'),
(15, 'branch_admin_penaranda@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 3, '2026-03-20 22:06:51', NULL, 'Active'),
(16, 'radtech_generaltinio@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 4, '2026-03-20 22:06:51', NULL, 'Active'),
(18, 'branch_admin_generaltinio@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 4, '2026-03-20 22:06:51', NULL, 'Active'),
(19, 'radtech_sanantonio@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 5, '2026-03-20 22:06:51', NULL, 'Active'),
(21, 'branch_admin_sanantonio@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 5, '2026-03-20 22:06:51', NULL, 'Active'),
(22, 'radtech_stodomingo@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 6, '2026-03-20 22:06:51', NULL, 'Active'),
(24, 'branch_admin_stodomingo@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 6, '2026-03-20 22:06:51', NULL, 'Active'),
(25, 'radtech_pantabangan@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'radtech', 7, '2026-03-20 22:06:51', NULL, 'Active'),
(27, 'branch_admin_pantabangan@citilife.com', '$2y$10$HPOnowr3K1Pil8XUPjgu5eI9o65iAyHc57J4uibHLmGyk1riVHOFe', 'branch_admin', 7, '2026-03-20 22:06:51', NULL, 'Active'),
(28, 'jayr.maglaque09153@gmail.com', '$2y$10$M8MjtUuTx7ab4DohklGOvefApC7vGQwRoyp86fiNxha0Yar0OoxGO', 'patient', NULL, '2026-03-22 12:19:30', 3, 'Active'),
(29, 'seigipascual09@gmail.com', '$2y$10$l/fpv1WQNzkDIlVViGysyOm1223eDBIgQl.PrjCq9gM5f4z7itk3O', 'patient', NULL, '2026-03-27 08:19:47', 4, 'Active'),
(30, 'personalsherlyn@gmail.com', '$2y$10$2omiDzLj3hG05ODWyN1f.emuucpY77DCufmkvf4RIgSJ.O5Hc3pP.', 'patient', NULL, '2026-03-27 11:29:55', 9, 'Active'),
(31, 'sherlynpascual17@gmail.com', '$2y$10$WJYSHfecDUKHqxClvZVNE.BVxhxYQyCshzFWdiDG0MG95hBCR5j/C', 'patient', NULL, '2026-03-27 11:46:59', 14, 'Active'),
(32, 'sedjipascual09@gmail.com', '$2y$10$1fPL7j7rxTcM0KAqx0Hl7uO9l2KcN2zVyB/Z5py5/KPF9x.liK12O', 'patient', NULL, '2026-03-27 12:03:29', 15, 'Active'),
(33, 'merlynpascual25@gmail.com', '$2y$10$NrnQamvB9MTSfShdOrtDKu.T5wTLkB17bth4KQB6/DHLnxBxp.IZ6', 'patient', NULL, '2026-03-27 12:30:03', 16, 'Active'),
(34, 'sedji09@gmail.com', '$2y$10$8MIo.hRgaBYSt5xp.SbTDugteBZgHtT1.sq/SWHqzmC1W/adJd/ri', 'patient', NULL, '2026-03-28 11:27:10', 29, 'Active'),
(35, 'merlynpascual025@gmail.com', '$2y$10$8HDZIkGVFdQ6x8kvCxmeN.ECy58jfO2AyaJZ6OaNhYkn0A6yDNE/S', 'patient', NULL, '2026-03-29 14:40:22', 35, 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `fk_cases_branch` (`branch_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_number` (`patient_number`),
  ADD KEY `fk_patients_branch` (`branch_id`);

--
-- Indexes for table `record_requests`
--
ALTER TABLE `record_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_requests_branch` (`branch_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `record_requests`
--
ALTER TABLE `record_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `fk_cases_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cases_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `record_requests`
--
ALTER TABLE `record_requests`
  ADD CONSTRAINT `fk_requests_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
