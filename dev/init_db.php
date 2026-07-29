<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // 1. Connect without DB to create DB
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS rlabz_db");
    echo "Database rlabz_db ensured.\n";

    // 2. Connect with DB to run schema
    $pdo = new PDO("mysql:host=$host;dbname=rlabz_db;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema) {
        $pdo->exec($schema);
        echo "Schema imported successfully.\n";
    }

    // 3. Ensure admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES ('admin', ?)");
        $insert->execute([$hash]);
        echo "Admin user (admin / admin123) created successfully.\n";
    } else {
        // Optionally update the password to ensure it's admin123
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
        $update->execute([$hash]);
        echo "Admin user exists. Password reset to admin123.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
