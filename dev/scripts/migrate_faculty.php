<?php
require_once 'admin/api/db.php';

try {
    echo "Creating project_faculty table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `project_faculty` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `designation` VARCHAR(100) DEFAULT '',
            `photo_path` VARCHAR(255) DEFAULT '',
            PRIMARY KEY (`id`),
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Migrating existing faculty data...\n";
    $stmt = $pdo->query("SELECT id, faculty_name, faculty_designation, faculty_photo FROM projects WHERE faculty_name != ''");
    $projects = $stmt->fetchAll();

    $insertStmt = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path) VALUES (?, ?, ?, ?)");
    foreach ($projects as $p) {
        $insertStmt->execute([
            $p['id'],
            $p['faculty_name'],
            $p['faculty_designation'],
            $p['faculty_photo']
        ]);
        echo "Migrated faculty for project ID: " . $p['id'] . "\n";
    }

    echo "Dropping old columns from projects table...\n";
    $pdo->exec("ALTER TABLE projects DROP COLUMN faculty_name, DROP COLUMN faculty_designation, DROP COLUMN faculty_photo");

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
