<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        try {
            $stmt = $pdo->query("SELECT id, username, role, created_at FROM admin_users ORDER BY id DESC");
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            exit;
        }

        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                exit;
            }

            // Insert new user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (:username, :password, :role)");
            $stmt->execute([
                'username' => $username,
                'password' => $hash,
                'role' => $role
            ]);

            echo json_encode(['success' => true, 'message' => 'User added successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to add user: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $role = $_POST['role'] ?? 'admin';
        $password = $_POST['password'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }

        try {
            if (!empty($password)) {
                // Update role and password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET role = :role, password_hash = :password WHERE id = :id");
                $stmt->execute(['role' => $role, 'password' => $hash, 'id' => $id]);
            } else {
                // Update role only
                $stmt = $pdo->prepare("UPDATE admin_users SET role = :role WHERE id = :id");
                $stmt->execute(['role' => $role, 'id' => $id]);
            }
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        
        // Prevent deleting oneself
        if ($id == $_SESSION['admin_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
