-- phpMyAdmin SQL Dump
-- version 4.5.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 16, 2025 at 10:51 AM
-- Server version: 5.7.11
-- PHP Version: 7.2.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skiblox`
--

-- --------------------------------------------------------

--
-- Table structure for table `bans`
--

CREATE TABLE `bans` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `banned_by` int(10) UNSIGNED DEFAULT NULL,
  `banned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unbanned_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(500) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `is_image` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `message`, `image_path`, `created_at`, `file_path`, `file_name`, `is_image`) VALUES
(1, 1, 'lol clean chat', NULL, '2025-08-12 07:04:18', NULL, NULL, 0),
(2, 1, 'wewewew', NULL, '2025-08-12 07:16:50', NULL, NULL, 0),
(3, 2, 'hi', NULL, '2025-08-12 07:21:08', NULL, NULL, 0),
(4, 7, '1', NULL, '2025-08-12 07:21:38', NULL, NULL, 0),
(5, 7, '2', NULL, '2025-08-12 07:21:43', NULL, NULL, 0),
(6, 7, '33', NULL, '2025-08-12 07:21:58', NULL, NULL, 0),
(7, 7, '33', NULL, '2025-08-12 07:22:00', NULL, NULL, 0),
(8, 7, '3', NULL, '2025-08-12 07:23:27', NULL, NULL, 0),
(9, 2, 'hi', NULL, '2025-08-12 07:25:44', NULL, NULL, 0),
(10, 8, 'wsPpsW', NULL, '2025-08-12 07:31:19', NULL, NULL, 0),
(11, 1, 'hallo gjys', NULL, '2025-08-12 07:35:38', NULL, NULL, 0),
(12, 1, '####bbb', NULL, '2025-08-12 07:35:42', NULL, NULL, 0),
(13, 1, 'nooo it censored ####', NULL, '2025-08-12 07:35:52', NULL, NULL, 0),
(14, 2, 'the site is slow ;(', NULL, '2025-08-13 00:07:02', NULL, NULL, 0),
(15, 2, 'abc', NULL, '2025-08-13 00:08:14', NULL, NULL, 0),
(16, 1, 'hi', NULL, '2025-08-13 00:08:49', NULL, NULL, 0),
(17, 1, 'hi', NULL, '2025-08-13 00:08:50', NULL, NULL, 0),
(18, 2, 'hello', NULL, '2025-08-13 00:09:04', NULL, NULL, 0),
(19, 1, 'hi', NULL, '2025-08-13 01:58:10', NULL, NULL, 0),
(20, 1, 'radmin vpn works good lol', NULL, '2025-08-13 01:59:56', NULL, NULL, 0),
(21, 2, 'hi', NULL, '2025-08-13 02:00:45', NULL, NULL, 0),
(22, 2, 'wait', NULL, '2025-08-13 02:01:15', NULL, NULL, 0),
(23, 2, 'can you interact with the skiblox site on the radmin one?', NULL, '2025-08-13 02:01:26', NULL, NULL, 0),
(24, 2, 'js asking', NULL, '2025-08-13 02:01:29', NULL, NULL, 0),
(25, 1, 'works?', NULL, '2025-08-13 02:01:30', NULL, NULL, 0),
(26, 1, 'uh', NULL, '2025-08-13 02:01:35', NULL, NULL, 0),
(27, 1, 'wdym', NULL, '2025-08-13 02:01:45', NULL, NULL, 0),
(28, 2, 'uhh', NULL, '2025-08-13 02:01:56', NULL, NULL, 0),
(29, 1, 'yes im on http://26.147.21.224/chat rn', NULL, '2025-08-13 02:02:01', NULL, NULL, 0),
(30, 2, ';D', NULL, '2025-08-13 02:02:46', NULL, NULL, 0),
(31, 1, 'FiLe UpLoAd FaIlEd', NULL, '2025-08-13 02:03:36', NULL, NULL, 0),
(32, 1, 'lol', NULL, '2025-08-13 02:03:39', NULL, NULL, 0),
(33, 2, 'WARNING! dont share your SKIBLOXSESSID or else you can be hacked', NULL, '2025-08-13 02:03:41', NULL, NULL, 0),
(34, 1, 'UnExCepTed ErRoR', NULL, '2025-08-13 02:03:55', NULL, NULL, 0),
(35, 2, 'FoRBiDdEN', NULL, '2025-08-13 02:05:36', NULL, NULL, 0),
(36, 1, 'lol', NULL, '2025-08-13 02:06:11', NULL, NULL, 0),
(37, 1, 'http://26.147.21.224/chathttp://26.147.21.224/chathttp://26.147.21.224/chathttp://26.147.21.224/chathttp://26.147.21.224/chat', NULL, '2025-08-13 02:06:19', NULL, NULL, 0),
(38, 1, 'hi hih hhhiii hih hih hi  i hi h h  i', NULL, '2025-08-14 04:35:23', NULL, NULL, 0),
(39, 1, 'what what wha', NULL, '2025-08-14 04:38:25', NULL, NULL, 0),
(40, 1, 'jfanuifweiuhruwje', NULL, '2025-08-14 04:38:43', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `configuration`
--

CREATE TABLE `configuration` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `maintenance_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `banner_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `banner_message` varchar(512) NOT NULL DEFAULT 'hi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `configuration`
--

INSERT INTO `configuration` (`id`, `maintenance_enabled`, `banner_enabled`, `banner_message`) VALUES
(1, 0, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `sibux` int(11) NOT NULL DEFAULT '0',
  `tixs` int(11) NOT NULL DEFAULT '0',
  `date_join` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_sibux_award_on` date DEFAULT NULL,
  `last_online` datetime DEFAULT NULL,
  `chat_msg_mod3` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `sibux`, `tixs`, `date_join`, `last_sibux_award_on`, `last_online`, `chat_msg_mod3`, `is_admin`) VALUES
(1, 'onion', 'onion@gmail.com', '$2y$10$kDJ1B3F5XArm.QMerH67E.xPrPRQjxkyYlik7RR6zDudR/3afOO1e', 1000001538, 1000000703, '1970-01-01 00:00:00', '2025-08-14', '2025-08-14 04:41:28', 0, 1),
(2, 'ROBLOX', 'agafonovkirill2010@gmail.com', '$2y$10$9l61yBfl/Q4uDEItVZ0bXeKNppnI36yHVPOxZeZP6MXExhbsVoStG', 2000000017, 1999999999, '2006-08-11 05:07:04', '2025-08-14', '2025-08-14 04:41:43', 1, 1),
(3, 'Builderman', '666bahram.jafari1985@gmail.com', '$2y$10$N8LDk2rxOKb4JX3B5yngE.Hx2WtFEjROYztHyE3eNp1nWt264qPU2', 0, 0, '2025-08-11 05:12:03', '2025-08-11', '2025-08-11 05:34:42', 0, 1),
(6, 'sykahhhh', 'ownerjack@gmail.com', '$2y$10$V0BAWA4DpxAMkFw.iSW.p.Wk414.vBtUJc/csaprDUX5f8hjX98M6', 0, 0, '2025-08-11 09:19:42', '2025-08-11', '2025-08-11 09:24:39', 2, 0),
(7, '.........', 'jdahmersfridge@gmail.com', '$2y$10$/7OPht5GOX0jk7UXI0LySOnd6nT/QtJVKuwMGnPN9DtqmxxJATJie', 1, 0, '2025-08-12 07:21:19', NULL, '2025-08-12 07:31:07', 2, 0),
(8, 'Liammagee10', 'liammagee105@gmail.com', '$2y$10$9kO48pWvVOPxrrkKgPB27.Sile9f6NmHVgjoesYhn/54L4eVaeQgK', 0, 0, '2025-08-12 07:31:07', NULL, '2025-08-12 07:36:53', 1, 0),
(9, 'onlytwentycharacters', 'agafonov.uu@mail.ru', '$2y$10$7eJP0EFYv90OgC7K9LzMiOPEtm2uR.tVw4RhLoOFhMwtz0eAJGcKC', 0, 0, '2025-08-12 07:32:54', NULL, '2025-08-12 07:35:13', 0, 0),
(10, '12234', 'fuckyou@gmail.com', '$2y$10$aE7.sBKHyyNrAWQadRFMRO/sQImI9o8ADe/Og3a0Irlpxh93Z1P6i', 0, 0, '2025-08-12 07:39:16', NULL, '2025-08-12 07:42:17', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_active` (`user_id`,`active`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `configuration`
--
ALTER TABLE `configuration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `bans`
--
ALTER TABLE `bans`
  ADD CONSTRAINT `fk_bans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
