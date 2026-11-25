<?php
/**
 * Admin API: Add Internship Opportunity
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

// 1. Collect and trim input fields
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

$status = trim($_POST['status'] ?? 'Draft');
$valid_statuses = ['Draft', 'Published', 'Closed'];
if (!in_array($status, $valid_statuses)) {
    $status = 'Draft';
}

// 2. Validate Required Fields
$errors = [];
if (empty($company_name)) $errors[] = "Company Name is required.";
if (empty($internship_title)) $errors[] = "Internship Title is required.";
if (empty($category)) $errors[] = "Category / Domain is required.";
if (empty($description)) $errors[] = "Full Description is required.";
if (empty($application_deadline)) $errors[] = "Application Deadline is required.";

if (empty($short_description) && !empty($description)) {
    $short_description = substr(strip_tags($description), 0, 200) . '...';
}

// Validate email format if provided
if (!empty($application_email) && !filter_var($application_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid application email address.";
}

if (!empty($errors)) {
    header("Location: ../pages/add_internship.php?error=" . urlencode(implode(' ', $errors)));
    exit;
}

// 3. File Upload Handling (Uploads/internships)
$uploadDir = __DIR__ . '/../../uploads/internships/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

// Handle Company Logo
$company_logo = '';
if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        header("Location: ../pages/add_internship.php?error=" . urlencode("Invalid logo format. Allowed: JPG, PNG, WEBP, GIF."));
        exit;
    }
    if ($_FILES['company_logo']['size'] > 5 * 1024 * 1024) {
        header("Location: ../pages/add_internship.php?error=" . urlencode("Logo file size exceeds 5MB limit."));
        exit;
    }
    $logoFilename = 'logo_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadDir . $logoFilename)) {
        $company_logo = 'uploads/internships/' . $logoFilename;
    }
} elseif (!empty(trim($_POST['company_logo_url'] ?? ''))) {
    $company_logo = trim($_POST['company_logo_url']);
}

// Handle Poster
$poster_path = '';
if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        header("Location: ../pages/add_internship.php?error=" . urlencode("Invalid poster format. Allowed: JPG, PNG, WEBP, GIF."));
        exit;
    }
    if ($_FILES['poster']['size'] > 10 * 1024 * 1024) {
        header("Location: ../pages/add_internship.php?error=" . urlencode("Poster file size exceeds 10MB limit."));
        exit;
    }
    $posterFilename = 'poster_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDir . $posterFilename)) {
        $poster_path = 'uploads/internships/' . $posterFilename;
    }
}

$published_at = ($status === 'Published') ? date('Y-m-d H:i:s') : null;

// 4. Insert into database
try {
    $sql = "INSERT INTO internship_opportunities (
        company_name, company_logo, company_website, company_location,
        internship_title, category, short_description, description,
        responsibilities, requirements, eligibility, duration,
        work_mode, location, stipend_type, stipend_amount,
        openings, start_date, application_deadline, application_email,
        application_url, application_instructions, contact_person, contact_phone,
        poster_path, status, published_at
    ) VALUES (
        :company_name, :company_logo, :company_website, :company_location,
        :internship_title, :category, :short_description, :description,
        :responsibilities, :requirements, :eligibility, :duration,
        :work_mode, :location, :stipend_type, :stipend_amount,
        :openings, :start_date, :application_deadline, :application_email,
        :application_url, :application_instructions, :contact_person, :contact_phone,
        :poster_path, :status, :published_at
    )";

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
        ':published_at' => $published_at
    ]);

    header("Location: ../pages/manage_internships.php?success=" . urlencode("Internship opportunity successfully added."));
    exit;

} catch (PDOException $e) {
    header("Location: ../pages/add_internship.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}
