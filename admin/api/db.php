<?php
// admin/api/db.php
$host = 'localhost';
$dbname = 'rlabz_db'; // Change this to your actual database name
$username = 'root';    // Change this to your database username
$envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
$envValues = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : [];
$password = getenv('RLABZ_DB_PASSWORD') ?: ($envValues['RLABZ_DB_PASSWORD'] ?? '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>