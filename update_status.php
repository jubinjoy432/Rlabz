<?php
require 'admin/api/db.php';
$stmt = $pdo->query("SELECT title, year, status FROM projects WHERE status = 'In Development'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
