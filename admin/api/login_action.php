<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=empty");
        exit;
    }

    // ---- TEMPORARY BYPASS FOR UI TESTING ----
    if (true) { // Always log in
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = 1;
        header("Location: ../dashboard.php");
        exit;
    }
    // -----------------------------------------

    /* 
    // Original DB Code:
    try {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login success
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Login failed
            header("Location: ../login.php?error=invalid");
            exit;
        }
    } catch (PDOException $e) {
        // In production, log this error instead of showing it
        die("Login error: " . $e->getMessage());
    }
    */
} else {
    header("Location: ../login.php");
    exit;
}
?>
