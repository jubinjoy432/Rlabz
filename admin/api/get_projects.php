<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db.php';

try {
    // 1. Get all projects
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY year DESC, id DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get all members
    $stmtMembers = $pdo->query("SELECT * FROM project_members ORDER BY project_id, id");
    $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get all screenshots
    $stmtScreenshots = $pdo->query("SELECT * FROM project_screenshots ORDER BY project_id, sort_order");
    $screenshots = $stmtScreenshots->fetchAll(PDO::FETCH_ASSOC);

    // 4. Organize members and screenshots by project ID
    $membersByProject = [];
    foreach ($members as $m) {
        $membersByProject[$m['project_id']][] = $m;
    }

    $screenshotsByProject = [];
    foreach ($screenshots as $s) {
        $screenshotsByProject[$s['project_id']][] = $s;
    }

    // 5. Attach members and screenshots to their projects, parse JSON arrays
    foreach ($projects as &$p) {
        $p_id = $p['id'];
        $p['team'] = $membersByProject[$p_id] ?? [];
        $p['screenshots'] = $screenshotsByProject[$p_id] ?? [];
        
        // Ensure arrays are proper format
        if (!empty($p['tech_stack'])) {
            $p['tech'] = array_map('trim', explode(',', $p['tech_stack']));
        } else {
            $p['tech'] = [];
        }

        if (!empty($p['key_features'])) {
            $p['features'] = array_map('trim', explode(',', $p['key_features']));
        } else {
            $p['features'] = [];
        }
    }

    echo json_encode($projects);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
