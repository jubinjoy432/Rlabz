-- =====================================================
-- RLabz Database Schema (Expanded for Project Repository)
-- =====================================================

-- 1. Table for Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note on Admin Users:
-- Do not insert placeholder/dummy hashes into production databases.
-- To create or reset the administrator password securely, run:
--   php dev/init_db.php  (sets default admin credentials in a local/dev environment)

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
    `image_path` VARCHAR(500) NOT NULL,
    `thumbnail_path` VARCHAR(500) DEFAULT '',
    `department` VARCHAR(100) DEFAULT 'MCA',
    `batch` VARCHAR(20) DEFAULT '',
    `category` VARCHAR(50) DEFAULT 'Web Application',
    `client` VARCHAR(255) DEFAULT NULL,
    `duration` VARCHAR(50) DEFAULT '',
    `status` VARCHAR(30) DEFAULT 'In Development',
    `github_link` VARCHAR(255) DEFAULT '',
    `demo_link` VARCHAR(255) DEFAULT '',
    `poster_path` VARCHAR(500) DEFAULT '',
    `ssl_tracking_enabled` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_year` (`year`),
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table for Project Team Members (Expanded)
CREATE TABLE IF NOT EXISTS `project_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `photo_path` VARCHAR(500) DEFAULT '',
    `role` VARCHAR(50) DEFAULT '',
    `register_number` VARCHAR(20) DEFAULT '',
    `linkedin_link` VARCHAR(255) DEFAULT '',
    PRIMARY KEY (`id`),
    INDEX `idx_pm_project_id` (`project_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3.5 Table for Project Faculty
CREATE TABLE IF NOT EXISTS `project_faculty` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(100) DEFAULT '',
    `photo_path` VARCHAR(500) DEFAULT '',
    `linkedin_link` VARCHAR(255) DEFAULT '',
    PRIMARY KEY (`id`),
    INDEX `idx_pf_project_id` (`project_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table for Project Screenshots
CREATE TABLE IF NOT EXISTS `project_screenshots` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `caption` VARCHAR(255) DEFAULT '',
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_ps_project_id` (`project_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table for SSL Certificates (Integrated Monitoring Module)
CREATE TABLE IF NOT EXISTS `project_ssl_certs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `domain_url` VARCHAR(255) NOT NULL,
    `provider` VARCHAR(100) DEFAULT '',
    `issue_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `certificate_fingerprint` VARCHAR(255) DEFAULT NULL,
    `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'Pending',
    `last_error` TEXT DEFAULT NULL,
    `last_alert_level` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ssl_project_id` (`project_id`),
    INDEX `idx_ssl_status` (`status`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table for Project Milestones / Timeline
CREATE TABLE IF NOT EXISTS `project_milestones` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `status` ENUM('Completed', 'Current', 'Upcoming') DEFAULT 'Completed',
    `milestone_date` DATE NOT NULL,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pmilestone_project_id` (`project_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table for Admin Announcements (Notifications)
CREATE TABLE IF NOT EXISTS `admin_announcements` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `severity` VARCHAR(50) DEFAULT 'info',
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT(11) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_announcement_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Table for Internship Opportunities
CREATE TABLE IF NOT EXISTS `internship_opportunities` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_name` VARCHAR(255) NOT NULL,
    `company_logo` VARCHAR(500) DEFAULT '',
    `company_website` VARCHAR(255) DEFAULT '',
    `company_location` VARCHAR(255) DEFAULT '',
    `internship_title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'General',
    `short_description` VARCHAR(500) NOT NULL,
    `description` TEXT NOT NULL,
    `responsibilities` TEXT DEFAULT NULL,
    `requirements` TEXT DEFAULT NULL,
    `eligibility` TEXT DEFAULT NULL,
    `duration` VARCHAR(100) NOT NULL,
    `work_mode` ENUM('Remote', 'On-site', 'Hybrid') NOT NULL DEFAULT 'Remote',
    `location` VARCHAR(255) DEFAULT '',
    `stipend_type` ENUM('Paid', 'Unpaid', 'Performance-based', 'Not disclosed') NOT NULL DEFAULT 'Paid',
    `stipend_amount` VARCHAR(100) DEFAULT '',
    `openings` INT(11) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `application_deadline` DATE NOT NULL,
    `application_email` VARCHAR(255) DEFAULT '',
    `application_url` VARCHAR(500) DEFAULT '',
    `application_instructions` TEXT DEFAULT NULL,
    `contact_person` VARCHAR(150) DEFAULT '',
    `contact_phone` VARCHAR(50) DEFAULT '',
    `poster_path` VARCHAR(500) DEFAULT '',
    `status` ENUM('Draft', 'Published', 'Closed', 'Archived') NOT NULL DEFAULT 'Draft',
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_internship_status` (`status`),
    INDEX `idx_internship_deadline` (`application_deadline`),
    INDEX `idx_internship_deleted` (`deleted_at`),
    INDEX `idx_internship_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. Default Administrator Account
-- Credentials: Username = admin | Password = admin123
-- Uses INSERT IGNORE so it will not overwrite existing credentials if already created.
-- =====================================================
INSERT IGNORE INTO `admin_users` (`id`, `username`, `password_hash`, `role`) 
VALUES (1, 'admin', '$2y$10$ZAXXwXTJinle7YPZ/m1BxOvUNUQbpVwD7eBhBspzn9/2pADj4wrei', 'admin');

