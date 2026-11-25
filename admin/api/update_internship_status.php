<?php
/**
 * Admin API: Quick Status Toggle for Internships (Publish / Close / Reopen / Draft)
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/db.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
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
    // Fetch current record
    $stmt = $pdo->prepare("SELECT * FROM internship_opportunities WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $msg = "Internship not found or already deleted.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        header("Location: ../pages/manage_internships.php?error=" . urlencode($msg));
        exit;
    }

    $newStatus = $item['status'];
    $setPublishedAt = false;

    switch (strtolower($action)) {
        case 'publish':
        case 'reopen':
            // Verify at least one contact method
            if (empty(trim($item['application_email'] ?? '')) && empty(trim($item['application_url'] ?? ''))) {
                $msg = "Cannot publish: At least one application contact method (email or URL) is required.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                header("Location: ../pages/manage_internships.php?error=" . urlencode($msg));
                exit;
            }
            $newStatus = 'Published';
            $setPublishedAt = empty($item['published_at']);
            break;

        case 'close':
            $newStatus = 'Closed';
            break;

        case 'draft':
            $newStatus = 'Draft';
            break;

        default:
            $msg = "Invalid status action.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            header("Location: ../pages/manage_internships.php?error=" . urlencode($msg));
            exit;
    }

    if ($setPublishedAt) {
        $updateStmt = $pdo->prepare("UPDATE internship_opportunities SET status = ?, published_at = NOW(), updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
    } else {
        $updateStmt = $pdo->prepare("UPDATE internship_opportunities SET status = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
    }

    $msg = "Internship status updated to " . $newStatus . ".";

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'status' => $newStatus
        ]);
        exit;
    }

    header("Location: ../pages/manage_internships.php?success=" . urlencode($msg));
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
