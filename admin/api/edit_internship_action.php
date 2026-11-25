<?php
/**
 * Admin API: Edit Internship Action
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/manage_internships.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: ../pages/manage_internships.php?error=" . urlencode("Invalid internship ID."));
    exit;
}

// Fetch existing record
$stmt = $pdo->prepare("SELECT * FROM internship_opportunities WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    header("Location: ../pages/manage_internships.php?error=" . urlencode("Internship record not found."));
    exit;
}

// 1. Collect inputs
$company_name = trim($_POST['company_name'] ?? '');
$company_website = trim($_POST['company_website'] ?? '');
$company_location = trim($_POST['company_location'] ?? '');

$internship_title = trim($_POST['internship_title'] ?? '');
$category = trim($_POST['category'] ?? '');
if ($category === 'Other' && !empty(trim($_POST['category_custom'] ?? ''))) {
    $category = trim($_POST['category_custom']);
}

$short_description = trim($_POST['short_description'] ?? '');
$description = trim($_POST['description'] ?? '');
$responsibilities = trim($_POST['responsibilities'] ?? '');
$requirements = trim($_POST['requirements'] ?? '');
$eligibility = trim($_POST['eligibility'] ?? '');

$duration = trim($_POST['duration'] ?? '');
$work_mode = trim($_POST['work_mode'] ?? 'Remote');
$location = trim($_POST['location'] ?? '');
$stipend_type = trim($_POST['stipend_type'] ?? 'Paid');
$stipend_amount = trim($_POST['stipend_amount'] ?? '');
$openings = !empty($_POST['openings']) ? (int)$_POST['openings'] : null;
$start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$application_deadline = trim($_POST['application_deadline'] ?? '');

$application_email = trim($_POST['application_email'] ?? '');
$application_url = trim($_POST['application_url'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$contact_phone = trim($_POST['contact_phone'] ?? '');
$application_instructions = trim($_POST['application_instructions'] ?? '');

$status = trim($_POST['status'] ?? $existing['status']);
$valid_statuses = ['Draft', 'Published', 'Closed'];
if (!in_array($status, $valid_statuses)) {
    $status = $existing['status'];
}

// 2. Validate
$errors = [];
if (empty($company_name)) $errors[] = "Company Name is required.";
if (empty($internship_title)) $errors[] = "Internship Title is required.";
if (empty($category)) $errors[] = "Category / Domain is required.";
if (empty($description)) $errors[] = "Full Description is required.";
if (empty($application_deadline)) $errors[] = "Application Deadline is required.";

if (empty($short_description) && !empty($description)) {
    $short_description = substr(strip_tags($description), 0, 200) . '...';
}

if (!empty($application_email) && !filter_var($application_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid application email address.";
}

if (!empty($errors)) {
    header("Location: ../pages/edit_internship.php?id=" . $id . "&error=" . urlencode(implode(' ', $errors)));
    exit;
}

// 3. File upload handling
$uploadDir = __DIR__ . '/../../uploads/internships/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

// Safe delete file helper
$safeDeleteFile = function($path) {
    if (empty($path)) return;
    $clean = trim(str_replace('\\', '/', $path));
    if (strpos($clean, 'uploads/internships/') === 0 || strpos($clean, 'uploads/') === 0) {
        $fullPath = __DIR__ . '/../../' . $clean;
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
};

// Company Logo
$company_logo = $existing['company_logo'];
if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
    $safeDeleteFile($company_logo);
    $company_logo = '';
}
if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExts) && $_FILES['company_logo']['size'] <= 5 * 1024 * 1024) {
        $safeDeleteFile($company_logo);
        $logoFilename = 'logo_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadDir . $logoFilename)) {
            $company_logo = 'uploads/internships/' . $logoFilename;
        }
    }
} elseif (!empty(trim($_POST['company_logo_url'] ?? ''))) {
    $company_logo = trim($_POST['company_logo_url']);
}

// Poster
$poster_path = $existing['poster_path'];
if (isset($_POST['remove_poster']) && $_POST['remove_poster'] == '1') {
    $safeDeleteFile($poster_path);
    $poster_path = '';
}
if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExts) && $_FILES['poster']['size'] <= 10 * 1024 * 1024) {
        $safeDeleteFile($poster_path);
        $posterFilename = 'poster_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDir . $posterFilename)) {
            $poster_path = 'uploads/internships/' . $posterFilename;
        }
    }
}

// Published timestamp
$published_at = $existing['published_at'];
if ($status === 'Published' && empty($published_at)) {
    $published_at = date('Y-m-d H:i:s');
}

// 4. Update Database
try {
    $sql = "UPDATE internship_opportunities SET 
        company_name = :company_name,
        company_logo = :company_logo,
        company_website = :company_website,
        company_location = :company_location,
        internship_title = :internship_title,
        category = :category,
        short_description = :short_description,
        description = :description,
        responsibilities = :responsibilities,
        requirements = :requirements,
        eligibility = :eligibility,
        duration = :duration,
        work_mode = :work_mode,
        location = :location,
        stipend_type = :stipend_type,
        stipend_amount = :stipend_amount,
        openings = :openings,
        start_date = :start_date,
        application_deadline = :application_deadline,
        application_email = :application_email,
        application_url = :application_url,
        application_instructions = :application_instructions,
        contact_person = :contact_person,
        contact_phone = :contact_phone,
        poster_path = :poster_path,
        status = :status,
        published_at = :published_at,
        updated_at = NOW()
    WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':company_name' => $company_name,
        ':company_logo' => $company_logo,
        ':company_website' => $company_website,
        ':company_location' => $company_location,
        ':internship_title' => $internship_title,
        ':category' => $category,
        ':short_description' => $short_description,
        ':description' => $description,
        ':responsibilities' => $responsibilities,
        ':requirements' => $requirements,
        ':eligibility' => $eligibility,
        ':duration' => $duration,
        ':work_mode' => $work_mode,
        ':location' => $location,
        ':stipend_type' => $stipend_type,
        ':stipend_amount' => $stipend_amount,
        ':openings' => $openings,
        ':start_date' => $start_date,
        ':application_deadline' => $application_deadline,
        ':application_email' => $application_email,
        ':application_url' => $application_url,
        ':application_instructions' => $application_instructions,
        ':contact_person' => $contact_person,
        ':contact_phone' => $contact_phone,
        ':poster_path' => $poster_path,
        ':status' => $status,
        ':published_at' => $published_at,
        ':id' => $id
    ]);

    header("Location: ../pages/manage_internships.php?success=" . urlencode("Internship updated successfully."));
    exit;

} catch (PDOException $e) {
    header("Location: ../pages/edit_internship.php?id=" . $id . "&error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}
