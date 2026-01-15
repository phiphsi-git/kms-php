-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 10. Nov 2025 um 00:49
-- Server-Version: 10.5.8-MariaDB-log
-- PHP-Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `kms`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsible_technician_id` int(10) UNSIGNED DEFAULT NULL,
  `owner_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `maintenance_type` enum('none','daily','weekly','biweekly','monthly','yearly','paused') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maintenance_time` time DEFAULT NULL,
  `maintenance_weekday` tinyint(4) DEFAULT NULL,
  `maintenance_week_of_month` tinyint(4) DEFAULT NULL,
  `maintenance_year_month` tinyint(4) DEFAULT NULL,
  `maintenance_year_day` tinyint(4) DEFAULT NULL,
  `maintenance_pause_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `customers`
--

INSERT INTO `customers` (`id`, `name`, `street`, `zip`, `city`, `website`, `logo_url`, `responsible_technician_id`, `owner_user_id`, `created_at`, `maintenance_type`, `maintenance_time`, `maintenance_weekday`, `maintenance_week_of_month`, `maintenance_year_month`, `maintenance_year_day`, `maintenance_pause_reason`) VALUES
(1, 'Mustermann AG', 'Industriestrasse 9', '8712', 'Stäfa', NULL, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSMQGtAX5lSAr05-o7uYqfqE38PyfAz1AmLjA&s', NULL, 2, '2025-11-07 06:30:09', 'daily', '11:00:00', NULL, NULL, NULL, NULL, NULL),
(2, 'Test AG', '', '8712', 'me', NULL, NULL, NULL, NULL, '2025-11-07 21:19:41', 'daily', '09:00:00', NULL, NULL, NULL, NULL, NULL),
(3, 'Muster AG', '', '', '', NULL, 'https://rcp.scsstatic.ch/content/dam/assets/about/unternehmen/marke/content/base-logo-217x309.jpg', NULL, NULL, '2025-11-07 23:16:51', 'daily', '11:00:00', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customer_contacts`
--

CREATE TABLE `customer_contacts` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tech_questions` tinyint(1) NOT NULL DEFAULT 0,
  `admin_questions` tinyint(1) NOT NULL DEFAULT 0,
  `budget_approvals` tinyint(1) NOT NULL DEFAULT 0,
  `credential_changes` tinyint(1) NOT NULL DEFAULT 0,
  `ticket_creation` tinyint(1) NOT NULL DEFAULT 0,
  `general_inquiries` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customer_files`
--

CREATE TABLE `customer_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `folder_id` int(10) UNSIGNED DEFAULT NULL,
  `system_id` int(10) UNSIGNED DEFAULT NULL,
  `task_id` int(10) UNSIGNED DEFAULT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `customer_files`
--

INSERT INTO `customer_files` (`id`, `customer_id`, `folder_id`, `system_id`, `task_id`, `stored_name`, `original_name`, `mime`, `size_bytes`, `description`, `uploaded_by`, `created_at`) VALUES
(4, 1, NULL, NULL, NULL, '20251109_064452_407ec939.pdf', 'Administration Anleitung für das Swisscom BNS Portal.pdf', 'application/pdf', 297187, 'Test', 1, '2025-11-08 22:44:52'),
(5, 1, NULL, NULL, NULL, '20251109_064552_4d1efd34.docx', 'Cocktailkarte.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1280302, NULL, 1, '2025-11-08 22:45:52');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customer_file_systems`
--

CREATE TABLE `customer_file_systems` (
  `file_id` int(10) UNSIGNED NOT NULL,
  `system_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `customer_file_systems`
--

INSERT INTO `customer_file_systems` (`file_id`, `system_id`) VALUES
(4, 3);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customer_file_tasks`
--

CREATE TABLE `customer_file_tasks` (
  `file_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `customer_file_tasks`
--

INSERT INTO `customer_file_tasks` (`file_id`, `task_id`) VALUES
(4, 5);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customer_folders`
--

CREATE TABLE `customer_folders` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `customer_reports`
--

CREATE TABLE `customer_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `customer_reports`
--

INSERT INTO `customer_reports` (`id`, `customer_id`, `filename`, `title`, `file_path`, `created_by`, `created_at`) VALUES
(1, 3, 'report_2025-11-09_052629.pdf', 'Wartungsreport', '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/report_2025-11-09_052629.pdf', 1, '2025-11-08 21:26:29'),
(2, 3, 'report_2025-11-09_052633.pdf', 'Wartungsreport', '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/report_2025-11-09_052633.pdf', 1, '2025-11-08 21:26:33'),
(9, 1, 'report_2025-11-09_055437.pdf', 'Wartungsreport', '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/1/reports/report_2025-11-09_055437.pdf', 1, '2025-11-08 21:54:37'),
(10, 2, 'report_2025-11-09_055953.pdf', 'Wartungsreport', '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/2/reports/report_2025-11-09_055953.pdf', 1, '2025-11-08 21:59:53'),
(11, 3, 'report_2025-11-09_180210.pdf', 'Wartungsreport', '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/report_2025-11-09_180210.pdf', 1, '2025-11-09 17:02:10'),
(12, 3, 'report_20251109_202145.pdf', NULL, '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/report_20251109_202145.pdf', NULL, '2025-11-09 19:21:45'),
(13, 3, 'report_20251109_202314.pdf', NULL, '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/report_20251109_202314.pdf', NULL, '2025-11-09 19:23:14'),
(14, 3, 'Report_Muster_AG_20251109_223901.pdf', NULL, '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/Report_Muster_AG_20251109_223901.pdf', 1, '2025-11-09 21:39:02'),
(15, 3, 'Report_Muster_AG_20251105_225100.pdf', NULL, '/share/CACHEDEV1_DATA/Web/kms-php/storage/customers/3/reports/Report_Muster_AG_20251105_225100.pdf', 1, '2025-11-09 21:51:08');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `systems`
--

CREATE TABLE `systems` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_version` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `install_date` date DEFAULT NULL,
  `responsible_technician_id` int(10) UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aktiv','pausiert','stillgelegt') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktiv'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `systems`
--

INSERT INTO `systems` (`id`, `customer_id`, `name`, `type`, `role`, `version`, `role_note`, `os_version`, `install_date`, `responsible_technician_id`, `notes`, `status`) VALUES
(3, 1, 'WinSRV2022', 'Windows Server 2022', 'Domain Controller', '22h2', NULL, NULL, '2024-04-01', 2, 'Test Notiz WinSrv2022', 'aktiv');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `system_documents`
--

CREATE TABLE `system_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `system_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tasks`
--

CREATE TABLE `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `system_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('offen','ausstehend','erledigt','fehlgeschlagen','pausiert') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offen',
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `pause_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `confirmed_by` int(10) UNSIGNED DEFAULT NULL,
  `failure_comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `tasks`
--

INSERT INTO `tasks` (`id`, `customer_id`, `system_id`, `title`, `description`, `status`, `is_paused`, `pause_reason`, `is_recurring`, `due_date`, `comment`, `created_by`, `assigned_to`, `completed_at`, `confirmed_by`, `failure_comment`, `created_at`) VALUES
(5, 1, 3, 'Kontrolle Datensicherung', NULL, 'erledigt', 0, NULL, 1, '2025-11-08', NULL, 1, NULL, NULL, NULL, NULL, '2025-11-08 21:28:30'),
(8, 3, NULL, 'Test', NULL, 'offen', 0, NULL, 1, '2025-11-09', NULL, 1, NULL, NULL, NULL, NULL, '2025-11-09 22:09:15'),
(9, 1, 3, 'Test 234', NULL, 'offen', 0, NULL, 1, NULL, NULL, 1, NULL, NULL, NULL, NULL, '2025-11-09 22:16:08');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_checkpoints`
--

CREATE TABLE `task_checkpoints` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT 0,
  `require_comment_on_fail` tinyint(1) NOT NULL DEFAULT 1,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_status_log`
--

CREATE TABLE `task_status_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `status` enum('offen','ausstehend','erledigt','pausiert') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `task_status_log`
--

INSERT INTO `task_status_log` (`id`, `task_id`, `status`, `comment`, `changed_by`, `changed_at`) VALUES
(3, 8, 'offen', NULL, 1, '2025-11-09 22:14:52'),
(4, 8, 'offen', NULL, 1, '2025-11-09 22:30:41');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_templates`
--

CREATE TABLE `task_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_comment` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_template_checkpoints`
--

CREATE TABLE `task_template_checkpoints` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `require_comment_on_fail` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mitarbeiter',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin-kms@bernauer.ch', '$2y$10$tNw6DVPsg8VAs0XZcyuh5eD2fQOMSxNs7mPMbpTDebzhrURixwlLa', 'Admin', 1, '2025-11-06 13:41:34'),
(2, 'philippe@bernauer.ch', '$2y$10$G.KVNZTMflOEmDJpdgTe3.rjCGCLO.XH1RbiS4GX/HeuyAEfbeAom', 'Mitarbeiter', 1, '2025-11-06 15:32:45');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customer_tech` (`responsible_technician_id`),
  ADD KEY `fk_customer_owner` (`owner_user_id`),
  ADD KEY `name` (`name`);

--
-- Indizes für die Tabelle `customer_contacts`
--
ALTER TABLE `customer_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indizes für die Tabelle `customer_files`
--
ALTER TABLE `customer_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `system_id` (`system_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `fk_cf_user` (`uploaded_by`);

--
-- Indizes für die Tabelle `customer_file_systems`
--
ALTER TABLE `customer_file_systems`
  ADD PRIMARY KEY (`file_id`,`system_id`),
  ADD KEY `fk_cfs_system` (`system_id`);

--
-- Indizes für die Tabelle `customer_file_tasks`
--
ALTER TABLE `customer_file_tasks`
  ADD PRIMARY KEY (`file_id`,`task_id`),
  ADD KEY `fk_cft_task` (`task_id`);

--
-- Indizes für die Tabelle `customer_folders`
--
ALTER TABLE `customer_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indizes für die Tabelle `customer_reports`
--
ALTER TABLE `customer_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fk_rep_creator` (`created_by`);

--
-- Indizes für die Tabelle `systems`
--
ALTER TABLE `systems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sys_tech` (`responsible_technician_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `responsible_technician_id` (`responsible_technician_id`);

--
-- Indizes für die Tabelle `system_documents`
--
ALTER TABLE `system_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_doc_user` (`uploaded_by`),
  ADD KEY `system_id` (`system_id`);

--
-- Indizes für die Tabelle `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_t_cb` (`created_by`),
  ADD KEY `fk_t_asg` (`assigned_to`),
  ADD KEY `fk_t_conf` (`confirmed_by`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `system_id` (`system_id`),
  ADD KEY `status` (`status`),
  ADD KEY `due_date` (`due_date`);

--
-- Indizes für die Tabelle `task_checkpoints`
--
ALTER TABLE `task_checkpoints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indizes für die Tabelle `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tc_user` (`user_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indizes für die Tabelle `task_status_log`
--
ALTER TABLE `task_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`,`changed_at`),
  ADD KEY `fk_tsl_user` (`changed_by`);

--
-- Indizes für die Tabelle `task_templates`
--
ALTER TABLE `task_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `task_template_checkpoints`
--
ALTER TABLE `task_template_checkpoints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `customer_contacts`
--
ALTER TABLE `customer_contacts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `customer_files`
--
ALTER TABLE `customer_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `customer_folders`
--
ALTER TABLE `customer_folders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `customer_reports`
--
ALTER TABLE `customer_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT für Tabelle `systems`
--
ALTER TABLE `systems`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `system_documents`
--
ALTER TABLE `system_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `task_checkpoints`
--
ALTER TABLE `task_checkpoints`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `task_status_log`
--
ALTER TABLE `task_status_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `task_templates`
--
ALTER TABLE `task_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `task_template_checkpoints`
--
ALTER TABLE `task_template_checkpoints`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customer_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_customer_tech` FOREIGN KEY (`responsible_technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `customer_contacts`
--
ALTER TABLE `customer_contacts`
  ADD CONSTRAINT `fk_cc_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `customer_files`
--
ALTER TABLE `customer_files`
  ADD CONSTRAINT `fk_cf_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cf_system` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cf_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cf_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `customer_file_systems`
--
ALTER TABLE `customer_file_systems`
  ADD CONSTRAINT `fk_cfs_file` FOREIGN KEY (`file_id`) REFERENCES `customer_files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cfs_system` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `customer_file_tasks`
--
ALTER TABLE `customer_file_tasks`
  ADD CONSTRAINT `fk_cft_file` FOREIGN KEY (`file_id`) REFERENCES `customer_files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cft_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `customer_reports`
--
ALTER TABLE `customer_reports`
  ADD CONSTRAINT `fk_rep_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rep_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `systems`
--
ALTER TABLE `systems`
  ADD CONSTRAINT `fk_sys_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sys_tech` FOREIGN KEY (`responsible_technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sys_technician` FOREIGN KEY (`responsible_technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `system_documents`
--
ALTER TABLE `system_documents`
  ADD CONSTRAINT `fk_doc_sys` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_t_asg` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_t_cb` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_t_conf` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_t_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_t_sys` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `task_checkpoints`
--
ALTER TABLE `task_checkpoints`
  ADD CONSTRAINT `fk_tcp_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `task_status_log`
--
ALTER TABLE `task_status_log`
  ADD CONSTRAINT `fk_tsl_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tsl_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `task_template_checkpoints`
--
ALTER TABLE `task_template_checkpoints`
  ADD CONSTRAINT `task_template_checkpoints_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `task_templates` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
