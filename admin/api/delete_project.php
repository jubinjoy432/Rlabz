<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../dashboard.php?error=" . urlencode("Project ID not provided for deletion."));
    exit;
}

$id = intval($_GET['id']);

try {
    // 1. Fetch project to get the main image paths
    $stmt = $pdo->prepare("SELECT image_path, thumbnail_path, poster_path, faculty_photo FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if (!$project) {
        header("Location: ../dashboard.php?error=" . urlencode("Project not found."));
        exit;
    }

    // 2. Fetch member photos
    $stmtMembers = $pdo->prepare("SELECT photo_path FROM project_members WHERE project_id = ?");
    $stmtMembers->execute([$id]);
    $members = $stmtMembers->fetchAll();

    // 3. Fetch screenshots
    $stmtScreenshots = $pdo->prepare("SELECT image_path FROM project_screenshots WHERE project_id = ?");
    $stmtScreenshots->execute([$id]);
    $screenshots = $stmtScreenshots->fetchAll();

    // 4. Delete files from disk
    $filesToDelete = [];
    
    if (!empty($project['image_path'])) $filesToDelete[] = '../../' . $project['image_path'];
    if (!empty($project['thumbnail_path']) && $project['thumbnail_path'] !== $project['image_path']) {
        $filesToDelete[] = '../../' . $project['thumbnail_path'];
    }
    if (!empty($project['poster_path'])) $filesToDelete[] = '../../' . $project['poster_path'];
    if (!empty($project['faculty_photo'])) $filesToDelete[] = '../../' . $project['faculty_photo'];

    foreach ($members as $m) {
        if (!empty($m['photo_path'])) {
            $filesToDelete[] = '../../' . $m['photo_path'];
        }
    }

    foreach ($screenshots as $s) {
        if (!empty($s['image_path'])) {
            $filesToDelete[] = '../../' . $s['image_path'];
        }
    }

    foreach ($filesToDelete as $file) {
        if (file_exists($file) && is_file($file)) {
            @unlink($file);
        }
    }

    // 5. Delete from database (Foreign keys ON DELETE CASCADE will handle members and screenshots if set up)
    // But just to be safe if cascade isn't set, delete children first
    
    $pdo->beginTransaction();
    
    $pdo->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM project_screenshots WHERE project_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    
    $pdo->commit();

    header("Location: ../dashboard.php?success=" . urlencode("Project deleted successfully."));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../dashboard.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}
?>
