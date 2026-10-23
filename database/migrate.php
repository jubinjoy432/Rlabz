<?php
/**
 * RLabz Safe Database Migration & Upgrade Script
 *
 * Safe & Idempotent:
 * - Creates missing tables without altering or dropping existing ones.
 * - Adds missing columns without deleting existing records.
 * - Expands photo and thumbnail path lengths to 500 characters to support external image URLs.
 * - Seeds missing initial projects from dev/projects_dump.json WITHOUT overwriting existing projects or statuses.
 * - Preserves existing milestone and SSL records.
 */

require_once __DIR__ . '/../admin/api/db.php';

$isFresh = in_array('--fresh', $argv ?? []);

echo "==================================================\n";
echo "RLabz Database Migration & Schema Upgrade\n";
echo "Mode: " . ($isFresh ? "FRESH INSTALLATION (Wipe & Rebuild)" : "SAFE UPGRADE (Preserve existing data)") . "\n";
echo "==================================================\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    if ($isFresh) {
        echo "Wiping existing tables for fresh installation...\n";
        $pdo->exec("DROP TABLE IF EXISTS `admin_announcements`, `project_milestones`, `project_ssl_certs`, `project_screenshots`, `project_faculty`, `project_members`, `projects`, `admin_users`;");
    }

    // 1. Create Tables IF NOT EXISTS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) DEFAULT 'admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            `project_type` VARCHAR(50) DEFAULT 'Academic',
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
    ");

    // 2. Safe Column Upgrades for Existing Databases
    // Check and add projects columns
    $projCols = $pdo->query("SHOW COLUMNS FROM `projects`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ssl_tracking_enabled', $projCols)) {
        $pdo->exec("ALTER TABLE `projects` ADD COLUMN `ssl_tracking_enabled` TINYINT(1) DEFAULT 1 AFTER `poster_path`");
        echo "Added `ssl_tracking_enabled` to projects.\n";
    }
    if (!in_array('short_description', $projCols)) {
        $pdo->exec("ALTER TABLE `projects` ADD COLUMN `short_description` VARCHAR(500) DEFAULT '' AFTER `year`");
        echo "Added `short_description` to projects.\n";
    }

    // Widen path columns to 500 chars to safely store external URLs and Google Drive links
    $pdo->exec("ALTER TABLE `projects` MODIFY COLUMN `image_path` VARCHAR(500) NOT NULL;");
    $pdo->exec("ALTER TABLE `projects` MODIFY COLUMN `thumbnail_path` VARCHAR(500) DEFAULT '';");
    $pdo->exec("ALTER TABLE `projects` MODIFY COLUMN `poster_path` VARCHAR(500) DEFAULT '';");
    $pdo->exec("ALTER TABLE `project_members` MODIFY COLUMN `photo_path` VARCHAR(500) DEFAULT '';");
    $pdo->exec("ALTER TABLE `project_faculty` MODIFY COLUMN `photo_path` VARCHAR(500) DEFAULT '';");
    $pdo->exec("ALTER TABLE `project_screenshots` MODIFY COLUMN `image_path` VARCHAR(500) NOT NULL;");

    // Check linkedin_link columns
    $pmCols = $pdo->query("SHOW COLUMNS FROM `project_members`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('linkedin_link', $pmCols)) {
        $pdo->exec("ALTER TABLE `project_members` ADD COLUMN `linkedin_link` VARCHAR(255) DEFAULT '' AFTER `register_number`");
        echo "Added `linkedin_link` to project_members.\n";
    }
    $pfCols = $pdo->query("SHOW COLUMNS FROM `project_faculty`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('linkedin_link', $pfCols)) {
        $pdo->exec("ALTER TABLE `project_faculty` ADD COLUMN `linkedin_link` VARCHAR(255) DEFAULT '' AFTER `photo_path`");
        echo "Added `linkedin_link` to project_faculty.\n";
    }

    // Check SSL columns
    $sslCols = $pdo->query("SHOW COLUMNS FROM `project_ssl_certs`")->fetchAll(PDO::FETCH_COLUMN);
    $sslAdditions = [
        'certificate_fingerprint' => "VARCHAR(255) DEFAULT NULL AFTER `expiry_date`",
        'last_checked_at'        => "TIMESTAMP NULL DEFAULT NULL AFTER `certificate_fingerprint`",
        'status'                 => "VARCHAR(50) DEFAULT 'Pending' AFTER `last_checked_at`",
        'last_error'              => "TEXT DEFAULT NULL AFTER `status`",
        'last_alert_level'       => "VARCHAR(50) DEFAULT NULL AFTER `last_error`"
    ];
    foreach ($sslAdditions as $col => $def) {
        if (!in_array($col, $sslCols)) {
            $pdo->exec("ALTER TABLE `project_ssl_certs` ADD COLUMN `{$col}` {$def}");
            echo "Added `{$col}` to project_ssl_certs.\n";
        }
    }

    // 3. Ensure any existing projects with empty short_description fall back to full description
    $pdo->exec("UPDATE `projects` SET `short_description` = `description` WHERE `short_description` IS NULL OR TRIM(`short_description`) = ''");

    // 4. Seeding Initial Projects (Non-Destructive)
    // Only insert projects if the database has 0 projects, or insert individual projects if their slug does not exist.
    $dumpFile = __DIR__ . '/../dev/projects_dump.json';
    if (file_exists($dumpFile)) {
        $json = file_get_contents($dumpFile);
        $projects = json_decode($json, true) ?: [];

        $stmt_check = $pdo->prepare("SELECT id, status, description, short_description FROM projects WHERE slug = ?");
        $stmt_project = $pdo->prepare("INSERT INTO projects 
            (slug, title, year, short_description, description, objectives, problem_statement, key_features, expected_outcome, tech_stack, image_path, thumbnail_path, department, batch, category, project_type, duration, status, github_link, demo_link, poster_path, ssl_tracking_enabled) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        $stmt_member = $pdo->prepare("INSERT INTO project_members (project_id, name, photo_path, linkedin_link) VALUES (?, ?, '', '')");
        $stmt_faculty = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path, linkedin_link) VALUES (?, ?, ?, '', '')");
        $stmt_screenshot = $pdo->prepare("INSERT INTO project_screenshots (project_id, image_path) VALUES (?, ?)");

        foreach ($projects as $p) {
            $slug = $p['id'];
            $stmt_check->execute([$slug]);
            $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Project already exists: preserve title, status, and description!
                // Only if short_description is empty, update it with description
                if (empty($existing['short_description']) && !empty($existing['description'])) {
                    $pdo->prepare("UPDATE projects SET short_description = ? WHERE id = ?")->execute([$existing['description'], $existing['id']]);
                }
                continue;
            }

            // Project does not exist: insert safely
            $short_desc = !empty($p['shortDescription']) ? $p['shortDescription'] : ($p['fullDescription'] ?? '');
            $tech_stack = isset($p['tech']) && is_array($p['tech']) ? implode(', ', $p['tech']) : '';
            $key_features = isset($p['keyFeatures']) && is_array($p['keyFeatures']) ? implode(', ', $p['keyFeatures']) : '';
            $status = !empty($p['status']) ? $p['status'] : 'In Development';

            $stmt_project->execute([
                $slug,
                $p['title'] ?? '',
                $p['year'] ?? '',
                $short_desc,
                $p['fullDescription'] ?? '',
                $p['objectives'] ?? '',
                $p['problemStatement'] ?? '',
                $key_features,
                $p['expectedOutcome'] ?? '',
                $tech_stack,
                $p['thumbnail'] ?? '',
                $p['thumbnail'] ?? '',
                $p['department'] ?? 'MCA',
                $p['batch'] ?? '',
                $p['category'] ?? 'Web Application',
                $p['projectType'] ?? 'Academic',
                $p['duration'] ?? '',
                $status,
                $p['githubLink'] ?? '',
                $p['demoLink'] ?? '',
                $p['poster'] ?? ''
            ]);

            $newId = $pdo->lastInsertId();

            if (!empty($p['team']) && is_array($p['team'])) {
                foreach ($p['team'] as $m) {
                    $m_name = is_array($m) ? ($m['name'] ?? '') : $m;
                    if (!empty($m_name)) {
                        $stmt_member->execute([$newId, $m_name]);
                    }
                }
            }

            if (!empty($p['faculty'])) {
                $f_name = is_array($p['faculty']) ? ($p['faculty']['name'] ?? '') : $p['faculty'];
                $f_desig = is_array($p['faculty']) ? ($p['faculty']['designation'] ?? '') : '';
                if (!empty($f_name)) {
                    $stmt_faculty->execute([$newId, $f_name, $f_desig]);
                }
            }

            if (!empty($p['screenshots']) && is_array($p['screenshots'])) {
                foreach ($p['screenshots'] as $ss) {
                    if (!empty($ss)) {
                        $stmt_screenshot->execute([$newId, $ss]);
                    }
                }
            }

            echo "Inserted new seed project: {$p['title']} ({$slug})\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Migration finished successfully.\n";

} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
