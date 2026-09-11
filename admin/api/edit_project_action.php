<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        header("Location: ../dashboard.php?error=" . urlencode("Project ID is missing."));
        exit;
    }

    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $objectives = trim($_POST['objectives'] ?? '');
    $problem_statement = trim($_POST['problem_statement'] ?? '');
    $expected_outcome = trim($_POST['expected_outcome'] ?? '');
    $tech_stack = trim($_POST['tech_stack'] ?? '');
    $key_features = trim($_POST['key_features'] ?? '');
    $department = trim($_POST['department'] ?? 'MCA');
    $batch = trim($_POST['batch'] ?? '');
    $category = trim($_POST['category'] ?? 'Web Application');
    $project_type = trim($_POST['project_type'] ?? 'Academic');
    $status = trim($_POST['status'] ?? 'Completed');
    $duration = trim($_POST['duration'] ?? '');
    $faculty_name = trim($_POST['faculty_name'] ?? '');
    $faculty_designation = trim($_POST['faculty_designation'] ?? '');
    $github_link = trim($_POST['github_link'] ?? '');
    $demo_link = trim($_POST['demo_link'] ?? '');
    
    if (empty($title) || empty($year) || empty($description)) {
        header("Location: ../edit_project.php?id=$id&error=" . urlencode("Title, Year, and Description are required."));
        exit;
    }

    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    // Fetch existing project to get current image paths
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        header("Location: ../dashboard.php?error=" . urlencode("Project not found."));
        exit;
    }

    $uploadDir = '../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    function handleUpload($fileInputName, $existingPath) {
        global $allowed, $uploadDir, $id;
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES[$fileInputName]['size'] <= 5 * 1024 * 1024) {
                $filename = uniqid($fileInputName . '_') . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $uploadDir . $filename)) {
                    return 'uploads/' . $filename;
                }
            }
        }
        return $existingPath;
    }

    $image_path = handleUpload('image', $project['image_path']);
    $thumbnail_path = handleUpload('thumbnail', $project['thumbnail_path']);
    $poster_path = handleUpload('poster', $project['poster_path']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE projects SET 
            slug = ?, title = ?, year = ?, short_description = ?, description = ?, 
            objectives = ?, problem_statement = ?, expected_outcome = ?, tech_stack = ?, 
            key_features = ?, department = ?, batch = ?, category = ?, project_type = ?, 
            status = ?, duration = ?, faculty_name = ?, faculty_designation = ?, 
            github_link = ?, demo_link = ?, image_path = ?, thumbnail_path = ?, poster_path = ?
            WHERE id = ?");
        
        $stmt->execute([
            $slug, $title, $year, $short_description, $description,
            $objectives, $problem_statement, $expected_outcome, $tech_stack,
            $key_features, $department, $batch, $category, $project_type,
            $status, $duration, $faculty_name, $faculty_designation,
            $github_link, $demo_link, $image_path, $thumbnail_path, $poster_path,
            $id
        ]);

        // Process team members (simple comma separated string replacement)
        if (isset($_POST['team_members_str'])) {
            $members_str = $_POST['team_members_str'];
            $names = array_filter(array_map('trim', explode(',', $members_str)));
            
            // Delete old members
            $delStmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ?");
            $delStmt->execute([$id]);

            if (!empty($names)) {
                $memStmt = $pdo->prepare("INSERT INTO project_members (project_id, name, role, photo_path) VALUES (?, ?, 'Member', '')");
                foreach ($names as $name) {
                    $memStmt->execute([$id, $name]);
                }
            }
        }

        $pdo->commit();
        header("Location: ../edit_project.php?id=$id&success=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: ../edit_project.php?id=$id&error=" . urlencode("Database Error: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../dashboard.php");
    exit;
}
