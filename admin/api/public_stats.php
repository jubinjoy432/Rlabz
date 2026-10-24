<?php
// admin/api/public_stats.php
// Public API returning dynamic and accurate project statistics for the frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

try {
    // 1. Overall status counts
    $stmtStatus = $pdo->query("
        SELECT 
            COUNT(*) AS total_projects,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_projects,
            SUM(CASE WHEN status = 'In Development' THEN 1 ELSE 0 END) AS in_dev_projects
        FROM projects
    ");
    $overall = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    // 2. Team members count
    $stmtMembers = $pdo->query("SELECT COUNT(*) FROM project_members");
    $totalMembers = (int) $stmtMembers->fetchColumn();

    // 3. Year-based completed and overall breakdown
    $stmtYears = $pdo->query("
        SELECT 
            year,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'In Development' THEN 1 ELSE 0 END) AS in_dev,
            COUNT(*) AS total
        FROM projects
        WHERE year IS NOT NULL AND year != ''
        GROUP BY year
        ORDER BY year DESC
    ");
    $yearsData = $stmtYears->fetchAll(PDO::FETCH_ASSOC);

    // Cast numbers to integers for clean JSON
    $response = [
        'success' => true,
        'total_projects' => (int) ($overall['total_projects'] ?? 0),
        'completed_projects' => (int) ($overall['completed_projects'] ?? 0),
        'in_dev_projects' => (int) ($overall['in_dev_projects'] ?? 0),
        'total_members' => $totalMembers,
        'year_stats' => array_map(function($row) {
            return [
                'year' => (string) $row['year'],
                'completed' => (int) $row['completed'],
                'in_dev' => (int) $row['in_dev'],
                'total' => (int) $row['total']
            ];
        }, $yearsData)
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
