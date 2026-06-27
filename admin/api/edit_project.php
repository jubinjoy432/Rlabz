<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

// Check if project ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../dashboard.php?error=" . urlencode("Project ID not provided for editing."));
    exit;
}

$id = intval($_GET['id']);

// Placeholder for full edit functionality.
// In a full implementation, this page would show a form populated with existing data,
// similar to the Add Project form, and process the update on POST.
// Since the instruction was to "Create edit_project.php", providing the foundational file.

try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if (!$project) {
        header("Location: ../dashboard.php?error=" . urlencode("Project not found."));
        exit;
    }
} catch (PDOException $e) {
    header("Location: ../dashboard.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Project - RLabz Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #fff; padding: 2rem; }
        .alert { background: rgba(14,165,233,0.1); border: 1px solid rgba(14,165,233,0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        a { color: #38bdf8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="alert">
        <i class="fa-solid fa-tools"></i> 
        Edit Project functionality for "<strong><?php echo htmlspecialchars($project['title']); ?></strong>" is under construction.
        <br><br>
        <a href="../dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
