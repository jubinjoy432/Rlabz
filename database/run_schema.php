<?php
// Run full schema to create any missing tables
require_once '../admin/api/db.php';

echo "Running schema migrations...\n";

$statements = [
    "CREATE TABLE IF NOT EXISTS `project_faculty` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `designation` VARCHAR(100) DEFAULT '',
        `photo_path` VARCHAR(255) DEFAULT '',
        `linkedin_link` VARCHAR(255) DEFAULT '',
        PRIMARY KEY (`id`),
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `project_screenshots` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `caption` VARCHAR(255) DEFAULT '',
        `sort_order` INT(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `project_ssl_certs` (
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
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `project_milestones` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `status` ENUM('Completed', 'Current', 'Upcoming') DEFAULT 'Completed',
        `milestone_date` DATE NOT NULL,
        `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `admin_announcements` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `type` VARCHAR(50) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `severity` VARCHAR(50) DEFAULT 'info',
        `reference_type` VARCHAR(50) DEFAULT NULL,
        `reference_id` INT(11) DEFAULT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

// Column additions (safe - ignore if already exists)
$alter_statements = [
    "ALTER TABLE `projects` ADD COLUMN `slug` VARCHAR(100) NOT NULL UNIQUE AFTER `id`",
    "ALTER TABLE `projects` ADD COLUMN `short_description` VARCHAR(500) DEFAULT '' AFTER `year`",
    "ALTER TABLE `projects` ADD COLUMN `objectives` TEXT DEFAULT NULL AFTER `description`",
    "ALTER TABLE `projects` ADD COLUMN `problem_statement` TEXT DEFAULT NULL AFTER `objectives`",
    "ALTER TABLE `projects` ADD COLUMN `key_features` TEXT DEFAULT NULL AFTER `problem_statement`",
    "ALTER TABLE `projects` ADD COLUMN `expected_outcome` TEXT DEFAULT NULL AFTER `key_features`",
    "ALTER TABLE `projects` ADD COLUMN `thumbnail_path` VARCHAR(255) DEFAULT '' AFTER `image_path`",
    "ALTER TABLE `projects` ADD COLUMN `department` VARCHAR(100) DEFAULT 'MCA' AFTER `thumbnail_path`",
    "ALTER TABLE `projects` ADD COLUMN `batch` VARCHAR(20) DEFAULT '' AFTER `department`",
    "ALTER TABLE `projects` ADD COLUMN `category` VARCHAR(50) DEFAULT 'Web Application'",
    "ALTER TABLE `projects` ADD COLUMN `project_type` VARCHAR(50) DEFAULT 'Academic'",
    "ALTER TABLE `projects` ADD COLUMN `duration` VARCHAR(50) DEFAULT ''",
    "ALTER TABLE `projects` ADD COLUMN `status` VARCHAR(20) DEFAULT 'Completed'",
    "ALTER TABLE `projects` ADD COLUMN `github_link` VARCHAR(255) DEFAULT ''",
    "ALTER TABLE `projects` ADD COLUMN `demo_link` VARCHAR(255) DEFAULT ''",
    "ALTER TABLE `projects` ADD COLUMN `ssl_tracking_enabled` TINYINT(1) DEFAULT 1",
    "ALTER TABLE `projects` ADD COLUMN `poster_path` VARCHAR(255) DEFAULT ''",
    "ALTER TABLE `project_members` ADD COLUMN `role` VARCHAR(50) DEFAULT '' AFTER `photo_path`",
    "ALTER TABLE `project_members` ADD COLUMN `register_number` VARCHAR(20) DEFAULT '' AFTER `role`",
    "ALTER TABLE `project_members` ADD COLUMN `linkedin_link` VARCHAR(255) DEFAULT ''",
    "ALTER TABLE `project_ssl_certs` ADD COLUMN `certificate_fingerprint` VARCHAR(255) DEFAULT NULL AFTER `expiry_date`",
];

try {
    foreach ($statements as $sql) {
        $pdo->exec($sql);
        echo "✓ CREATE TABLE executed\n";
    }

    foreach ($alter_statements as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ " . substr($sql, 0, 60) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || $e->getCode() === '42S21') {
                echo "  (skipped - column already exists)\n";
            } else {
                echo "  Warning: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n✅ Schema migration completed!\n";

    // Show final table list
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB: " . implode(', ', $tables) . "\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
