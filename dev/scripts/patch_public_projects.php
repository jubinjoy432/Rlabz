<?php
$file = 'admin/api/public_projects.php';
$content = file_get_contents($file);

// Normalize line endings
$content = str_replace("\r\n", "\n", $content);

// 1. Add SQL fetch for SSL
$search1 = <<<'EOD'
    // 4. Organize members and screenshots by project ID
EOD;
$replace1 = <<<'EOD'
    // 3.6 Get all SSL certs
    $stmtSsl = $pdo->query("SELECT * FROM project_ssl_certs");
    $sslCerts = $stmtSsl->fetchAll(PDO::FETCH_ASSOC);

    // 4. Organize members and screenshots by project ID
EOD;
$content = str_replace($search1, $replace1, $content);

// 2. Organize SSL by project ID
$search2 = <<<'EOD'
    // 5. Attach members and screenshots to their projects, map to frontend expected format
EOD;
$replace2 = <<<'EOD'
    $sslByProject = [];
    foreach ($sslCerts as $s) {
        $sslByProject[$s['project_id']] = [
            'domain_url' => $s['domain_url'],
            'provider' => $s['provider'],
            'issue_date' => $s['issue_date'],
            'expiry_date' => $s['expiry_date']
        ];
    }

    // 5. Attach members and screenshots to their projects, map to frontend expected format
EOD;
$content = str_replace($search2, $replace2, $content);

// 3. Attach SSL to project array
$search3 = <<<'EOD'
            'githubLink' => $p['github_link'] ? $p['github_link'] : '#',
            'demoLink' => $p['demo_link'] ? $p['demo_link'] : '#',
EOD;
$replace3 = <<<'EOD'
            'githubLink' => $p['github_link'] ? $p['github_link'] : '#',
            'demoLink' => $p['demo_link'] ? $p['demo_link'] : '#',
            'ssl' => $sslByProject[$p_id] ?? null,
EOD;
$content = str_replace($search3, $replace3, $content);

file_put_contents($file, $content);
echo "Done patching public_projects.php!\n";
?>
