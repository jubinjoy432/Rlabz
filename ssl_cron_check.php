<?php
/**
 * RLabz - SSL Certificate Checker Script
 * Automatically/Manually updates SSL status for all project domains.
 * Native PHP check using stream_socket_client and openssl_x509_parse (no external API).
 */

// Determine execution context (CLI or Web)
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

echo $isCli ? "=== RLabz SSL Certificate Checker ===\n" : "<h2>RLabz SSL Certificate Checker</h2><pre>";

// Load Database Connection
$dbPath = __DIR__ . '/admin/api/db.php';
if (!file_exists($dbPath)) {
    die($isCli ? "Error: db.php not found.\n" : "Error: db.php not found.</pre>");
}
require_once $dbPath;

try {
    // 1. Ensure ssl_status table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ssl_status` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL UNIQUE,
        `domain` VARCHAR(255) NOT NULL,
        `expiry_date` DATETIME DEFAULT NULL,
        `days_left` INT(11) DEFAULT NULL,
        `issuer` VARCHAR(255) DEFAULT NULL,
        `checked_at` DATETIME NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'error',
        PRIMARY KEY (`id`),
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Fetch all projects
    $stmt = $pdo->query("SELECT id, slug, title, demo_link FROM projects ORDER BY id ASC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo $isCli ? "Found " . count($projects) . " projects to process.\n\n" : "Found " . count($projects) . " projects to process.<br><br>";

    // Prepare DB statements
    $upsertStmt = $pdo->prepare("
        INSERT INTO ssl_status (project_id, domain, expiry_date, days_left, issuer, checked_at, status)
        VALUES (:project_id, :domain, :expiry_date, :days_left, :issuer, NOW(), :status)
        ON DUPLICATE KEY UPDATE
            domain = VALUES(domain),
            expiry_date = VALUES(expiry_date),
            days_left = VALUES(days_left),
            issuer = VALUES(issuer),
            checked_at = VALUES(checked_at),
            status = VALUES(status)
    ");

    $processed = 0;

    foreach ($projects as $project) {
        $projectId = $project['id'];
        $title = $project['title'];
        $demoLink = trim($project['demo_link'] ?? '');

        $domain = extractDomain($demoLink);

        if (empty($domain)) {
            // No valid demo link / domain
            $statusData = [
                'project_id' => $projectId,
                'domain' => $demoLink ?: 'N/A',
                'expiry_date' => null,
                'days_left' => null,
                'issuer' => null,
                'status' => 'error'
            ];
            $upsertStmt->execute($statusData);
            $msg = sprintf("[%s] ID: %d | %s | Domain: N/A -> Status: error (No live domain)\n", date('Y-m-d H:i:s'), $projectId, $title);
            echo $isCli ? $msg : htmlspecialchars($msg);
            $processed++;
            continue;
        }

        // Native PHP SSL check
        $check = checkSslCertificate($domain);

        $statusData = [
            'project_id' => $projectId,
            'domain' => $domain,
            'expiry_date' => $check['expiry_date'],
            'days_left' => $check['days_left'],
            'issuer' => $check['issuer'],
            'status' => $check['status']
        ];

        $upsertStmt->execute($statusData);

        $msg = sprintf(
            "[%s] ID: %d | %s | Domain: %s | Expiry: %s | Days Left: %s | Status: %s %s\n",
            date('Y-m-d H:i:s'),
            $projectId,
            $title,
            $domain,
            $check['expiry_date'] ?? 'N/A',
            isset($check['days_left']) ? $check['days_left'] : 'N/A',
            strtoupper($check['status']),
            !empty($check['error']) ? "(" . $check['error'] . ")" : ""
        );
        echo $isCli ? $msg : htmlspecialchars($msg);
        $processed++;
    }

    echo $isCli ? "\nSSL check complete. Processed $processed projects.\n" : "<br><strong>SSL check complete. Processed $processed projects.</strong></pre>";

} catch (PDOException $e) {
    die($isCli ? "Database Error: " . $e->getMessage() . "\n" : "Database Error: " . htmlspecialchars($e->getMessage()) . "</pre>");
}

/**
 * Extracts hostname from URL string
 */
function extractDomain($url) {
    if (empty($url)) return null;
    $url = trim($url);
    if ($url === '#' || strtolower($url) === 'javascript:void(0)') return null;

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'http://' . $url;
    }

    $host = parse_url($url, PHP_URL_HOST);
    return $host ? strtolower($host) : null;
}

/**
 * Native PHP SSL Certificate Expiry Check using stream_socket_client & openssl_x509_parse
 */
function checkSslCertificate($domain, $timeout = 8) {
    if (empty($domain)) {
        return [
            'domain' => '',
            'expiry_date' => null,
            'days_left' => null,
            'issuer' => null,
            'status' => 'error',
            'error' => 'No domain specified'
        ];
    }

    $gcontext = stream_context_create([
        "ssl" => [
            "capture_peer_cert" => true,
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ]);

    $client = @stream_socket_client(
        "ssl://" . $domain . ":443",
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $gcontext
    );

    if (!$client) {
        return [
            'domain' => $domain,
            'expiry_date' => null,
            'days_left' => null,
            'issuer' => null,
            'status' => 'error',
            'error' => "Connection failed: $errstr ($errno)"
        ];
    }

    $params = stream_context_get_params($client);
    fclose($client);

    if (empty($params["options"]["ssl"]["peer_certificate"])) {
        return [
            'domain' => $domain,
            'expiry_date' => null,
            'days_left' => null,
            'issuer' => null,
            'status' => 'error',
            'error' => "No peer certificate returned"
        ];
    }

    $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);
    if (!$cert || empty($cert['validTo_time_t'])) {
        return [
            'domain' => $domain,
            'expiry_date' => null,
            'days_left' => null,
            'issuer' => null,
            'status' => 'error',
            'error' => "Unable to parse validTo date"
        ];
    }

    $validTo = $cert['validTo_time_t'];
    $expiryDate = date('Y-m-d H:i:s', $validTo);
    $now = time();
    $daysLeft = (int) floor(($validTo - $now) / 86400);

    $issuer = 'Unknown';
    if (isset($cert['issuer']['O'])) {
        $issuer = is_array($cert['issuer']['O']) ? implode(', ', $cert['issuer']['O']) : $cert['issuer']['O'];
    } elseif (isset($cert['issuer']['CN'])) {
        $issuer = is_array($cert['issuer']['CN']) ? implode(', ', $cert['issuer']['CN']) : $cert['issuer']['CN'];
    } elseif (!empty($cert['issuer']) && is_array($cert['issuer'])) {
        $parts = [];
        foreach ($cert['issuer'] as $k => $v) {
            $parts[] = "$k=" . (is_array($v) ? implode('/', $v) : $v);
        }
        $issuer = implode(', ', $parts);
    }

    // Status rules: "danger" if expired or <=15 days left, "warning" if <=30 days, "ok" otherwise, "error" if domain can't be reached
    if ($daysLeft <= 15) {
        $status = 'danger';
    } elseif ($daysLeft <= 30) {
        $status = 'warning';
    } else {
        $status = 'ok';
    }

    return [
        'domain' => $domain,
        'expiry_date' => $expiryDate,
        'days_left' => $daysLeft,
        'issuer' => $issuer,
        'status' => $status,
        'error' => null
    ];
}
