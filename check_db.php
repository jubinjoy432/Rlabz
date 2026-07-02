<?php
require_once 'admin/api/db.php';
$stmt = $pdo->query("DESCRIBE admin_users");
print_r($stmt->fetchAll());
?>
