<?php
/**
 * Admin API: Delete (Soft-Delete) Internship
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/db.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if ($id <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid internship ID.']);
        exit;
    }
    header("Location: ../pages/manage_internships.php?error=" . urlencode("Invalid internship ID."));
    exit;
}

try {
    // Helper to safely delete file from uploads
    $safeDelete = function($path) {
        if (empty($path)) return;
        $clean = trim(str_replace('\\', '/', $path));
        if (strpos($clean, 'uploads/internships/') === 0 || strpos($clean, 'uploads/') === 0) {
            $fullPath = __DIR__ . '/../../' . $clean;
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    };

    // Check if permanent delete requested or standard soft-delete
    $purge = isset($_GET['purge']) && $_GET['purge'] == '1';
    if ($purge) {
        $stmtF = $pdo->prepare("SELECT company_logo, poster_path FROM internship_opportunities WHERE id = ?");
        $stmtF->execute([$id]);
        $files = $stmtF->fetch(PDO::FETCH_ASSOC);
        if ($files) {
            $safeDelete($files['company_logo'] ?? '');
            $safeDelete($files['poster_path'] ?? '');
        }
        $stmt = $pdo->prepare("DELETE FROM internship_opportunities WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        // Soft-delete: sets deleted_at so it is excluded from public and admin active views
        $stmt = $pdo->prepare("UPDATE internship_opportunities SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Internship deleted successfully.']);
        exit;
    }

    header("Location: ../pages/manage_internships.php?success=" . urlencode("Internship deleted successfully."));
    exit;

} catch (PDOException $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
    header("Location: ../pages/manage_internships.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}
