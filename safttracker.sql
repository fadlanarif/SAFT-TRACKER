-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 04:11 AM
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
-- Database: `safttracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `approaches_count` int(11) DEFAULT 0,
  `presentations_count` int(11) DEFAULT 0,
  `closing_count` int(11) DEFAULT 0,
  `log_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_photos`
--

CREATE TABLE `activity_photos` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_photos`
--

INSERT INTO `activity_photos` (`id`, `agent_id`, `photo_path`, `description`, `created_at`) VALUES
(7, 5, 'uploads/activities/1793523307_RSC00031.jpeg', 'Pick up My First Car', '2026-11-01 08:55:07'),
(10, 3, 'uploads/activities/1794709080_HHC00025.png', '', '2026-11-15 02:18:00');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `agent_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('agent','leader') DEFAULT 'agent',
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `agent_code`, `name`, `password`, `role`, `profile_pic`, `created_at`) VALUES
(2, 'admin', 'Pengurus Agensi (Admin)', '$2y$10$wT8KzJ3vG9L4M2N1O0P9Qu8R7S6T5U4V3W2X1Y0Z9a8b7c6d5e4f', 'leader', 'default.png', '2026-10-05 02:18:11'),
(3, 'HHC00025', 'NOOR HANIS BINTI HASSAN', '$2y$10$bxKerzn05yz/6lwENB7bSu9cOVeWpIJST/ZPbMtquUHXEZz5fY7yS', 'agent', '1792041703_HHC00025.jpg', '2026-10-05 02:21:37'),
(4, 'RSC00038', 'DURRATUL INSYIRAH BINTI MOHAMAD ARIFF', '$2y$10$zB6JTvH7G3Q5r390/YbRT.8EdyCb0720M4Wx5IPtwnkeStYt/.vXK', 'agent', 'default.png', '2026-10-05 03:37:05'),
(5, 'RSC00031', 'NUR ALYA AKMAL BINTI TAJUDDIN', '$2y$10$zJ0CPS85ZRGlofJpWwTe2uXHm7L1eL5jqjT8QPnGXs5ox72r3O7fW', 'agent', '1792045708_RSC00031.jpg', '2026-10-15 06:28:28'),
(6, 'RSC00035', 'MOHD FARID BIN MOHD TAIB', '$2y$10$NSQEcFL3Wk6mQB/eJGwdgOUBMjPqdCfp/wpAc.YuCTIyFvH0lwotm', 'agent', '1792545830_RSC00035.jpg', '2026-10-21 01:23:50'),
(7, 'RHD00034', 'MOHD HANIF BIN WAHAB', '$2y$10$8s8llVR84HHXJ2YKTkHRYO5ENE/fxGXQwQr5DjoK.SBj/su4LpYUm', 'agent', '1793590791_RHD00034.jpeg', '2026-11-01 08:09:52'),
(8, 'FB100005', 'MOHAMAD SYAFFUAN RAMLI', '$2y$10$.iQBL2mcYzsCf9r.9HWWuuu64QaZYVls5r.0EcJcgrmaegdHS4HpC', 'agent', 'default.png', '2026-11-01 08:15:23'),
(9, 'MFP00069', 'ROSLINDA BINTI SENO', '$2y$10$Ah/MTTwZkU.c5Op8FcHsR.FMgjiLSXljObIhzskvHUcFAxWSdg.we', 'agent', 'default.png', '2026-11-01 08:16:07'),
(10, 'MFP00071', 'AZRUL BIN AB RAHIM', '$2y$10$THpmu817Ddh2CXkTTqLt0OmFtHmv74epIqDy9HTRZMfIwjz/n6IfG', 'agent', 'default.png', '2026-11-01 08:16:41'),
(11, 'RSC800029', 'NORHANIDAH AKMA BINTI MOHAMAD ZAKARIA', '$2y$10$0S3lD1.Dph.ElnVen.Cy.Oe8T3iCMPZrQqoJrKebV/54K4iztIKx2', 'agent', 'default.png', '2026-11-01 08:19:14'),
(12, 'H2M00010', 'SYED ABDULLAH ILMI', '$2y$10$gWJy9pGnVHNL/cX7L5wNE.YUXTLweKld4YZkBpcz4WE49G0fbbKBa', 'agent', 'default.png', '2026-11-01 08:21:19'),
(13, 'FB100003', 'AISHAH BINTI MAZLAN', '$2y$10$RmvYgQ5m58aY7tbmCDrNDeYlpjsANJQkcJmssckQoohjt6DQTQBAy', 'agent', 'default.png', '2026-11-01 08:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `performance`
--

CREATE TABLE `performance` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `tpc_amount` int(11) DEFAULT 0,
  `cases_count` int(11) DEFAULT 0,
  `health_cases` int(11) DEFAULT 0,
  `persistency` int(11) DEFAULT 0,
  `c_amax` int(11) DEFAULT 0,
  `qualifiers` int(11) DEFAULT 0,
  `new_cases` int(11) DEFAULT 0,
  `bop_recruits` int(11) DEFAULT 0,
  `mtd_amount` int(11) DEFAULT 0,
  `ytd_amount` int(11) DEFAULT 0,
  `month_year` varchar(7) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `performance`
--

INSERT INTO `performance` (`id`, `agent_id`, `tpc_amount`, `cases_count`, `health_cases`, `persistency`, `c_amax`, `qualifiers`, `new_cases`, `bop_recruits`, `mtd_amount`, `ytd_amount`, `month_year`, `updated_at`) VALUES
(2, 3, 120552, 52, 0, 5, 2, 7, 15, 3, 7000, 123557, '2026-10', '2026-10-27 01:45:52'),
(3, 2, 120552, 52, 0, 5, 2, 11, 15, 3, 7000, 120552, '2026-10', '2026-10-15 04:58:25'),
(4, 4, 0, 0, 0, 3, 0, 4, 47, 0, 0, 0, '2026-10', '2026-10-15 05:24:12'),
(5, 5, 0, 0, 0, 5, 0, 0, 0, 0, 0, 0, '2026-10', '2026-10-27 03:23:24'),
(6, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 232547, '2026-11', '2026-11-01 03:11:26'),
(7, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-11', '2026-11-01 08:55:07'),
(8, 7, 0, 0, 0, 5, 1, 3, 4, 0, 2800, 15000, '2026-11', '2026-11-02 03:34:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `activity_photos`
--
ALTER TABLE `activity_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_id_no` (`agent_code`);

--
-- Indexes for table `performance`
--
ALTER TABLE `performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_photos`
--
ALTER TABLE `activity_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `performance`
--
ALTER TABLE `performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_photos`
--
ALTER TABLE `activity_photos`
  ADD CONSTRAINT `activity_photos_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance`
--
ALTER TABLE `performance`
  ADD CONSTRAINT `performance_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
