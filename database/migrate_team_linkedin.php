<?php
require_once __DIR__ . '/../admin/api/db.php';

$sql = "ALTER TABLE `project_members` ADD COLUMN IF NOT EXISTS `linkedin_link` VARCHAR(255) DEFAULT '' AFTER `register_number`;";

try {
    $pdo->exec($sql);
    echo "Successfully added linkedin_link column to project_members table.\n";
} catch (PDOException $e) {
    echo "Error altering table: " . $e->getMessage() . "\n";
}
?>
