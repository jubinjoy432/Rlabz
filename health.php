<?php
/**
 * RLabz - Hosting Pre-Flight & Health Diagnostics Check
 * Access via: http://your-domain.com/health.php
 * Verifies that the hosting server meets all technical requirements.
 */

// Basic security check: Can be disabled or restricted in production
header('Content-Type: text/html; charset=utf-8');

$checks = [];
$allPassed = true;

// 1. PHP Version Check
$phpVersion = PHP_VERSION;
$phpPass = version_compare($phpVersion, '8.0.0', '>=');
$checks[] = [
    'category' => 'Runtime Environment',
    'item' => 'PHP Version',
    'value' => $phpVersion . ' (Requirement: >= 8.0.0)',
    'passed' => $phpPass,
    'critical' => true
];
if (!$phpPass) $allPassed = false;

// 2. Required PHP Extensions
$requiredExtensions = [
    'pdo' => 'PHP Data Objects core engine',
    'pdo_mysql' => 'MySQL database communication driver',
    'openssl' => 'Required for native SSL certificate health monitoring',
    'mbstring' => 'Multi-byte string encoding and normalization',
    'fileinfo' => 'Safe MIME-type detection for image uploads',
    'json' => 'REST API serialization and payload parsing'
];

foreach ($requiredExtensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'category' => 'PHP Extensions',
        'item' => "ext-$ext",
        'value' => $loaded ? "Installed ($desc)" : "Missing — Please enable $ext in php.ini",
        'passed' => $loaded,
        'critical' => true
    ];
    if (!$loaded) $allPassed = false;
}

// 3. Storage & Directory Permissions
$uploadDir = __DIR__ . '/uploads';
$uploadsWritable = is_dir($uploadDir) && is_writable($uploadDir);
$checks[] = [
    'category' => 'File System Permissions',
    'item' => 'Uploads Directory Writable (/uploads/)',
    'value' => $uploadsWritable ? 'Writable (chmod 755/775)' : 'Not writable! Run: chmod 755 uploads/',
    'passed' => $uploadsWritable,
    'critical' => true
];
if (!$uploadsWritable) $allPassed = false;

// 4. .env File Status
$envPath = __DIR__ . '/.env';
$envExists = file_exists($envPath);
$checks[] = [
    'category' => 'Configuration',
    'item' => '.env File Present',
    'value' => $envExists ? 'Found at root directory' : 'Missing! Copy .env.example to .env',
    'passed' => $envExists,
    'critical' => true
];
if (!$envExists) $allPassed = false;

// 5. Database Connection & Schema Verification
$dbConnected = false;
$tableCount = 0;
$dbMessage = '';

if ($envExists) {
    $env = parse_ini_file($envPath);
    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? 'rlabz_db';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        $dbConnected = true;
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);
        $dbMessage = "Connected successfully. {$tableCount} active tables detected.";
    } catch (Exception $e) {
        $dbMessage = "Connection failed: " . $e->getMessage();
    }
} else {
    $dbMessage = "Cannot test: .env file missing.";
}

$checks[] = [
    'category' => 'Database Connectivity',
    'item' => 'MySQL Connection & Tables',
    'value' => $dbMessage,
    'passed' => $dbConnected && $tableCount >= 7,
    'critical' => true
];
if (!$dbConnected || $tableCount < 7) $allPassed = false;

// 6. Outbound Port 443 Socket (For SSL Monitor)
$socketTest = false;
$socketMsg = '';
$stream = @stream_socket_client("ssl://google.com:443", $errno, $errstr, 4, STREAM_CLIENT_CONNECT);
if ($stream) {
    $socketTest = true;
    $socketMsg = 'Outbound SSL socket handshake successful (Port 443 open)';
    fclose($stream);
} else {
    $socketMsg = "Outbound socket blocked or timeout ($errstr). Required for SSL monitoring.";
}

$checks[] = [
    'category' => 'Network & Connectivity',
    'item' => 'Outbound SSL Sockets (Port 443)',
    'value' => $socketMsg,
    'passed' => $socketTest,
    'critical' => false
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RLabz — Hosting Pre-Flight Health Check</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --card: #111827;
            --border: rgba(255, 255, 255, 0.08);
            --text: #f9fafb;
            --muted: #9ca3af;
            --green: #10b981;
            --red: #ef4444;
            --blue: #3b82f6;
        }
        body {
            margin: 0;
            padding: 2.5rem 1rem;
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }
        .container {
            max-width: 860px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        h1 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pass {
            background: rgba(16, 185, 129, 0.15);
            color: var(--green);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-fail {
            background: rgba(239, 68, 68, 0.15);
            color: var(--red);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .check-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .check-table th, .check-table td {
            padding: 0.85rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .check-table th {
            color: var(--muted);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            background: rgba(255, 255, 255, 0.02);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .footer-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
        }
        .btn-primary { background: var(--blue); color: white; }
        .btn-secondary { background: rgba(255, 255, 255, 0.08); color: var(--text); border: 1px solid var(--border); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>RLabz Hosting Pre-Flight Diagnostics</h1>
                <p style="margin: 0.25rem 0 0; color: var(--muted); font-size: 0.9rem;">
                    Automated technical compatibility audit for production deployment.
                </p>
            </div>
            <div class="status-badge <?php echo $allPassed ? 'status-pass' : 'status-fail'; ?>">
                <?php echo $allPassed ? '✓ All Systems Ready' : '⚠ Action Required'; ?>
            </div>
        </div>

        <table class="check-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Requirement / Component</th>
                    <th>Current Server State</th>
                    <th style="text-align: right;">Result</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $c): ?>
                    <tr>
                        <td style="color: var(--muted); font-weight: 500;"><?php echo htmlspecialchars($c['category']); ?></td>
                        <td><strong><?php echo htmlspecialchars($c['item']); ?></strong></td>
                        <td style="color: <?php echo $c['passed'] ? '#cbd5e1' : '#fca5a5'; ?>;">
                            <?php echo htmlspecialchars($c['value']); ?>
                        </td>
                        <td style="text-align: right;">
                            <span class="badge <?php echo $c['passed'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $c['passed'] ? '✓ Pass' : '✗ Fail'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer-actions">
            <a href="setup.php" class="btn btn-primary">Run Database Setup</a>
            <a href="index.html" class="btn btn-secondary">Open Main Website</a>
            <a href="admin/login.php" class="btn btn-secondary">Open Admin Panel</a>
        </div>
    </div>
</body>
</html>

