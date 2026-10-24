<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once 'db.php';

function normalize_image_url($url) {
    if (empty($url)) return '';
    $url = trim($url);
    // Convert Google Drive share/view/uc links to direct viewable thumbnail URLs
    if (preg_match('/drive\.google\.com\/(?:file\/d\/|open\?id=|uc\?id=)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    return $url;
}

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
    $department = trim($_POST['department'] ?? 'MCA');
    if ($department === 'Other' && !empty(trim($_POST['department_custom'] ?? ''))) {
        $department = trim($_POST['department_custom']);
    }

    $batch = trim($_POST['batch'] ?? '');

    $category = trim($_POST['category'] ?? 'Web Application');
    if ($category === 'Other' && !empty(trim($_POST['category_custom'] ?? ''))) {
        $category = trim($_POST['category_custom']);
    }
    $project_type = trim($_POST['project_type'] ?? 'Academic');
    
    // Validate status: supported values are 'In Development', 'Deployed', 'Completed'
    $valid_statuses = ['In Development', 'Deployed', 'Completed'];
    $status_input = trim($_POST['status'] ?? '');
    $status = in_array($status_input, $valid_statuses) ? $status_input : 'In Development';

    $duration = trim($_POST['duration'] ?? '');
    $github_link = trim($_POST['github_link'] ?? '');
    $demo_link = trim($_POST['demo_link'] ?? '');
    $ssl_tracking_enabled = isset($_POST['ssl_tracking_enabled']) ? 1 : 0;
    
    // Validate basics
    if (empty($title) || empty($year) || empty($description)) {
        header("Location: ../dashboard.php?error=" . urlencode("Title, Year, and Description are required."));
        exit;
    }

    if (empty($short_description)) {
        $short_description = $description;
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

    // Handle Cover Image (Upload OR URL / Drive Link)
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
        }
    } elseif (!empty($_POST['image_url'])) {
        $image_path = normalize_image_url($_POST['image_url']);
    }

    if (empty($image_path)) {
        $image_path = 'assets/images/logo1.png'; // Safe fallback
    }

    // Handle Poster
    $poster_path = '';

    $ssl_tracking_enabled = isset($_POST['ssl_tracking_enabled']) ? 1 : 0;

    // Insert into database
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO projects (slug, title, year, short_description, description, objectives, problem_statement, expected_outcome, tech_stack, key_features, image_path, thumbnail_path, department, batch, category, project_type, duration, status, github_link, demo_link, poster_path, ssl_tracking_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $slug, $title, $year, $short_description, $description,
            $objectives, $problem_statement, $expected_outcome,
            $tech_stack, $key_features, $image_path, $image_path,
            $department, $batch, $category, $project_type, $duration,
            $status, $github_link, $demo_link, $poster_path, $ssl_tracking_enabled
        ]);
        
        $project_id = $pdo->lastInsertId();

        // Handle Team Members (Upload or URL / Drive Link)
        if (isset($_POST['member_names']) && is_array($_POST['member_names'])) {
            $member_names = $_POST['member_names'];
            $member_roles = $_POST['member_roles'] ?? [];
            $member_linkedin = $_POST['member_linkedin'] ?? [];
            $member_photo_urls = $_POST['member_photo_urls'] ?? [];
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
                } elseif (!empty($member_photo_urls[$index])) {
                    $m_photo_path = normalize_image_url($member_photo_urls[$index]);
                }
                
                $stmtMember->execute([$project_id, $m_name, $m_photo_path, $m_role, $m_linkedin]);
            }
        }

        // Handle Faculty Members (Upload or URL / Drive Link)
        if (isset($_POST['faculty_names']) && is_array($_POST['faculty_names'])) {
            $faculty_names = $_POST['faculty_names'];
            $faculty_designations = $_POST['faculty_designations'] ?? [];
            $faculty_linkedin = $_POST['faculty_linkedin'] ?? [];
            $faculty_photo_urls = $_POST['faculty_photo_urls'] ?? [];
            $faculty_photos = $_FILES['faculty_photos'] ?? null;

            $stmtFac = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path, linkedin_link) VALUES (?, ?, ?, ?, ?)");

            foreach ($faculty_names as $index => $f_name) {
                $f_name = trim($f_name);
                if (empty($f_name)) continue;

                $f_designation = trim($faculty_designations[$index] ?? '');
                $f_linkedin = trim($faculty_linkedin[$index] ?? '');
                $f_photo_path = '';

                if (isset($faculty_photos['name'][$index]) && $faculty_photos['error'][$index] === UPLOAD_ERR_OK) {
                    $f_ext = strtolower(pathinfo($faculty_photos['name'][$index], PATHINFO_EXTENSION));
                    if (in_array($f_ext, $allowed)) {
                        $f_filename = uniqid('faculty_') . '.' . $f_ext;
                        if (move_uploaded_file($faculty_photos['tmp_name'][$index], $uploadDir . $f_filename)) {
                            $f_photo_path = 'uploads/' . $f_filename;
                        }
                    }
                } elseif (!empty($faculty_photo_urls[$index])) {
                    $f_photo_path = normalize_image_url($faculty_photo_urls[$index]);
                }
                
                $stmtFac->execute([$project_id, $f_name, $f_designation, $f_photo_path, $f_linkedin]);
            }
        }

        // Handle Screenshots (Files and/or URLs)
        $stmtScreenshot = $pdo->prepare("INSERT INTO project_screenshots (project_id, image_path, sort_order) VALUES (?, ?, ?)");
        $sortOrder = 0;

        if (isset($_FILES['screenshots']) && is_array($_FILES['screenshots']['name'])) {
            foreach ($_FILES['screenshots']['name'] as $index => $sname) {
                if ($_FILES['screenshots']['error'][$index] !== UPLOAD_ERR_OK) continue;
                $sext = strtolower(pathinfo($sname, PATHINFO_EXTENSION));
                if (in_array($sext, $allowed) && $_FILES['screenshots']['size'][$index] <= 5 * 1024 * 1024) {
                    $sfilename = uniqid('ss_') . '.' . $sext;
                    if (move_uploaded_file($_FILES['screenshots']['tmp_name'][$index], $uploadDir . $sfilename)) {
                        $stmtScreenshot->execute([$project_id, 'uploads/' . $sfilename, $sortOrder++]);
                    }
                }
            }
        }

        if (!empty($_POST['screenshot_urls'])) {
            $urls = array_filter(array_map('trim', explode(',', $_POST['screenshot_urls'])));
            foreach ($urls as $url) {
                $normUrl = normalize_image_url($url);
                if (!empty($normUrl)) {
                    $stmtScreenshot->execute([$project_id, $normUrl, $sortOrder++]);
                }
            }
        }
                


        // --- Handle SSL Details (Optional) ---
        // Ensure SSL table exists (idempotent)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `project_ssl_certs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `domain_url` VARCHAR(255) NOT NULL,
            `provider` VARCHAR(100) DEFAULT '',
            `issue_date` DATE DEFAULT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `certificate_fingerprint` VARCHAR(255) DEFAULT NULL,
            `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'Pending',
            `last_error` TEXT DEFAULT NULL,
            `last_alert_level` VARCHAR(50) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        if (!empty($_POST['ssl_domain'])) {
            $ssl_domain = trim($_POST['ssl_domain']);
            $ssl_provider = isset($_POST['ssl_provider']) ? trim($_POST['ssl_provider']) : '';
            $ssl_issue_date = !empty($_POST['ssl_issue_date']) ? $_POST['ssl_issue_date'] : null;
            $ssl_expiry_date = !empty($_POST['ssl_expiry_date']) ? $_POST['ssl_expiry_date'] : null;
            $ssl_fingerprint = !empty($_POST['ssl_fingerprint']) ? $_POST['ssl_fingerprint'] : null;

            $stmtSsl = $pdo->prepare("INSERT INTO project_ssl_certs (project_id, domain_url, provider, issue_date, expiry_date, certificate_fingerprint) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtSsl->execute([$project_id, $ssl_domain, $ssl_provider, $ssl_issue_date, $ssl_expiry_date, $ssl_fingerprint]);
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
    }

?>
