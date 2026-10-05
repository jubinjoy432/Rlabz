<?php
$pdo = new PDO('mysql:host=localhost;dbname=rlabz_db;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SELECT * FROM admin_announcements');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
