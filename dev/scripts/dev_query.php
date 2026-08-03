<?php
require_once 'admin/api/db.php';
$stmt = $pdo->query("SELECT * FROM project_members;");
print_r($stmt->fetchAll());
?>
