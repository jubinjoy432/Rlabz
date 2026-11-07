<?php
/**
 * RLabz - Direct Database Schema Runner
 * Executes database/schema.sql against the configured PDO connection.
 */

require_once __DIR__ . '/../admin/api/db.php';

echo "======================================================\n";
echo "  RLabz Database Schema Runner\n";
echo "======================================================\n\n";

try {
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Error: schema.sql not found at $schemaFile\n");
    }

    echo "Reading schema.sql...\n";
    $rawSql = file_get_contents($schemaFile);

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Clean comments and split queries
    $rawSql = preg_replace('!/\*.*?\*/!s', '', $rawSql);
    $lines = explode("\n", $rawSql);
    $queries = [];
    $current = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $current .= $line . "\n";
        if (str_ends_with($trimmed, ';')) {
            $q = trim($current);
            if (!empty($q)) {
                $queries[] = $q;
            }
            $current = '';
        }
    }
    if (!empty(trim($current))) {
        $queries[] = trim($current);
    }

    $executed = 0;
    foreach ($queries as $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;

        $pdo->exec($sql);
        $executed++;

        if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
            echo "✓ Created table: {$m[1]}\n";
        } elseif (preg_match('/INSERT IGNORE INTO `?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
            echo "✓ Seeded default: {$m[1]}\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n------------------------------------------------------\n";
    echo "Successfully executed {$executed} statements.\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB (" . count($tables) . "): " . implode(', ', $tables) . "\n";
    echo "------------------------------------------------------\n";
    echo "Admin Login: Username = admin | Password = admin123\n\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
