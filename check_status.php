<?php
require 'admin/api/db.php';
$stmt = $pdo->query("SELECT id, title, status FROM projects");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
