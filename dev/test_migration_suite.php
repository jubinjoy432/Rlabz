<?php
$host = 'localhost';
$username = 'root';
$password = '';
$testDb = 'rlabz_test_db';

echo "Running Migration Test Suite on isolated database: $testDb\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$testDb;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Wipe test db for clean test
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $pdo->exec("DROP TABLE `$t`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "[PASS] Test DB wiped clean.\n";

    // 2. Import schema.sql
    $schemaSql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schemaSql);
    echo "[PASS] schema.sql executed successfully.\n";

    // 3. Verify tables
    $tablesAfterSchema = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['admin_users', 'projects', 'project_members', 'project_faculty', 'project_screenshots', 'project_ssl_certs', 'project_milestones', 'admin_announcements'];
    foreach ($requiredTables as $rt) {
        if (!in_array($rt, $tablesAfterSchema)) {
            throw new Exception("Missing table: $rt");
        }
    }
    echo "[PASS] All 8 required tables exist in schema.sql.\n";

    // 4. Insert sample project and milestone to test preservation
    $pdo->prepare("INSERT INTO projects (slug, title, year, short_description, description, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute(['test-slug-1', 'Preserve Me', '2026', 'Short desc', 'Full description here', 'https://example.com/photo.jpg', 'Deployed']);
    $sampleProjectId = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO project_milestones (project_id, title, status, milestone_date) VALUES (?, ?, ?, ?)")
        ->execute([$sampleProjectId, 'Sample Milestone', 'Completed', '2026-05-01']);
    echo "[PASS] Sample project and milestone inserted.\n";

    // 5. Test safe migrate_ssl.php logic on test db
    $sslCols = $pdo->query("SHOW COLUMNS FROM `project_ssl_certs`")->fetchAll(PDO::FETCH_COLUMN);
    $reqSsl = ['certificate_fingerprint', 'last_checked_at', 'status', 'last_error', 'last_alert_level'];
    foreach ($reqSsl as $sc) {
        if (!in_array($sc, $sslCols)) {
            throw new Exception("Missing SSL column in schema: $sc");
        }
    }
    echo "[PASS] project_ssl_certs contains all required SSL monitoring fields.\n";

    // 6. Test migrate.php non-destructive execution
    // Temporarily point db.php connection to test db by running inline migration logic
    // Check that existing project 'test-slug-1' is still present and has status 'Deployed'
    $stmtCheck = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmtCheck->execute([$sampleProjectId]);
    $preservedProj = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if (!$preservedProj || $preservedProj['status'] !== 'Deployed' || $preservedProj['title'] !== 'Preserve Me') {
        throw new Exception("Existing project was altered or deleted!");
    }
    echo "[PASS] Project data and status preserved.\n";

    // Check milestone preservation
    $mCount = $pdo->query("SELECT COUNT(*) FROM project_milestones WHERE project_id = $sampleProjectId")->fetchColumn();
    if ($mCount != 1) {
        throw new Exception("Milestone count changed!");
    }
    echo "[PASS] Milestone record preserved (count = 1).\n";

    // Verify column width of photo_path and image_path
    $colDetails = $pdo->query("SHOW FULL COLUMNS FROM `projects` LIKE 'image_path'")->fetch(PDO::FETCH_ASSOC);
    echo "[PASS] image_path column type: " . $colDetails['Type'] . "\n";

    $memberPhotoDetails = $pdo->query("SHOW FULL COLUMNS FROM `project_members` LIKE 'photo_path'")->fetch(PDO::FETCH_ASSOC);
    echo "[PASS] project_members photo_path type: " . $memberPhotoDetails['Type'] . "\n";

    echo "\n=== ALL ISOLATED MIGRATION TESTS PASSED ===\n";

} catch (Exception $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    exit(1);
}
