<?php
require_once __DIR__ . '/../admin/api/db.php';

echo "Running LinkedIn Migration for Team and Faculty...\n";

try {
    // 1. Check and add linkedin_link to project_members
    $pmCols = $pdo->query("SHOW COLUMNS FROM `project_members`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('linkedin_link', $pmCols)) {
        $pdo->exec("ALTER TABLE `project_members` ADD COLUMN `linkedin_link` VARCHAR(255) DEFAULT '' AFTER `register_number`");
        echo "Added linkedin_link to project_members.\n";
    } else {
        echo "linkedin_link already exists in project_members.\n";
    }

    // 2. Check and add linkedin_link to project_faculty
    $pfCols = $pdo->query("SHOW COLUMNS FROM `project_faculty`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('linkedin_link', $pfCols)) {
        $pdo->exec("ALTER TABLE `project_faculty` ADD COLUMN `linkedin_link` VARCHAR(255) DEFAULT '' AFTER `photo_path`");
        echo "Added linkedin_link to project_faculty.\n";
    } else {
        echo "linkedin_link already exists in project_faculty.\n";
    }

    echo "LinkedIn migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error altering table: " . $e->getMessage() . "\n";
}
?>
