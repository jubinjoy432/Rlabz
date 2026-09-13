<?php
require_once 'api/db.php';

$username = 'farsan';
$password = 'farsan';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:username, :hash) 
                           ON DUPLICATE KEY UPDATE password_hash = :hash");
    $stmt->execute(['hash' => $hash, 'username' => $username]);
    echo "<h1>Admin setup complete!</h1>";
    echo "<p>Username: <strong>$username</strong></p>";
    echo "<p>Password: <strong>$password</strong></p>";
    echo "<p><a href='login.php'>Click here to login</a></p>";
    echo "<p><i>Note: Please delete this setup_admin.php file after logging in for security.</i></p>";
} catch (PDOException $e) {
    echo "Error updating admin user: " . $e->getMessage();
}
?>
