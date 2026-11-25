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
    } else {
        // Safe check: Ensure internship_opportunities table exists
        $checkInternship = $pdo->query("SHOW TABLES LIKE 'internship_opportunities'");
        if ($checkInternship->rowCount() === 0) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `internship_opportunities` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `company_name` VARCHAR(255) NOT NULL,
                `company_logo` VARCHAR(500) DEFAULT '',
                `company_website` VARCHAR(255) DEFAULT '',
                `company_location` VARCHAR(255) DEFAULT '',
                `internship_title` VARCHAR(255) NOT NULL,
                `category` VARCHAR(100) NOT NULL DEFAULT 'General',
                `short_description` VARCHAR(500) NOT NULL,
                `description` TEXT NOT NULL,
                `responsibilities` TEXT DEFAULT NULL,
                `requirements` TEXT DEFAULT NULL,
                `eligibility` TEXT DEFAULT NULL,
                `duration` VARCHAR(100) NOT NULL,
                `work_mode` ENUM('Remote', 'On-site', 'Hybrid') NOT NULL DEFAULT 'Remote',
                `location` VARCHAR(255) DEFAULT '',
                `stipend_type` ENUM('Paid', 'Unpaid', 'Performance-based', 'Not disclosed') NOT NULL DEFAULT 'Paid',
                `stipend_amount` VARCHAR(100) DEFAULT '',
                `openings` INT(11) DEFAULT NULL,
                `start_date` DATE DEFAULT NULL,
                `application_deadline` DATE NOT NULL,
                `application_email` VARCHAR(255) DEFAULT '',
                `application_url` VARCHAR(500) DEFAULT '',
                `application_instructions` TEXT DEFAULT NULL,
                `contact_person` VARCHAR(150) DEFAULT '',
                `contact_phone` VARCHAR(50) DEFAULT '',
                `poster_path` VARCHAR(500) DEFAULT '',
                `status` ENUM('Draft', 'Published', 'Closed', 'Archived') NOT NULL DEFAULT 'Draft',
                `published_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `idx_internship_status` (`status`),
                INDEX `idx_internship_deadline` (`application_deadline`),
                INDEX `idx_internship_deleted` (`deleted_at`),
                INDEX `idx_internship_category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    }
} catch(PDOException $e) {
    $db_error = $e->getMessage();
    if (!defined('ALLOW_DB_FAIL')) {
        die("Connection failed: " . $e->getMessage());
    }
}

?>
