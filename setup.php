<?php
/**
 * RLabz - Automated Database Schema & Initialization Runner
 * Can be run via CLI: `php setup.php`
 * Or via Web Browser: `http://your-domain.com/setup.php`
 */

// 1. Establish Environment and Database Connection
require_once __DIR__ . '/admin/api/db.php';

$isCli = (php_sapi_name() === 'cli');

$results = [];
$error = null;

try {
    // Disable foreign key checks during migration
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 2. Read and Parse database/schema.sql
    $schemaPath = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("schema.sql not found at: " . $schemaPath);
    }

    $rawSql = file_get_contents($schemaPath);

    // Strip comments and split statements cleanly
    $rawSql = preg_replace('!/\*.*?\*/!s', '', $rawSql);
    $lines = explode("\n", $rawSql);
    $queries = [];
    $currentQuery = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $currentQuery .= $line . "\n";
        if (str_ends_with($trimmed, ';')) {
            $q = trim($currentQuery);
            if (!empty($q)) {
                $queries[] = $q;
            }
            $currentQuery = '';
        }
    }
    if (!empty(trim($currentQuery))) {
        $queries[] = trim($currentQuery);
    }

    // 3. Execute all schema statements
    foreach ($queries as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        // Extract a friendly name for reporting
        $label = 'SQL Statement';
        if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?([a-zA-Z0-9_]+)`?/i', $statement, $m)) {
            $label = "Create Table: " . $m[1];
        } elseif (preg_match('/INSERT INTO `?([a-zA-Z0-9_]+)`?/i', $statement, $m)) {
            $label = "Seed Records: " . $m[1];
        } elseif (preg_match('/INSERT IGNORE INTO `?([a-zA-Z0-9_]+)`?/i', $statement, $m)) {
            $label = "Seed Default Record: " . $m[1];
        }

        $pdo->exec($statement);
        $results[] = [
            'type' => 'success',
            'action' => $label,
            'message' => 'Executed successfully'
        ];
    }

    // 4. Check if baseline projects exist; if empty, seed from dev/projects_dump.json
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM projects");
    $projectCount = (int)$stmtCount->fetchColumn();

    $seederFile = __DIR__ . '/dev/projects_dump.json';
    if ($projectCount === 0 && file_exists($seederFile)) {
        $dumpData = json_decode(file_get_contents($seederFile), true);
        if (!empty($dumpData) && is_array($dumpData)) {
            $stmtProject = $pdo->prepare("
                INSERT INTO projects (
                    slug, title, year, short_description, description, objectives,
                    problem_statement, key_features, expected_outcome, tech_stack,
                    image_path, thumbnail_path, department, batch, category,
                    project_type, duration, status, github_link, demo_link,
                    poster_path, ssl_tracking_enabled
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtMember = $pdo->prepare("INSERT INTO project_members (project_id, name, photo_path, role, linkedin_link) VALUES (?, ?, ?, ?, ?)");
            $stmtFaculty = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path, linkedin_link) VALUES (?, ?, ?, ?, ?)");
            $stmtScreenshot = $pdo->prepare("INSERT INTO project_screenshots (project_id, image_path, sort_order) VALUES (?, ?, ?)");
            $stmtMilestone = $pdo->prepare("INSERT INTO project_milestones (project_id, title, status, milestone_date, sort_order) VALUES (?, ?, ?, ?, ?)");

            $seededCount = 0;
            foreach ($dumpData as $p) {
                $slug = $p['slug'] ?? ($p['id'] ?? uniqid('proj_'));
                $title = $p['title'] ?? 'Untitled';
                $tech = is_array($p['tech'] ?? null) ? implode(', ', $p['tech']) : ($p['tech_stack'] ?? '');
                $features = is_array($p['keyFeatures'] ?? null) ? implode(', ', $p['keyFeatures']) : ($p['key_features'] ?? '');

                $stmtProject->execute([
                    $slug,
                    $title,
                    $p['year'] ?? date('Y'),
                    $p['shortDescription'] ?? ($p['short_description'] ?? ''),
                    $p['fullDescription'] ?? ($p['description'] ?? ''),
                    $p['objectives'] ?? null,
                    $p['problemStatement'] ?? ($p['problem_statement'] ?? null),
                    $features,
                    $p['expectedOutcome'] ?? ($p['expected_outcome'] ?? null),
                    $tech,
                    $p['thumbnail'] ?? ($p['image_path'] ?? 'assets/images/logo1.png'),
                    $p['thumbnail'] ?? ($p['thumbnail_path'] ?? 'assets/images/logo1.png'),
                    $p['department'] ?? 'MCA',
                    $p['batch'] ?? '',
                    $p['category'] ?? 'Web Application',
                    $p['projectType'] ?? ($p['project_type'] ?? 'Academic'),
                    $p['duration'] ?? '',
                    $p['status'] ?? 'In Development',
                    $p['githubLink'] ?? ($p['github_link'] ?? ''),
                    $p['demoLink'] ?? ($p['demo_link'] ?? ''),
                    $p['poster'] ?? ($p['poster_path'] ?? ''),
                    isset($p['ssl_tracking_enabled']) ? (int)$p['ssl_tracking_enabled'] : 1
                ]);

                $newId = (int)$pdo->lastInsertId();

                // Seed Team Members
                if (!empty($p['team']) && is_array($p['team'])) {
                    foreach ($p['team'] as $m) {
                        $mName = is_array($m) ? ($m['name'] ?? '') : $m;
                        if (!empty($mName)) {
                            $stmtMember->execute([
                                $newId,
                                $mName,
                                is_array($m) ? ($m['photo'] ?? ($m['photo_path'] ?? '')) : '',
                                is_array($m) ? ($m['role'] ?? '') : '',
                                is_array($m) ? ($m['linkedin'] ?? ($m['linkedin_link'] ?? '')) : ''
                            ]);
                        }
                    }
                }

                // Seed Faculty
                if (!empty($p['faculty'])) {
                    $facList = is_array($p['faculty']) && isset($p['faculty'][0]) ? $p['faculty'] : [$p['faculty']];
                    foreach ($facList as $f) {
                        $fName = is_array($f) ? ($f['name'] ?? '') : $f;
                        if (!empty($fName)) {
                            $stmtFaculty->execute([
                                $newId,
                                $fName,
                                is_array($f) ? ($f['designation'] ?? '') : '',
                                is_array($f) ? ($f['photo'] ?? ($f['photo_path'] ?? '')) : '',
                                is_array($f) ? ($f['linkedin'] ?? ($f['linkedin_link'] ?? '')) : ''
                            ]);
                        }
                    }
                }

                // Seed Screenshots
                if (!empty($p['screenshots']) && is_array($p['screenshots'])) {
                    $order = 0;
                    foreach ($p['screenshots'] as $s) {
                        $sPath = is_array($s) ? ($s['image_path'] ?? '') : $s;
                        if (!empty($sPath)) {
                            $stmtScreenshot->execute([$newId, $sPath, $order++]);
                        }
                    }
                }

                // Seed Milestones
                if (!empty($p['milestones']) && is_array($p['milestones'])) {
                    $mOrder = 0;
                    foreach ($p['milestones'] as $ms) {
                        $stmtMilestone->execute([
                            $newId,
                            $ms['title'] ?? 'Milestone',
                            $ms['status'] ?? 'Completed',
                            $ms['date'] ?? date('Y-m-d'),
                            $mOrder++
                        ]);
                    }
                }

                $seededCount++;
            }

            $results[] = [
                'type' => 'success',
                'action' => 'Project Seeder',
                'message' => "Populated {$seededCount} baseline projects and child relations"
            ];
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Fetch verified tables in database
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    $error = $e->getMessage();
}

// ==============================================================================
// Output Presentation (CLI or HTML)
// ==============================================================================

if ($isCli) {
    echo "\n======================================================\n";
    echo "  RLabz Database Schema & Initialization Runner\n";
    echo "======================================================\n\n";

    if ($error) {
        echo "❌ ERROR: " . $error . "\n\n";
        exit(1);
    }

    foreach ($results as $res) {
        echo "✓ [" . $res['action'] . "] " . $res['message'] . "\n";
    }

    echo "\n------------------------------------------------------\n";
    echo "Active Tables in Database (" . count($tables) . "):\n";
    echo implode(', ', $tables) . "\n";
    echo "------------------------------------------------------\n";
    echo "Default Admin Account: admin / admin123\n";
    echo "Database setup completed successfully!\n\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RLabz — Database Schema Initialization</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: #131b2e;
            --accent: #2563eb;
            --accent-glow: #3b82f6;
            --success: #10b981;
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
        }
        body {
            margin: 0;
            padding: 2.5rem 1rem;
            background: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 760px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
        }
        .logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        h1 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
        }
        p.subtitle {
            margin: 0.25rem 0 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .alert {
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        .log-box {
            background: #070a12;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem;
            max-height: 280px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            margin-bottom: 1.75rem;
        }
        .log-item {
            display: flex;
            justify-content: space-between;
            padding: 0.35rem 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
        }
        .log-action { color: #60a5fa; }
        .log-status { color: #34d399; }
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .table-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .table-pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
        }
        .actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            flex: 1;
            padding: 0.85rem 1.25rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
        .btn-primary:hover {
            background: var(--accent-glow);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-main);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .credentials-card {
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.25);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.75rem;
            font-size: 0.9rem;
        }
        .credentials-card strong {
            color: #93c5fd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-badge">R</div>
            <div>
                <h1>RLabz System Bootstrap</h1>
                <p class="subtitle">Automated Database Schema & Initialization Engine</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Please verify that your database credentials in <code>.env</code> are correct and that the MySQL server is running.
            </p>
            <div class="actions">
                <a href="setup.php" class="btn btn-primary">Retry Setup</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                ✓ All database tables and default administrator accounts were successfully created and verified!
            </div>

            <div class="credentials-card">
                <strong>Default Administrator Credentials:</strong><br>
                Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code><br>
                <small style="color: var(--text-muted);">(You can change this password anytime in the Admin -> Users panel)</small>
            </div>

            <h3 style="font-size: 1rem; margin: 0 0 0.75rem 0; color: var(--text-muted);">Execution Log</h3>
            <div class="log-box">
                <?php foreach ($results as $res): ?>
                    <div class="log-item">
                        <span class="log-action"><?php echo htmlspecialchars($res['action']); ?></span>
                        <span class="log-status"><?php echo htmlspecialchars($res['message']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 style="font-size: 1rem; margin: 0 0 0.75rem 0; color: var(--text-muted);">Verified Tables in Database (<?php echo count($tables); ?>)</h3>
            <div class="tables-grid">
                <?php foreach ($tables as $t): ?>
                    <div class="table-pill"><?php echo htmlspecialchars($t); ?></div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <a href="index.html" class="btn btn-secondary">Open Public Website</a>
                <a href="admin/login.php" class="btn btn-primary">Open Admin Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

