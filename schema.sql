-- Create the database (optional, you can also use an existing one)
-- CREATE DATABASE IF NOT EXISTS rlabz_db;
-- USE rlabz_db;

-- 1. Table for Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin user (Password is 'admin123', you should change this later!)
-- The hash below is generated using password_hash('admin123', PASSWORD_DEFAULT) in PHP
INSERT IGNORE INTO `admin_users` (`username`, `password_hash`) VALUES
('admin', '$2y$10$YourHashedPasswordStringHere...'); 
-- Note: You should run a quick PHP script to generate a real hash for your desired password and replace it here.

-- 2. Table for Projects
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `year` VARCHAR(10) NOT NULL,
    `description` TEXT NOT NULL,
    `tech_stack` VARCHAR(255) DEFAULT '', -- Stored as comma-separated string
    `image_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table for Project Team Members
CREATE TABLE IF NOT EXISTS `project_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `photo_path` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
