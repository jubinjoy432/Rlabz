<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // Validate basics
    if (empty($title) || empty($year) || empty($description)) {
        header("Location: ../dashboard.php?error=" . urlencode("Title, Year, and Description are required."));
        exit;
    }

    // Generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    $uploadDir = '../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    // Handle Cover Image Upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            header("Location: ../dashboard.php?error=" . urlencode("Invalid cover image format."));
            exit;
        }
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            header("Location: ../dashboard.php?error=" . urlencode("Cover image exceeds 5MB limit."));
            exit;
        }
        $newFilename = uniqid('proj_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
            $image_path = 'uploads/' . $newFilename;
        } else {
            header("Location: ../dashboard.php?error=" . urlencode("Failed to upload cover image."));
            exit;
        }
    } else {
        header("Location: ../dashboard.php?error=" . urlencode("Cover image is required."));
        exit;
    }

    // Handle Poster
    $poster_path = '';
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $pname = uniqid('poster_') . '.' . $ext;
            if (move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDir . $pname)) {
                $poster_path = 'uploads/' . $pname;
            }
        }
    }

    // Insert into database
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO projects (slug, title, year, short_description, description, objectives, problem_statement, expected_outcome, tech_stack, key_features, image_path, thumbnail_path, department, batch, category, project_type, duration, status, github_link, demo_link, poster_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $slug, $title, $year, $short_description, $description,
            $objectives, $problem_statement, $expected_outcome,
            $tech_stack, $key_features, $image_path, $image_path,
            $department, $batch, $category, $project_type, $duration,
            $status, $github_link, $demo_link, $poster_path
        ]);
        
        $project_id = $pdo->lastInsertId();

        // Handle Team Members
        if (isset($_POST['member_names']) && is_array($_POST['member_names'])) {
            $member_names = $_POST['member_names'];
            $member_roles = $_POST['member_roles'] ?? [];
            $member_linkedin = $_POST['member_linkedin'] ?? [];
            $member_photos = $_FILES['member_photos'] ?? null;

            $stmtMember = $pdo->prepare("INSERT INTO project_members (project_id, name, photo_path, role, linkedin_link) VALUES (?, ?, ?, ?, ?)");

            foreach ($member_names as $index => $m_name) {
                $m_name = trim($m_name);
                if (empty($m_name)) continue;

                $m_role = trim($member_roles[$index] ?? '');
                $m_linkedin = trim($member_linkedin[$index] ?? '');
                $m_photo_path = '';

                if (isset($member_photos['name'][$index]) && $member_photos['error'][$index] === UPLOAD_ERR_OK) {
                    $m_ext = strtolower(pathinfo($member_photos['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($m_ext, $allowed)) {
                        $m_filename = uniqid('member_') . '.' . $m_ext;
                        if (move_uploaded_file($member_photos['tmp_name'][$index], $uploadDir . $m_filename)) {
                            $m_photo_path = 'uploads/' . $m_filename;
                        }
                    }
                }
                
                $stmtMember->execute([$project_id, $m_name, $m_photo_path, $m_role, $m_linkedin]);
            }
        }

        // Handle Faculty Members (Dynamic)
        if (isset($_POST['faculty_names']) && is_array($_POST['faculty_names'])) {
            $faculty_names = $_POST['faculty_names'];
            $faculty_designations = $_POST['faculty_designations'] ?? [];
            $faculty_photos = $_FILES['faculty_photos'] ?? null;

            $stmtFac = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path) VALUES (?, ?, ?, ?)");

            foreach ($faculty_names as $index => $f_name) {
                $f_name = trim($f_name);
                if (empty($f_name)) continue;

                $f_designation = trim($faculty_designations[$index] ?? '');
                $f_photo_path = '';

                if (isset($faculty_photos['name'][$index]) && $faculty_photos['error'][$index] === UPLOAD_ERR_OK) {
                    $f_ext = strtolower(pathinfo($faculty_photos['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($f_ext, $allowed)) {
                        $f_filename = uniqid('faculty_') . '.' . $f_ext;
                        if (move_uploaded_file($faculty_photos['tmp_name'][$index], $uploadDir . $f_filename)) {
                            $f_photo_path = 'uploads/' . $f_filename;
                        }
                    }
                }
                
                $stmtFac->execute([$project_id, $f_name, $f_designation, $f_photo_path]);
            }
        }

        // Handle Screenshots
        if (isset($_FILES['screenshots']) && is_array($_FILES['screenshots']['name'])) {
            $stmtScreenshot = $pdo->prepare("INSERT INTO project_screenshots (project_id, image_path, sort_order) VALUES (?, ?, ?)");
            
            foreach ($_FILES['screenshots']['name'] as $index => $sname) {
                if ($_FILES['screenshots']['error'][$index] !== UPLOAD_ERR_OK) continue;
                
                $s_ext = strtolower(pathinfo($sname, PATHINFO_EXTENSION));
                if (!in_array($s_ext, $allowed)) continue;
                
                $s_filename = uniqid('screenshot_') . '.' . $s_ext;
                if (move_uploaded_file($_FILES['screenshots']['tmp_name'][$index], $uploadDir . $s_filename)) {
                    $stmtScreenshot->execute([$project_id, 'uploads/' . $s_filename, $index]);
                }
            }
        }

        // --- Handle SSL Details (Optional) ---
        if (!empty($_POST['ssl_domain'])) {
            $ssl_domain = trim($_POST['ssl_domain']);
            $ssl_provider = isset($_POST['ssl_provider']) ? trim($_POST['ssl_provider']) : '';
            $ssl_issue_date = !empty($_POST['ssl_issue_date']) ? $_POST['ssl_issue_date'] : null;
            $ssl_expiry_date = !empty($_POST['ssl_expiry_date']) ? $_POST['ssl_expiry_date'] : null;

            $stmtSsl = $pdo->prepare("INSERT INTO project_ssl_certs (project_id, domain_url, provider, issue_date, expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stmtSsl->execute([$project_id, $ssl_domain, $ssl_provider, $ssl_issue_date, $ssl_expiry_date]);
        }

        $pdo->commit();
        header("Location: ../dashboard.php?success=1");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../dashboard.php?error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }

} else {
    header("Location: ../dashboard.php");
    exit;
}
?>
