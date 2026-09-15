<?php
// admin/api/ssl_certs.php
header('Content-Type: application/json');
require_once 'db.php';

session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT s.*, p.title as project_title, DATEDIFF(s.expiry_date, CURDATE()) as days_left 
                         FROM project_ssl_certs s 
                         JOIN projects p ON s.project_id = p.id 
                         ORDER BY s.expiry_date ASC");
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'Healthy' => 0,
        'Expiring' => 0,
        'Critical' => 0,
        'Failed' => 0
    ];

    foreach ($certs as &$cert) {
        if ($cert['status'] === 'Check Failed') {
            $summary['Failed']++;
        } elseif ($cert['days_left'] !== null) {
            if ($cert['days_left'] <= 7) {
                $summary['Critical']++;
                $cert['dashboard_status'] = 'Critical';
            } elseif ($cert['days_left'] <= 30) {
                $summary['Expiring']++;
                $cert['dashboard_status'] = 'Warning';
            } else {
                $summary['Healthy']++;
                $cert['dashboard_status'] = 'Healthy';
            }
        }
    }

    echo json_encode([
        'summary' => $summary,
        'certificates' => $certs
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
