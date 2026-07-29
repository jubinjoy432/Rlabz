<?php
// Public API to get all projects for the frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins

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

    // 3.5 Get all faculty members
    $stmtFaculty = $pdo->query("SELECT * FROM project_faculty ORDER BY project_id, id");
    $faculties = $stmtFaculty->fetchAll(PDO::FETCH_ASSOC);

    // 3.6 Get all SSL certs
    $stmtSsl = $pdo->query("SELECT * FROM project_ssl_certs");
    $sslCerts = $stmtSsl->fetchAll(PDO::FETCH_ASSOC);

    // 4. Organize members and screenshots by project ID
    $membersByProject = [];
    foreach ($members as $m) {
        $membersByProject[$m['project_id']][] = [
            'name' => $m['name'],
            'role' => $m['role'],
            'photo' => $m['photo_path'],
            'linkedin' => $m['linkedin_link']
        ];
    }

    $screenshotsByProject = [];
    foreach ($screenshots as $s) {
        $screenshotsByProject[$s['project_id']][] = $s['image_path']; // Simplify for frontend compatibility
    }

    $facultyByProject = [];
    foreach ($faculties as $f) {
        $facultyByProject[$f['project_id']][] = [
            'name' => $f['name'],
            'designation' => $f['designation'],
            'photo' => $f['photo_path']
        ];
    }

    $sslByProject = [];
    foreach ($sslCerts as $s) {
        $sslByProject[$s['project_id']] = [
            'domain_url' => $s['domain_url'],
            'provider' => $s['provider'],
            'issue_date' => $s['issue_date'],
            'expiry_date' => $s['expiry_date']
        ];
    }

    // 5. Attach members and screenshots to their projects, map to frontend expected format
    $frontend_projects = [];
    
    foreach ($projects as $p) {
        $p_id = $p['id'];
        
        // Map database fields to the exact keys expected by the frontend JS
        $mapped_project = [
            'id' => $p['slug'], // The frontend uses slug as the 'id'
            'title' => $p['title'],
            'year' => $p['year'],
            'shortDescription' => $p['short_description'],
            'fullDescription' => $p['description'],
            'objectives' => $p['objectives'],
            'problemStatement' => $p['problem_statement'],
            'expectedOutcome' => $p['expected_outcome'],
            'tech' => !empty($p['tech_stack']) ? array_map('trim', explode(',', $p['tech_stack'])) : [],
            'keyFeatures' => !empty($p['key_features']) ? array_map('trim', explode(',', $p['key_features'])) : [],
            'thumbnail' => $p['thumbnail_path'],
            'screenshots' => $screenshotsByProject[$p_id] ?? [],
            'team' => $membersByProject[$p_id] ?? [],
            'faculty' => $facultyByProject[$p_id] ?? [],
            'department' => $p['department'],
            'batch' => $p['batch'],
            'category' => $p['category'],
            'projectType' => $p['project_type'],
            'duration' => $p['duration'],
            'status' => $p['status'],
            'githubLink' => $p['github_link'],
            'demoLink' => $p['demo_link'],
            'poster' => $p['poster_path']
        ];
        
        $frontend_projects[] = $mapped_project;
    }

    echo json_encode($frontend_projects);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
