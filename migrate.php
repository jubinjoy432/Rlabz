<?php
require_once 'admin/api/db.php';

$json = file_get_contents('projects_dump.json');
$projects = json_decode($json, true);

// PDF Overrides
$pdf_data = [
    'sahrdaya' => [
        'team' => ['Gracen K. Shaji (MCA 23)', 'Sahil Sharma (MCA 20)'],
        'faculty' => 'Dr. Shiju Thomas M Y and Dr. Bindiya M Varghese'
    ],
    'rlabz-revamp' => [
        'team' => ['RLabZ Development Team with students'],
        'faculty' => 'Gracen K. Shaji'
    ],
    'conference' => [
        'team' => ['Ananthu Krishna (MCA 25)', 'Shyam James (MCA 25)', 'Febin Sunny (MCA 25)', 'Nikhil Krishna (MSC CS 25)'],
        'faculty' => 'Ajay Antony Joseph & Jobin K Jaison'
    ],
    'splendore-2025' => [
        'team' => ['Students Team'],
        'faculty' => 'RLabZ'
    ],
    'mentoring' => [
        'team' => ['Student Innovation Team'],
        'faculty' => 'Diljith K Benny'
    ],
    'booking' => [
        'team' => ['Campus Automation Team'],
        'faculty' => 'Diljith K Benny'
    ],
    'blood' => [
        'team' => ['Dhanush (BSC CS 24)', 'Vivek (BSC CS 24)'],
        'faculty' => 'Ms.Sunu Fathima'
    ],
    'placement' => [
        'team' => ['Abhilash BK (MSC 23)'],
        'faculty' => 'Dr. Bindiya M Varghese'
    ],
    'euphoria-2024' => [
        'team' => ['Daivath Lal (MCA 23)', 'Hrishikesh U (MCA 23)'],
        'faculty' => 'Mr. Diljith K Benny'
    ],
    'arkon-2025' => [
        'team' => ['Arkon Development Team'],
        'faculty' => 'RLabZ'
    ],
    'dyuti' => [
        'team' => ['George B George (MCA 24)'],
        'faculty' => 'Dr. Anil John'
    ],
    'sparc' => [
        'team' => ['Albin Joseph (MCA 24)'],
        'faculty' => 'Dr. Bindiya M Varghese and Mr.Diljith K Benny'
    ],
    'stuba' => [
        'team' => ['Daivadath Lal (MCA 24)'],
        'faculty' => 'Mr.Diljith K Benny'
    ],
    'campus-connect-2025' => [
        'team' => [],
        'faculty' => 'Dr.Sreejith R'
    ],
    'colabsphere' => [
        'team' => ['Jeffin Joby', 'Jasin Joji'],
        'faculty' => 'Dr.Bindiya M varghese & Mr.'
    ],
    'examcell' => [
        'team' => ['Shyam James', 'Jubin Joy'],
        'faculty' => 'Dr.Shiju Thomas M Y'
    ]
];

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE projects;");
    $pdo->exec("TRUNCATE TABLE project_members;");
    $pdo->exec("TRUNCATE TABLE project_screenshots;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $stmt_project = $pdo->prepare("INSERT INTO projects 
        (slug, title, year, short_description, description, objectives, problem_statement, key_features, expected_outcome, tech_stack, image_path, thumbnail_path, department, batch, faculty_name, faculty_designation, category, project_type, duration, status, github_link, demo_link, poster_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt_member = $pdo->prepare("INSERT INTO project_members (project_id, name, photo_path) VALUES (?, ?, '')");
    $stmt_screenshot = $pdo->prepare("INSERT INTO project_screenshots (project_id, image_path) VALUES (?, ?)");

    foreach ($projects as $p) {
        $slug = $p['id'];
        $faculty = $p['faculty']['name'] ?? '';
        $team = $p['team'] ?? [];

        if (isset($pdf_data[$slug])) {
            $faculty = $pdf_data[$slug]['faculty'];
            $team = $pdf_data[$slug]['team'];
        }

        $tech_stack = isset($p['tech']) ? implode(', ', $p['tech']) : '';
        $key_features = isset($p['keyFeatures']) ? implode(', ', $p['keyFeatures']) : '';

        $stmt_project->execute([
            $slug,
            $p['title'] ?? '',
            $p['year'] ?? '',
            $p['shortDescription'] ?? '',
            $p['fullDescription'] ?? '',
            $p['objectives'] ?? '',
            $p['problemStatement'] ?? '',
            $key_features,
            $p['expectedOutcome'] ?? '',
            $tech_stack,
            $p['thumbnail'] ?? '',
            $p['thumbnail'] ?? '', // thumbnail_path
            $p['department'] ?? '',
            $p['batch'] ?? '',
            $faculty,
            $p['faculty']['designation'] ?? '',
            $p['category'] ?? '',
            $p['projectType'] ?? '',
            $p['duration'] ?? '',
            $p['status'] ?? '',
            $p['githubLink'] ?? '',
            $p['demoLink'] ?? '',
            $p['poster'] ?? ''
        ]);

        $project_id = $pdo->lastInsertId();

        foreach ($team as $member_name) {
            $stmt_member->execute([$project_id, $member_name]);
        }

        if (isset($p['screenshots']) && is_array($p['screenshots'])) {
            foreach ($p['screenshots'] as $ss) {
                $stmt_screenshot->execute([$project_id, $ss]);
            }
        }
    }

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
