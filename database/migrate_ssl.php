<?php
require_once __DIR__ . '/../admin/api/db.php';

$sql = "
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
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "Successfully created project_ssl_certs and admin_announcements tables.\n";
    
    // Add ssl_tracking_enabled if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE `projects` ADD COLUMN `ssl_tracking_enabled` TINYINT(1) DEFAULT 1 AFTER `demo_link`");
        echo "Successfully added ssl_tracking_enabled to projects table.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column ssl_tracking_enabled already exists.\n";
        } else {
            echo "Error altering table: " . $e->getMessage() . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}

?>
