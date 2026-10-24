<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once 'db.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'read');
$id = $_GET['id'] ?? ($_POST['id'] ?? null);

try {
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE admin_announcements SET is_read = 1 WHERE is_read = 0");
        $stmt->execute();
        echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
        exit;
    }

    if ($action === 'delete_read') {
        $stmt = $pdo->prepare("DELETE FROM admin_announcements WHERE is_read = 1");
        $stmt->execute();
        echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
        exit;
    }

    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM admin_announcements WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'unread' && $id) {
        $stmt = $pdo->prepare("UPDATE admin_announcements SET is_read = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE admin_announcements SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Missing ID or action']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
