-- =====================================================
-- RLabz Database Schema (Expanded for Project Repository)
-- =====================================================

-- Create the database (optional, you can also use an existing one)
-- CREATE DATABASE IF NOT EXISTS rlabz_db;
-- USE rlabz_db;

-- 1. Table for Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin user (Password is 'admin123', you should change this later!)
-- The hash below is generated using password_hash('admin123', PASSWORD_DEFAULT) in PHP
INSERT IGNORE INTO `admin_users` (`username`, `password_hash`) VALUES
('admin', '$2y$10$YourHashedPasswordStringHere...'); 
-- Note: You should run a quick PHP script to generate a real hash for your desired password and replace it here.

-- 2. Table for Projects (Expanded)
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `year` VARCHAR(10) NOT NULL,
    `short_description` VARCHAR(500) DEFAULT '',
    `description` TEXT NOT NULL,
    `objectives` TEXT DEFAULT NULL,
    `problem_statement` TEXT DEFAULT NULL,
    `key_features` TEXT DEFAULT NULL,
    `expected_outcome` TEXT DEFAULT NULL,
    `tech_stack` VARCHAR(255) DEFAULT '',
    `image_path` VARCHAR(255) NOT NULL,
    `thumbnail_path` VARCHAR(255) DEFAULT '',
    `department` VARCHAR(100) DEFAULT 'MCA',
    `batch` VARCHAR(20) DEFAULT '',
    `category` VARCHAR(50) DEFAULT 'Web Application',
    `project_type` VARCHAR(50) DEFAULT 'Academic',
    `duration` VARCHAR(50) DEFAULT '',
    `status` VARCHAR(20) DEFAULT 'Completed',
    `github_link` VARCHAR(255) DEFAULT '',
    `demo_link` VARCHAR(255) DEFAULT '',
    `poster_path` VARCHAR(255) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table for Project Team Members (Expanded)
CREATE TABLE IF NOT EXISTS `project_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `photo_path` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT '',
    `register_number` VARCHAR(20) DEFAULT '',
    `linkedin_link` VARCHAR(255) DEFAULT '',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3.5 Table for Project Faculty (New)
CREATE TABLE IF NOT EXISTS `project_faculty` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(100) DEFAULT '',
    `photo_path` VARCHAR(255) DEFAULT '',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table for Project Screenshots (New)
CREATE TABLE IF NOT EXISTS `project_screenshots` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) DEFAULT '',
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Migration from old schema (run only if upgrading)
-- =====================================================
-- ALTER TABLE `projects` ADD COLUMN `slug` VARCHAR(100) NOT NULL UNIQUE AFTER `id`;
-- ALTER TABLE `projects` ADD COLUMN `short_description` VARCHAR(500) DEFAULT '' AFTER `year`;
-- ALTER TABLE `projects` ADD COLUMN `objectives` TEXT DEFAULT NULL AFTER `description`;
-- ALTER TABLE `projects` ADD COLUMN `problem_statement` TEXT DEFAULT NULL AFTER `objectives`;
-- ALTER TABLE `projects` ADD COLUMN `key_features` TEXT DEFAULT NULL AFTER `problem_statement`;
-- ALTER TABLE `projects` ADD COLUMN `expected_outcome` TEXT DEFAULT NULL AFTER `key_features`;
-- ALTER TABLE `projects` ADD COLUMN `thumbnail_path` VARCHAR(255) DEFAULT '' AFTER `image_path`;
-- ALTER TABLE `projects` ADD COLUMN `department` VARCHAR(100) DEFAULT 'MCA' AFTER `thumbnail_path`;
-- ALTER TABLE `projects` ADD COLUMN `batch` VARCHAR(20) DEFAULT '' AFTER `department`;
-- ALTER TABLE `projects` ADD COLUMN `faculty_name` VARCHAR(100) DEFAULT '' AFTER `batch`;
-- ALTER TABLE `projects` ADD COLUMN `faculty_designation` VARCHAR(100) DEFAULT '' AFTER `faculty_name`;
-- ALTER TABLE `projects` ADD COLUMN `faculty_photo` VARCHAR(255) DEFAULT '' AFTER `faculty_designation`;
-- ALTER TABLE `projects` ADD COLUMN `category` VARCHAR(50) DEFAULT 'Web Application' AFTER `faculty_photo`;
-- ALTER TABLE `projects` ADD COLUMN `project_type` VARCHAR(50) DEFAULT 'Academic' AFTER `category`;
-- ALTER TABLE `projects` ADD COLUMN `duration` VARCHAR(50) DEFAULT '' AFTER `project_type`;
-- ALTER TABLE `projects` ADD COLUMN `status` VARCHAR(20) DEFAULT 'Completed' AFTER `duration`;
-- ALTER TABLE `projects` ADD COLUMN `github_link` VARCHAR(255) DEFAULT '' AFTER `status`;
-- ALTER TABLE `projects` ADD COLUMN `demo_link` VARCHAR(255) DEFAULT '' AFTER `github_link`;
-- ALTER TABLE `projects` ADD COLUMN `poster_path` VARCHAR(255) DEFAULT '' AFTER `demo_link`;
-- ALTER TABLE `project_members` ADD COLUMN `role` VARCHAR(50) DEFAULT '' AFTER `photo_path`;
-- ALTER TABLE `project_members` ADD COLUMN `register_number` VARCHAR(20) DEFAULT '' AFTER `role`;

-- 5. Table for SSL Certificates (New Module)
CREATE TABLE IF NOT EXISTS `project_ssl_certs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `domain_url` VARCHAR(255) NOT NULL,
    `provider` VARCHAR(100) DEFAULT '',
    `issue_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
