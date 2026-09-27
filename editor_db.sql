-- CodeOven database schema (v3)
-- Import this into MySQL/MariaDB (e.g. via phpMyAdmin on XAMPP/WAMP).
-- Database Name: editor_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Drop existing tables in reverse dependency order
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_files`;
DROP TABLE IF EXISTS `tbl_projects`;
DROP TABLE IF EXISTS `tbl_preferences`;
DROP TABLE IF EXISTS `tbl_user_profiles`;
DROP TABLE IF EXISTS `tbl_users`;

-- --------------------------------------------------------
-- Table: tbl_users
-- Core authentication table for all registered users.
-- --------------------------------------------------------
CREATE TABLE `tbl_users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` char(60) NOT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: tbl_user_profiles
-- Extended user profile, developer bio, links, and avatar.
-- --------------------------------------------------------
CREATE TABLE `tbl_user_profiles` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `preferred_language` varchar(50) NOT NULL DEFAULT 'JavaScript',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `uniq_user_profile` (`user_id`),
  CONSTRAINT `tbl_user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: tbl_projects
-- A project is a workspace container holding multiple code files.
-- --------------------------------------------------------
CREATE TABLE `tbl_projects` (
  `project_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `uniq_user_project` (`user_id`, `project_name`),
  CONSTRAINT `tbl_projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: tbl_files
-- Multi-file code storage for HTML, CSS, JS, Python, C++, Java, etc.
-- --------------------------------------------------------
CREATE TABLE `tbl_files` (
  `file_id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `language` varchar(30) NOT NULL DEFAULT 'plaintext',
  `file_content` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `uniq_project_file` (`project_id`, `file_name`),
  KEY `idx_user_files` (`user_id`),
  CONSTRAINT `tbl_files_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `tbl_projects` (`project_id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_files_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: tbl_preferences
-- User IDE UI settings (theme, layout, font size, active project).
-- --------------------------------------------------------
CREATE TABLE `tbl_preferences` (
  `preference_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `layout` varchar(20) NOT NULL DEFAULT 'vertical',
  `theme` varchar(20) NOT NULL DEFAULT 'dark',
  `word_wrap` tinyint(1) NOT NULL DEFAULT '1',
  `show_line_numbers` tinyint(1) NOT NULL DEFAULT '1',
  `auto_save` tinyint(1) NOT NULL DEFAULT '1',
  `font_size` int NOT NULL DEFAULT '14',
  `last_project` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `uniq_user_pref` (`user_id`),
  CONSTRAINT `tbl_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Seed Data (Sample Users & Profiles)
-- --------------------------------------------------------
INSERT INTO `tbl_users` (`user_id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'demo_developer', 'dev@codeoven.io', '$2y$10$VZEvLMr9lbTdZVFTJWGRe.2JiAHZ//dQrgrHbKs/wDk.ZpWhloxE2', '2025-09-06 17:04:59'),
(2, 'alex_code', 'alex@example.com', '$2y$10$1LD7LLGXfrzvxyp..SEi0uP7K7gmb80XxAFWI8N8p32GJEQ2xjIyS', '2025-09-06 17:15:50');

INSERT INTO `tbl_user_profiles` (`user_id`, `full_name`, `bio`, `avatar_url`, `github_url`, `website_url`, `location`, `preferred_language`) VALUES
(1, 'Demo Developer', 'Full-stack web architect and open-source enthusiast building with CodeOven.', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150', 'https://github.com/codeoven-demo', 'https://codeoven.io', 'San Francisco, CA', 'JavaScript'),
(2, 'Alex Rivera', 'Backend engineer exploring Python, C++, and cloud compilers.', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150', 'https://github.com/alex-dev', 'https://alexrivera.dev', 'Austin, TX', 'Python');

INSERT INTO `tbl_preferences` (`user_id`, `layout`, `theme`, `font_size`) VALUES
(1, 'vertical', 'dark', 14),
(2, 'horizontal', 'dark', 15);

INSERT INTO `tbl_projects` (`project_id`, `user_id`, `project_name`) VALUES
(1, 1, 'My First Web App');

INSERT INTO `tbl_files` (`project_id`, `user_id`, `file_name`, `language`, `file_content`) VALUES
(1, 1, 'index.html', 'HTML', '<!DOCTYPE html>\n<html>\n<head>\n  <title>Welcome to CodeOven</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n  <h1>Hello from CodeOven!</h1>\n  <p>Your instant offline-first web IDE.</p>\n  <script src="app.js"></script>\n</body>\n</html>'),
(1, 1, 'style.css', 'CSS', 'body {\n  background: #0f172a;\n  color: #f8fafc;\n  font-family: system-ui, sans-serif;\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  height: 90vh;\n}\nh1 {\n  color: #06b6d4;\n}'),
(1, 1, 'app.js', 'JavaScript', 'console.log("Welcome to CodeOven Studio!");');

COMMIT;
