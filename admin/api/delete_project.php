<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../pages/manage_projects.php?error=" . urlencode("Project ID not provided for deletion."));
    exit;
}

$id = intval($_GET['id']);

try {
    // 1. Fetch project to get the main image paths
    $stmt = $pdo->prepare("SELECT image_path, thumbnail_path, poster_path FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if (!$project) {
        header("Location: ../pages/manage_projects.php?error=" . urlencode("Project not found."));
        exit;
    }

    // 2. Fetch member photos
    $stmtMembers = $pdo->prepare("SELECT photo_path FROM project_members WHERE project_id = ?");
    $stmtMembers->execute([$id]);
    $members = $stmtMembers->fetchAll();

    // 2b. Fetch faculty photos
    $stmtFaculty = $pdo->prepare("SELECT photo_path FROM project_faculty WHERE project_id = ?");
    $stmtFaculty->execute([$id]);
    $faculty = $stmtFaculty->fetchAll();

    // 3. Fetch screenshots
    $stmtScreenshots = $pdo->prepare("SELECT image_path FROM project_screenshots WHERE project_id = ?");
    $stmtScreenshots->execute([$id]);
    $screenshots = $stmtScreenshots->fetchAll();

    // 4. Safe file deletion helper: ONLY delete files located in 'uploads/'
    // NEVER delete files in assets/ (such as logo1.png) or external URLs
    $safeDeleteFile = function($relativePath) {
        if (empty($relativePath)) return;
        $clean = trim(str_replace('\\', '/', $relativePath));
        // Strictly allow only files inside the 'uploads/' folder
        if (strpos($clean, 'uploads/') === 0) {
            $fullPath = __DIR__ . '/../../' . $clean;
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    };

    $safeDeleteFile($project['image_path'] ?? '');
    if (!empty($project['thumbnail_path']) && $project['thumbnail_path'] !== $project['image_path']) {
        $safeDeleteFile($project['thumbnail_path']);
    }
    $safeDeleteFile($project['poster_path'] ?? '');

    foreach ($members as $m) {
        $safeDeleteFile($m['photo_path'] ?? '');
    }

    foreach ($faculty as $f) {
        $safeDeleteFile($f['photo_path'] ?? '');
    }

    foreach ($screenshots as $s) {
        $safeDeleteFile($s['image_path'] ?? '');
    }

    // 5. Delete from database
    $pdo->beginTransaction();
    
    $pdo->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM project_faculty WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM project_screenshots WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM project_ssl_certs WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM project_milestones WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    
    $pdo->commit();

    header("Location: ../pages/manage_projects.php?success=" . urlencode("Project deleted successfully."));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../pages/manage_projects.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}
?>
