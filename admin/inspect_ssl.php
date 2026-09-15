<?php
require 'api/db.php';

// Backup existing SSL certs data
$stmt = $pdo->query("SELECT * FROM project_ssl_certs");
$ssl_certs = $stmt->fetchAll();
file_put_contents('../dev/ssl_certs_backup.json', json_encode($ssl_certs, JSON_PRETTY_PRINT));

echo "Backup of project_ssl_certs saved to dev/ssl_certs_backup.json\n";
echo "Let's inspect the data:\n";
print_r($ssl_certs);
?>
