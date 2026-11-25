<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic rate-limiting / brute-force throttle
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt'] = time();
    }

    // Reset attempts if cooldown period (30 seconds) has elapsed
    if (time() - $_SESSION['last_attempt'] > 30) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['last_attempt'] = time();
        header("Location: ../login.php?error=locked");
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=empty");
        exit;
    }

    if (strlen($username) < 3 || strlen($password) < 6) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt'] = time();
        header("Location: ../login.php?error=invalid");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login success - clear rate limits
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt']);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Login failed
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            header("Location: ../login.php?error=invalid");
            exit;
        }
    } catch (PDOException $e) {
        // In production, log this error instead of showing it
        die("Login error: " . $e->getMessage());
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
