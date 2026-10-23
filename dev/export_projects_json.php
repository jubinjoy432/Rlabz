<?php
/**
 * Export current database projects to projects_dump.json
 * Usage: php dev/export_projects_json.php
 */
require_once __DIR__ . '/../admin/api/db.php';

$projects = $pdo->query("SELECT * FROM projects ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$export = [];
foreach ($projects as $p) {
    $pId = $p['id'];
    $p['members'] = $pdo->query("SELECT name, role, photo_path, linkedin_link FROM project_members WHERE project_id = $pId")->fetchAll(PDO::FETCH_ASSOC);
    $p['faculty'] = $pdo->query("SELECT name, designation, photo_path, linkedin_link FROM project_faculty WHERE project_id = $pId")->fetchAll(PDO::FETCH_ASSOC);
    $p['screenshots'] = $pdo->query("SELECT image_path, caption FROM project_screenshots WHERE project_id = $pId")->fetchAll(PDO::FETCH_ASSOC);
    $export[] = $p;
}

$target = __DIR__ . '/projects_dump.json';
file_put_contents($target, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Successfully exported " . count($export) . " projects to " . $target . "\n";
