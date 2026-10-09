<?php
require_once __DIR__ . '/../admin/api/db.php';

$sql = "
DROP TABLE IF EXISTS `project_ssl_certs`;
CREATE TABLE `project_ssl_certs` (
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
";

try {
    $pdo->exec($sql);
    echo "Successfully created project_ssl_certs table.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}

?>
