<?php
require_once "admin/api/db.php";

$valid_slugs = ["sahrdaya", "rlabz-revamp", "conference", "splendore-2025", "mentoring", "booking", "blood", "placement", "euphoria-2024", "arkon-2025", "dyuti", "sparc", "stuba", "campus-connect-2025", "colabsphere", "examcell"];

$stmt = $pdo->query("SELECT * FROM projects");
$all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$to_remove = [];
foreach ($all_projects as $p) {
    if (!in_array($p["slug"], $valid_slugs)) {
        $to_remove[] = $p;
    }
}

file_put_contents("other_projects.json", json_encode($to_remove, JSON_PRETTY_PRINT));
echo "Saved " . count($to_remove) . " projects to other_projects.json\n";

foreach ($to_remove as $p) {
    $id = $p["id"];
    // Delete members
    $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ?");
    $stmt->execute([$id]);
    // Delete screenshots
    $stmt = $pdo->prepare("DELETE FROM project_screenshots WHERE project_id = ?");
    $stmt->execute([$id]);
    // Delete project
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    echo "Deleted project: " . $p["title"] . "\n";
}
?>
