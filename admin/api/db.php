<?php
// admin/api/db.php
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? 'rlabz_db';
    $username = $env['DB_USER'] ?? 'root';
    $password = $env['DB_PASS'] ?? '';
} else {
    // Fallback if .env is missing
    $host = 'localhost';
    $dbname = 'rlabz_db';
    $username = 'root';
    $password = '';
}

$pdo = null;
$db_error = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-bootstrap: If tables do not exist when running the app, execute schema.sql automatically
    $checkTable = $pdo->query("SHOW TABLES LIKE 'projects'");
    if ($checkTable->rowCount() === 0) {
        $schemaPath = __DIR__ . '/../../database/schema.sql';
        if (file_exists($schemaPath)) {
            $rawSql = file_get_contents($schemaPath);
            $rawSql = preg_replace('!/\*.*?\*/!s', '', $rawSql);
            $lines = explode("\n", $rawSql);
            $currentSql = '';
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) continue;
                $currentSql .= $line . "\n";
                if (str_ends_with($trimmed, ';')) {
                    $q = trim($currentSql);
                    if (!empty($q)) {
                        $pdo->exec($q);
                    }
                    $currentSql = '';
                }
            }
            if (!empty(trim($currentSql))) {
                $pdo->exec(trim($currentSql));
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }
} catch(PDOException $e) {
    $db_error = $e->getMessage();
    if (!defined('ALLOW_DB_FAIL')) {
        die("Connection failed: " . $e->getMessage());
    }
}

?>
