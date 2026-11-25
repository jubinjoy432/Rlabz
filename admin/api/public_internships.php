<?php
/**
 * Public Internship API
 * Provides endpoints for:
 *  - Live announcement count (action=count)
 *  - Published opportunities listing with filters (action=list)
 *  - Single opportunity details (action=detail&id=...)
 *
 * Rules: Excludes draft, closed, archived/deleted, and expired (deadline < CURDATE()) opportunities.
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

require_once __DIR__ . '/db.php';

if (!function_exists('normalize_asset_url')) {
    function normalize_asset_url($path) {
        if (empty($path)) return '';
        $path = trim($path);
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        // Return relative to site root or uploads folder
        $clean = ltrim($path, '/');
        return '../' . $clean;
    }
}

$action = $_GET['action'] ?? 'list';

try {
    if (!$pdo) {
        throw new Exception("Database connection not available.");
    }

    if ($action === 'count') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM internship_opportunities 
            WHERE status = 'Published' 
              AND deleted_at IS NULL 
              AND application_deadline >= CURDATE()
        ");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();

        $label = "";
        if ($count === 1) {
            $label = "Internship Opportunities";
        } elseif ($count > 1) {
            $label = "Internship Opportunities (" . $count . ")";
        }

        echo json_encode([
            'success' => true,
            'count' => $count,
            'label' => $label,
            'has_opportunities' => ($count > 0)
        ]);
        exit;
    }

    if ($action === 'detail') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid internship ID.']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT * 
            FROM internship_opportunities 
            WHERE id = ? 
              AND status = 'Published' 
              AND deleted_at IS NULL 
              AND application_deadline >= CURDATE()
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $internship = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$internship) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Internship opportunity not found or no longer available.']);
            exit;
        }

        // Format dates
        $deadlineTs = strtotime($internship['application_deadline']);
        $daysLeft = ceil(($deadlineTs - time()) / 86400);

        $data = [
            'id' => (int)$internship['id'],
            'company_name' => $internship['company_name'],
            'company_logo' => !empty($internship['company_logo']) ? normalize_asset_url($internship['company_logo']) : '',
            'company_website' => $internship['company_website'] ?? '',
            'company_location' => $internship['company_location'] ?? '',
            'internship_title' => $internship['internship_title'],
            'category' => $internship['category'],
            'short_description' => $internship['short_description'],
            'description' => $internship['description'],
            'responsibilities' => $internship['responsibilities'] ?? '',
            'requirements' => $internship['requirements'] ?? '',
            'eligibility' => $internship['eligibility'] ?? '',
            'duration' => $internship['duration'],
            'work_mode' => $internship['work_mode'],
            'location' => $internship['location'] ?? '',
            'stipend_type' => $internship['stipend_type'],
            'stipend_amount' => $internship['stipend_amount'] ?? '',
            'openings' => $internship['openings'] ? (int)$internship['openings'] : null,
            'start_date' => $internship['start_date'] ? date('M d, Y', strtotime($internship['start_date'])) : '',
            'application_deadline' => date('M d, Y', $deadlineTs),
            'raw_deadline' => $internship['application_deadline'],
            'days_left' => max(0, (int)$daysLeft),
            'application_email' => $internship['application_email'] ?? '',
            'application_url' => $internship['application_url'] ?? '',
            'application_instructions' => $internship['application_instructions'] ?? '',
            'contact_person' => $internship['contact_person'] ?? '',
            'contact_phone' => $internship['contact_phone'] ?? '',
            'poster_path' => !empty($internship['poster_path']) ? normalize_asset_url($internship['poster_path']) : '',
            'published_at' => $internship['published_at'] ? date('M d, Y', strtotime($internship['published_at'])) : ''
        ];

        echo json_encode([
            'success' => true,
            'internship' => $data,
            'data' => $data
        ]);
        exit;
    }

    // Default action: list
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $workMode = trim($_GET['work_mode'] ?? '');
    $stipendType = trim($_GET['stipend_type'] ?? '');

    $sql = "
        SELECT 
            id, company_name, company_logo, company_website, company_location,
            internship_title, category, short_description, duration, work_mode,
            location, stipend_type, stipend_amount, openings, start_date,
            application_deadline, poster_path, published_at, created_at
        FROM internship_opportunities 
        WHERE status = 'Published' 
          AND deleted_at IS NULL 
          AND application_deadline >= CURDATE()
    ";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (internship_title LIKE ? OR company_name LIKE ? OR category LIKE ? OR short_description LIKE ? OR requirements LIKE ?)";
        $term = "%{$search}%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    if (!empty($workMode)) {
        $sql .= " AND work_mode = ?";
        $params[] = $workMode;
    }

    if (!empty($stipendType)) {
        $sql .= " AND stipend_type = ?";
        $params[] = $stipendType;
    }

    $sql .= " ORDER BY application_deadline ASC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $r) {
        $deadlineTs = strtotime($r['application_deadline']);
        $daysLeft = ceil(($deadlineTs - time()) / 86400);

        $results[] = [
            'id' => (int)$r['id'],
            'company_name' => $r['company_name'],
            'company_logo' => !empty($r['company_logo']) ? normalize_asset_url($r['company_logo']) : '',
            'company_website' => $r['company_website'] ?? '',
            'company_location' => $r['company_location'] ?? '',
            'internship_title' => $r['internship_title'],
            'category' => $r['category'],
            'short_description' => $r['short_description'],
            'duration' => $r['duration'],
            'work_mode' => $r['work_mode'],
            'location' => $r['location'] ?? '',
            'stipend_type' => $r['stipend_type'],
            'stipend_amount' => $r['stipend_amount'] ?? '',
            'openings' => $r['openings'] ? (int)$r['openings'] : null,
            'start_date' => $r['start_date'] ? date('M d, Y', strtotime($r['start_date'])) : '',
            'application_deadline' => date('M d, Y', $deadlineTs),
            'raw_deadline' => $r['application_deadline'],
            'days_left' => max(0, (int)$daysLeft),
            'has_poster' => !empty($r['poster_path']),
            'poster_path' => !empty($r['poster_path']) ? normalize_asset_url($r['poster_path']) : ''
        ];
    }

    // Also get distinct categories from currently published opportunities for filter pills
    $catStmt = $pdo->query("
        SELECT DISTINCT category 
        FROM internship_opportunities 
        WHERE status = 'Published' 
          AND deleted_at IS NULL 
          AND application_deadline >= CURDATE()
        ORDER BY category ASC
    ");
    $availableCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'categories' => $availableCategories,
        'internships' => $results,
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
