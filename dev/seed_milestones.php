<?php
require_once '../admin/api/db.php';

// Seed milestones for testing
$milestones = [
    // RLabz Website Revamp (slug: rlabz-revamp, project_id is unknown, we will look it up)
    'rlabz-revamp' => [
        ['title' => 'Project Kickoff & Requirements', 'status' => 'Completed', 'date' => '2026-08-01', 'sort_order' => 1],
        ['title' => 'Database Schema Design', 'status' => 'Completed', 'date' => '2026-08-15', 'sort_order' => 2],
        ['title' => 'UI Redesign (Phase 1)', 'status' => 'Current', 'date' => '2026-09-15', 'sort_order' => 3],
        ['title' => 'SSL Monitoring Integration', 'status' => 'Upcoming', 'date' => '2026-10-01', 'sort_order' => 4],
        ['title' => 'Launch & Deployment', 'status' => 'Upcoming', 'date' => '2026-11-01', 'sort_order' => 5],
    ],
    // Mentoring App (slug: mentoring)
    'mentoring' => [
        ['title' => 'Initial Planning', 'status' => 'Completed', 'date' => '2026-01-10', 'sort_order' => 1],
        ['title' => 'Prototype Development', 'status' => 'Completed', 'date' => '2026-03-20', 'sort_order' => 2],
        ['title' => 'Beta Testing', 'status' => 'Completed', 'date' => '2026-05-15', 'sort_order' => 3],
        ['title' => 'Final Release', 'status' => 'Completed', 'date' => '2026-07-01', 'sort_order' => 4],
    ]
];

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE project_milestones;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $stmt_insert = $pdo->prepare("INSERT INTO project_milestones (project_id, title, status, milestone_date, sort_order) VALUES (?, ?, ?, ?, ?)");

    foreach ($milestones as $slug => $project_milestones) {
        $stmt_find = $pdo->prepare("SELECT id FROM projects WHERE slug = ?");
        $stmt_find->execute([$slug]);
        $project = $stmt_find->fetch();

        if ($project) {
            $project_id = $project['id'];
            foreach ($project_milestones as $m) {
                $stmt_insert->execute([
                    $project_id,
                    $m['title'],
                    $m['status'],
                    $m['date'],
                    $m['sort_order']
                ]);
            }
            echo "Seeded milestones for project: $slug\n";
        } else {
            echo "Project not found for slug: $slug\n";
        }
    }

    echo "Milestone seeding completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
