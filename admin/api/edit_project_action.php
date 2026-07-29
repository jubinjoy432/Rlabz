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
            status = ?, duration = ?, 
            github_link = ?, demo_link = ?, image_path = ?, thumbnail_path = ?, poster_path = ?
            WHERE id = ?");
        
        $stmt->execute([
            $slug, $title, $year, $short_description, $description,
            $objectives, $problem_statement, $expected_outcome, $tech_stack,
            $key_features, $department, $batch, $category, $project_type,
            $status, $duration,
            $github_link, $demo_link, $image_path, $thumbnail_path, $poster_path,
            $id
        ]);

        // Process team members (Dynamic rows)
        if (isset($_POST['member_names']) && is_array($_POST['member_names'])) {
            $member_names = $_POST['member_names'];
            $member_roles = $_POST['member_roles'] ?? [];
            $member_linkedin = $_POST['member_linkedin'] ?? [];
            $existing_photos = $_POST['existing_photos'] ?? [];
            $member_photos = $_FILES['member_photos'] ?? null;
            
            // Delete old members
            $delStmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ?");
            $delStmt->execute([$id]);

            $memStmt = $pdo->prepare("INSERT INTO project_members (project_id, name, role, photo_path, linkedin_link) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($member_names as $index => $m_name) {
                $m_name = trim($m_name);
                if (empty($m_name)) continue;

                $m_role = trim($member_roles[$index] ?? '');
                $m_linkedin = trim($member_linkedin[$index] ?? '');
                $m_photo_path = trim($existing_photos[$index] ?? '');

                if (isset($member_photos['name'][$index]) && $member_photos['error'][$index] === UPLOAD_ERR_OK) {
                    $m_ext = strtolower(pathinfo($member_photos['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($m_ext, $allowed)) {
                        $m_filename = uniqid('member_') . '.' . $m_ext;
                        if (move_uploaded_file($member_photos['tmp_name'][$index], $uploadDir . $m_filename)) {
                            $m_photo_path = 'uploads/' . $m_filename;
                        }
                    }
                }
                
                $memStmt->execute([$id, $m_name, $m_role, $m_photo_path, $m_linkedin]);
            }
        }

        // Process faculty members (Dynamic rows)
        if (isset($_POST['faculty_names']) && is_array($_POST['faculty_names'])) {
            $faculty_names = $_POST['faculty_names'];
            $faculty_designations = $_POST['faculty_designations'] ?? [];
            $existing_faculty_photos = $_POST['existing_faculty_photos'] ?? [];
            $faculty_photos = $_FILES['faculty_photos'] ?? null;
            
            // Delete old faculty
            $delFacStmt = $pdo->prepare("DELETE FROM project_faculty WHERE project_id = ?");
            $delFacStmt->execute([$id]);

            $facStmt = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path) VALUES (?, ?, ?, ?)");
            
            foreach ($faculty_names as $index => $f_name) {
                $f_name = trim($f_name);
                if (empty($f_name)) continue;

                $f_designation = trim($faculty_designations[$index] ?? '');
                $f_photo_path = trim($existing_faculty_photos[$index] ?? '');

                if (isset($faculty_photos['name'][$index]) && $faculty_photos['error'][$index] === UPLOAD_ERR_OK) {
                    $f_ext = strtolower(pathinfo($faculty_photos['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($f_ext, $allowed)) {
                        $f_filename = uniqid('faculty_') . '.' . $f_ext;
                        if (move_uploaded_file($faculty_photos['tmp_name'][$index], $uploadDir . $f_filename)) {
                            $f_photo_path = 'uploads/' . $f_filename;
                        }
                    }
                }
                
                $facStmt->execute([$id, $f_name, $f_designation, $f_photo_path]);
            }
        }

        // --- Handle SSL Details ---
        $ssl_domain = isset($_POST['ssl_domain']) ? trim($_POST['ssl_domain']) : '';
        $pdo->prepare("DELETE FROM project_ssl_certs WHERE project_id = ?")->execute([$id]);
        if (!empty($ssl_domain)) {
            $ssl_provider = isset($_POST['ssl_provider']) ? trim($_POST['ssl_provider']) : '';
            $ssl_issue_date = !empty($_POST['ssl_issue_date']) ? $_POST['ssl_issue_date'] : null;
            $ssl_expiry_date = !empty($_POST['ssl_expiry_date']) ? $_POST['ssl_expiry_date'] : null;
            $stmtSsl = $pdo->prepare("INSERT INTO project_ssl_certs (project_id, domain_url, provider, issue_date, expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stmtSsl->execute([$id, $ssl_domain, $ssl_provider, $ssl_issue_date, $ssl_expiry_date]);
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
