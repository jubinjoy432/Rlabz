<?php
require_once '../admin/api/db.php';
echo "DB connected successfully!\n";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Existing tables: " . (empty($tables) ? 'none' : implode(', ', $tables)) . "\n";
