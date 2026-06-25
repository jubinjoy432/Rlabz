<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // First get the image path so we can delete the file
        $stmt = $pdo->prepare("SELECT image_path FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();

        if ($project) {
            // Delete the main project image file if it exists
            if (!empty($project['image_path']) && strpos($project['image_path'], 'uploads/') === 0) {
                $filePath = '../../' . $project['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Get all member photos to delete them
            $stmtMembers = $pdo->prepare("SELECT photo_path FROM project_members WHERE project_id = ?");
            $stmtMembers->execute([$id]);
            $members = $stmtMembers->fetchAll();

            foreach ($members as $m) {
                if (!empty($m['photo_path']) && strpos($m['photo_path'], 'uploads/') === 0) {
                    $mPath = '../../' . $m['photo_path'];
                    if (file_exists($mPath)) {
                        unlink($mPath);
                    }
                }
            }

            // Delete from database (this will cascade delete project_members)
            $deleteStmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $deleteStmt->execute([$id]);
        }
        
        header("Location: ../dashboard.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: ../dashboard.php?error=" . urlencode("Failed to delete: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../dashboard.php");
    exit;
}
?>
