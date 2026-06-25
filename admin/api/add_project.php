<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tech_stack = trim($_POST['tech_stack'] ?? '');
    $icon_html = trim($_POST['icon_html'] ?? '<i class="fa-solid fa-code"></i>');
    
    // Validate basics
    if (empty($title) || empty($year) || empty($description)) {
        header("Location: ../dashboard.php?error=" . urlencode("Title, Year, and Description are required."));
        exit;
    }

    // Handle Image Upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/'; // The physical folder on server relative to this file
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['image']['name']);
        $ext = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allowed)) {
            header("Location: ../dashboard.php?error=" . urlencode("Invalid image format."));
            exit;
        }

        // Generate a unique filename
        $newFilename = uniqid('proj_') . '.' . $ext;
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            // Path to store in DB (relative to root index.html)
            $image_path = 'uploads/' . $newFilename;
        } else {
            header("Location: ../dashboard.php?error=" . urlencode("Failed to move uploaded file. Check folder permissions."));
            exit;
        }
    } else {
        header("Location: ../dashboard.php?error=" . urlencode("Image upload is required or an error occurred."));
        exit;
    }

    // Insert into projects database
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO projects (title, year, description, tech_stack, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $year, $description, $tech_stack, $image_path]);
        
        $project_id = $pdo->lastInsertId();

        // Handle Team Members
        if (isset($_POST['member_names']) && is_array($_POST['member_names'])) {
            $member_names = $_POST['member_names'];
            $member_photos = $_FILES['member_photos'] ?? null;

            $stmtMember = $pdo->prepare("INSERT INTO project_members (project_id, name, photo_path) VALUES (?, ?, ?)");

            foreach ($member_names as $index => $m_name) {
                $m_name = trim($m_name);
                if (empty($m_name)) continue;

                $m_photo_path = '';
                if (isset($member_photos['name'][$index]) && $member_photos['error'][$index] === UPLOAD_ERR_OK) {
                    $m_ext = strtolower(pathinfo($member_photos['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($m_ext, $allowed)) {
                        $m_filename = uniqid('member_') . '.' . $m_ext;
                        $m_dest = $uploadDir . $m_filename;
                        if (move_uploaded_file($member_photos['tmp_name'][$index], $m_dest)) {
                            $m_photo_path = 'uploads/' . $m_filename;
                        }
                    }
                }
                
                // Only insert if photo was successfully uploaded
                if (!empty($m_photo_path)) {
                    $stmtMember->execute([$project_id, $m_name, $m_photo_path]);
                }
            }
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
