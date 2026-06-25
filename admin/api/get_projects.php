<?php
// API endpoint to fetch projects as JSON
header('Content-Type: application/json');
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY year ASC, id ASC");
    $projects = $stmt->fetchAll();
    
    // Fetch all members to easily attach them
    $stmtMembers = $pdo->query("SELECT * FROM project_members");
    $allMembers = $stmtMembers->fetchAll();
    
    // Group members by project_id
    $membersByProject = [];
    foreach ($allMembers as $m) {
        $membersByProject[$m['project_id']][] = [
            'name' => $m['name'],
            'photo' => $m['photo_path']
        ];
    }

    // Convert tech_stack string to array and attach members
    foreach ($projects as &$p) {
        if (!empty($p['tech_stack'])) {
            $p['tech'] = array_map('trim', explode(',', $p['tech_stack']));
        } else {
            $p['tech'] = [];
        }
        $p['team'] = $membersByProject[$p['id']] ?? [];
    }
    
    echo json_encode(['success' => true, 'data' => $projects]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
