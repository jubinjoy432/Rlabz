<?php
require_once '../admin/api/db.php';

try {
    // Attempt to add new columns to project_ssl_certs
    $pdo->exec("ALTER TABLE project_ssl_certs ADD COLUMN last_checked_at TIMESTAMP NULL DEFAULT NULL AFTER expiry_date;");
    $pdo->exec("ALTER TABLE project_ssl_certs ADD COLUMN status VARCHAR(50) DEFAULT 'Pending' AFTER last_checked_at;");
    $pdo->exec("ALTER TABLE project_ssl_certs ADD COLUMN last_error TEXT DEFAULT NULL AFTER status;");
    $pdo->exec("ALTER TABLE project_ssl_certs ADD COLUMN last_alert_level VARCHAR(50) DEFAULT NULL AFTER last_error;");
    echo "project_ssl_certs altered successfully.\n";
} catch (PDOException $e) {
    echo "project_ssl_certs alter error (might already exist): " . $e->getMessage() . "\n";
}

try {
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schema);
    echo "Schema executed successfully (created project_milestones if not exists).\n";
} catch (PDOException $e) {
    echo "Schema execution error: " . $e->getMessage() . "\n";
}
?>
