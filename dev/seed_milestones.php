<?php
require_once __DIR__ . '/../admin/api/db.php';

echo "Seeding project milestones (non-destructive)...\n";

$milestones = [
    // RLabz Website Revamp
    'rlabz-website-revamp' => [
        ['title' => 'Project Kickoff & Requirements', 'status' => 'Completed', 'date' => '2026-08-01', 'sort_order' => 1],
        ['title' => 'Database Schema Design', 'status' => 'Completed', 'date' => '2026-08-15', 'sort_order' => 2],
        ['title' => 'UI Redesign (Phase 1)', 'status' => 'Current', 'date' => '2026-09-15', 'sort_order' => 3],
        ['title' => 'SSL Monitoring Integration', 'status' => 'Upcoming', 'date' => '2026-10-01', 'sort_order' => 4],
        ['title' => 'Launch & Deployment', 'status' => 'Upcoming', 'date' => '2026-11-01', 'sort_order' => 5],
    ],
    // Mentoring App
    'mentoring-app' => [
        ['title' => 'Initial Planning', 'status' => 'Completed', 'date' => '2026-01-10', 'sort_order' => 1],
        ['title' => 'Prototype Development', 'status' => 'Completed', 'date' => '2026-03-20', 'sort_order' => 2],
        ['title' => 'Beta Testing', 'status' => 'Completed', 'date' => '2026-05-15', 'sort_order' => 3],
        ['title' => 'Final Release', 'status' => 'Completed', 'date' => '2026-07-01', 'sort_order' => 4],
    ]
];

try {
    // 1. Ensure table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `project_milestones` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `status` ENUM('Completed', 'Current', 'Upcoming') DEFAULT 'Completed',
            `milestone_date` DATE NOT NULL,
            `sort_order` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_pmilestone_project_id` (`project_id`),
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt_find = $pdo->prepare("SELECT id FROM projects WHERE slug = ? OR slug LIKE ?");
    $stmt_check = $pdo->prepare("SELECT id FROM project_milestones WHERE project_id = ? AND title = ?");
    $stmt_insert = $pdo->prepare("INSERT INTO project_milestones (project_id, title, status, milestone_date, sort_order) VALUES (?, ?, ?, ?, ?)");

    foreach ($milestones as $slug => $project_milestones) {
        $stmt_find->execute([$slug, "%$slug%"]);
        $project = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if ($project) {
            $project_id = $project['id'];
            foreach ($project_milestones as $m) {
                $stmt_check->execute([$project_id, $m['title']]);
                if ($stmt_check->fetch()) {
                    // Milestone already exists, skip to prevent duplicates
                    continue;
                }
                $stmt_insert->execute([
                    $project_id,
                    $m['title'],
                    $m['status'],
                    $m['date'],
                    $m['sort_order']
                ]);
                echo "Added milestone '{$m['title']}' to project ID {$project_id}.\n";
            }
        } else {
            echo "Project not found for slug: $slug (skipping milestone seeding)\n";
        }
    }
    // Seed SSL certificates (non-destructive)
    $stmt_ssl_find = $pdo->prepare("SELECT id FROM project_ssl_certs WHERE project_id = ? AND domain_url = ?");
    $stmt_ssl_insert = $pdo->prepare("INSERT INTO project_ssl_certs (project_id, domain_url, provider, issue_date, expiry_date, certificate_fingerprint) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($milestones as $slug => $project_milestones) {
        $stmt_find->execute([$slug, "%$slug%"]); 
        $project = $stmt_find->fetch(PDO::FETCH_ASSOC);
        if ($project) {
            $project_id = $project['id'];
            $domain = $slug . ".example.com";
            $stmt_ssl_find->execute([$project_id, $domain]);
            if (!$stmt_ssl_find->fetch(PDO::FETCH_ASSOC)) {
                $stmt_ssl_insert->execute([
                    $project_id,
                    $domain,
                    "Let's Encrypt",
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+90 days')),
                    bin2hex(random_bytes(16))
                ]);
                echo "Added SSL entry for {$domain} to project ID {$project_id}.\n";
            }
        }
    }

    echo "Milestone seeding check completed without truncating existing data.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
