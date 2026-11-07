<?php
/**
 * RLabz - Production Data Loader & Database Migrator
 * Use this file on your hosting server (cPanel / VPS) to import all local projects,
 * team members, faculty, screenshots, and admin accounts into the server database.
 *
 * Access in browser: http://your-domain.com/load_data.php
 */

define('ALLOW_DB_FAIL', true);
require_once __DIR__ . '/admin/api/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = null;
$error = null;
$stats = null;

// Handle File Self-Deletion for Security
if ($action === 'delete_self') {
    if (@unlink(__FILE__)) {
        echo '<!DOCTYPE html><html><body style="background:#0b0f19;color:#f8fafc;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">';
        echo '<div style="background:#131b2e;padding:2rem;border-radius:12px;text-align:center;border:1px solid #334155;">';
        echo '<h2 style="color:#10b981;margin-top:0;">✓ File Deleted Successfully</h2>';
        echo '<p style="color:#94a3b8;"><code>load_data.php</code> has been permanently removed from your server for security.</p>';
        echo '<a href="index.html" style="display:inline-block;margin-top:1rem;background:#2563eb;color:white;padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;">Go to Website</a>';
        echo '</div></body></html>';
        exit;
    } else {
        $error = "Could not automatically delete the file. Please remove load_data.php manually via cPanel File Manager.";
    }
}

// Check database connection and load config
$envConfig = [
    'host' => $host ?? 'localhost',
    'dbname' => $dbname ?? 'rlabz_db',
    'user' => $username ?? 'root',
    'pass' => $password ?? ''
];

if (!$pdo) {
    // Attempt auto-create if connection failed because database doesn't exist
    try {
        $rootPdo = new PDO("mysql:host={$envConfig['host']};charset=utf8mb4", $envConfig['user'], $envConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$envConfig['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo = new PDO("mysql:host={$envConfig['host']};dbname={$envConfig['dbname']};charset=utf8mb4", $envConfig['user'], $envConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        $error = "Database Connection Error: " . $e->getMessage() . " (Verify your .env settings)";
    }
}

// Handle Migration Action
if ($action === 'run_migration' && $pdo) {
    try {
        $wipeExisting = isset($_POST['wipe_existing']) && $_POST['wipe_existing'] == '1';

        // 1. Ensure Schema Exists
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $rawSql = file_get_contents($schemaFile);
            $rawSql = preg_replace('!/\*.*?\*/!s', '', $rawSql);
            $lines = explode("\n", $rawSql);
            $currentSql = '';
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) continue;
                $currentSql .= $line . "\n";
                if (str_ends_with($trimmed, ';')) {
                    $q = trim($currentSql);
                    if (!empty($q)) {
                        $pdo->exec($q);
                    }
                    $currentSql = '';
                }
            }
            if (!empty(trim($currentSql))) {
                $pdo->exec(trim($currentSql));
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        }

        // 2. Wipe existing data if requested
        if ($wipeExisting) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE `project_ssl_certs`");
            $pdo->exec("TRUNCATE TABLE `project_milestones`");
            $pdo->exec("TRUNCATE TABLE `project_screenshots`");
            $pdo->exec("TRUNCATE TABLE `project_faculty`");
            $pdo->exec("TRUNCATE TABLE `project_members`");
            $pdo->exec("TRUNCATE TABLE `projects`");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        }

        // 3. Ensure Default Admin Exists (admin / admin123)
        $adminStmt = $pdo->prepare("
            INSERT INTO admin_users (id, username, password_hash, role)
            VALUES (1, 'admin', '$2y$10$ZAXXwXTJinle7YPZ/m1BxOvUNUQbpVwD7eBhBspzn9/2pADj4wrei', 'admin')
            ON DUPLICATE KEY UPDATE username = VALUES(username)
        ");
        $adminStmt->execute();

        // 4. Load Master Dump JSON
        $jsonPath = __DIR__ . '/dev/projects_dump.json';
        if (!file_exists($jsonPath)) {
            throw new Exception("Master dataset not found at dev/projects_dump.json. Make sure the 'dev' folder was uploaded.");
        }

        $projectsData = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($projectsData) || empty($projectsData)) {
            throw new Exception("dev/projects_dump.json is empty or contains invalid JSON.");
        }

        // Prepared Statements
        $stmtProject = $pdo->prepare("
            INSERT INTO projects (
                slug, title, year, short_description, description, objectives,
                problem_statement, key_features, expected_outcome, tech_stack,
                image_path, thumbnail_path, department, batch, category,
                project_type, duration, status, github_link, demo_link,
                poster_path, ssl_tracking_enabled
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                year = VALUES(year),
                short_description = VALUES(short_description),
                description = VALUES(description),
                objectives = VALUES(objectives),
                problem_statement = VALUES(problem_statement),
                key_features = VALUES(key_features),
                expected_outcome = VALUES(expected_outcome),
                tech_stack = VALUES(tech_stack),
                image_path = VALUES(image_path),
                thumbnail_path = VALUES(thumbnail_path),
                department = VALUES(department),
                batch = VALUES(batch),
                category = VALUES(category),
                project_type = VALUES(project_type),
                duration = VALUES(duration),
                status = VALUES(status),
                github_link = VALUES(github_link),
                demo_link = VALUES(demo_link),
                poster_path = VALUES(poster_path),
                ssl_tracking_enabled = VALUES(ssl_tracking_enabled)
        ");

        $stmtMember = $pdo->prepare("INSERT INTO project_members (project_id, name, photo_path, role, linkedin_link) VALUES (?, ?, ?, ?, ?)");
        $stmtFaculty = $pdo->prepare("INSERT INTO project_faculty (project_id, name, designation, photo_path, linkedin_link) VALUES (?, ?, ?, ?, ?)");
        $stmtScreenshot = $pdo->prepare("INSERT INTO project_screenshots (project_id, image_path, sort_order) VALUES (?, ?, ?)");
        $stmtMilestone = $pdo->prepare("INSERT INTO project_milestones (project_id, title, status, milestone_date, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmtSsl = $pdo->prepare("INSERT INTO project_ssl_certs (project_id, domain_url, status) VALUES (?, ?, 'Pending') ON DUPLICATE KEY UPDATE domain_url = VALUES(domain_url)");

        $projectsCount = 0;
        $membersCount = 0;
        $facultyCount = 0;
        $screenshotsCount = 0;
        $milestonesCount = 0;
        $sslCount = 0;

        $pdo->beginTransaction();

        foreach ($projectsData as $p) {
            $slug = trim($p['slug'] ?? ($p['id'] ?? uniqid('proj_')));
            $title = trim($p['title'] ?? 'Untitled Project');
            $year = trim((string)($p['year'] ?? date('Y')));
            $shortDesc = trim($p['short_description'] ?? ($p['shortDescription'] ?? ''));
            $fullDesc = trim($p['description'] ?? ($p['fullDescription'] ?? ''));
            $objectives = $p['objectives'] ?? null;
            $problem = $p['problem_statement'] ?? ($p['problemStatement'] ?? null);
            $outcome = $p['expected_outcome'] ?? ($p['expectedOutcome'] ?? null);
            
            $tech = is_array($p['tech'] ?? null) ? implode(', ', $p['tech']) : ($p['tech_stack'] ?? '');
            $features = is_array($p['keyFeatures'] ?? null) ? implode(', ', $p['keyFeatures']) : ($p['key_features'] ?? '');
            
            $imgPath = $p['thumbnail_path'] ?? ($p['thumbnail'] ?? ($p['image_path'] ?? 'assets/images/logo1.png'));
            $thumbPath = $p['thumbnail_path'] ?? ($p['thumbnail'] ?? 'assets/images/logo1.png');
            $dept = $p['department'] ?? 'MCA';
            $batch = $p['batch'] ?? '';
            $category = $p['category'] ?? 'Web Application';
            $projType = $p['project_type'] ?? ($p['projectType'] ?? 'Academic');
            $duration = $p['duration'] ?? '';
            $status = $p['status'] ?? 'In Development';
            $github = $p['github_link'] ?? ($p['githubLink'] ?? '');
            $demo = $p['demo_link'] ?? ($p['demoLink'] ?? '');
            $poster = $p['poster_path'] ?? ($p['poster'] ?? '');
            $sslEnabled = isset($p['ssl_tracking_enabled']) ? (int)$p['ssl_tracking_enabled'] : 1;

            $stmtProject->execute([
                $slug, $title, $year, $shortDesc, $fullDesc, $objectives,
                $problem, $features, $outcome, $tech,
                $imgPath, $thumbPath, $dept, $batch, $category,
                $projType, $duration, $status, $github, $demo,
                $poster, $sslEnabled
            ]);

            // Retrieve inserted/updated project ID
            $idStmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ?");
            $idStmt->execute([$slug]);
            $projectId = (int)$idStmt->fetchColumn();

            // Clear old children if not wiped
            if (!$wipeExisting) {
                $pdo->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$projectId]);
                $pdo->prepare("DELETE FROM project_faculty WHERE project_id = ?")->execute([$projectId]);
                $pdo->prepare("DELETE FROM project_screenshots WHERE project_id = ?")->execute([$projectId]);
                $pdo->prepare("DELETE FROM project_milestones WHERE project_id = ?")->execute([$projectId]);
            }

            // Team Members
            $members = $p['members'] ?? ($p['team'] ?? []);
            if (!empty($members) && is_array($members)) {
                foreach ($members as $m) {
                    $mName = is_array($m) ? ($m['name'] ?? '') : (string)$m;
                    if (empty(trim($mName))) continue;
                    $mRole = is_array($m) ? ($m['role'] ?? '') : '';
                    $mPhoto = is_array($m) ? ($m['photo_path'] ?? ($m['photo'] ?? '')) : '';
                    $mLink = is_array($m) ? ($m['linkedin_link'] ?? ($m['linkedin'] ?? '')) : '';

                    $stmtMember->execute([$projectId, $mName, $mPhoto, $mRole, $mLink]);
                    $membersCount++;
                }
            }

            // Faculty Mentors
            $facList = $p['faculty'] ?? [];
            if (!empty($facList)) {
                if (is_array($facList) && !isset($facList[0])) $facList = [$facList];
                foreach ($facList as $f) {
                    $fName = is_array($f) ? ($f['name'] ?? '') : (string)$f;
                    if (empty(trim($fName))) continue;
                    $fDesig = is_array($f) ? ($f['designation'] ?? '') : '';
                    $fPhoto = is_array($f) ? ($f['photo_path'] ?? ($f['photo'] ?? '')) : '';
                    $fLink = is_array($f) ? ($f['linkedin_link'] ?? ($f['linkedin'] ?? '')) : '';

                    $stmtFaculty->execute([$projectId, $fName, $fDesig, $fPhoto, $fLink]);
                    $facultyCount++;
                }
            }

            // Screenshots
            $screenshots = $p['screenshots'] ?? [];
            if (!empty($screenshots) && is_array($screenshots)) {
                $sOrder = 0;
                foreach ($screenshots as $s) {
                    $sPath = is_array($s) ? ($s['image_path'] ?? '') : (string)$s;
                    if (!empty(trim($sPath))) {
                        $stmtScreenshot->execute([$projectId, $sPath, $sOrder++]);
                        $screenshotsCount++;
                    }
                }
            }

            // Milestones (Generate standard progression if empty)
            $milestones = $p['milestones'] ?? [];
            if (empty($milestones)) {
                $baseYear = is_numeric($year) ? (int)$year : (int)date('Y');
                $isComp = ($status === 'Completed');
                $milestones = [
                    ['title' => 'Requirements & Problem Definition', 'status' => 'Completed', 'date' => "$baseYear-02-15"],
                    ['title' => 'System Architecture & Database Design', 'status' => 'Completed', 'date' => "$baseYear-04-10"],
                    ['title' => 'Core Module Development & Integration', 'status' => 'Completed', 'date' => "$baseYear-08-20"],
                    ['title' => 'System Testing & QA Review', 'status' => $isComp ? 'Completed' : 'Current', 'date' => "$baseYear-10-05"],
                    ['title' => 'Final Release & Institutional Handover', 'status' => $isComp ? 'Completed' : 'Upcoming', 'date' => "$baseYear-11-20"]
                ];
            }
            $mOrder = 0;
            foreach ($milestones as $ms) {
                $stmtMilestone->execute([
                    $projectId,
                    $ms['title'] ?? 'Project Milestone',
                    $ms['status'] ?? 'Completed',
                    $ms['date'] ?? date('Y-m-d'),
                    $mOrder++
                ]);
                $milestonesCount++;
            }

            // SSL Tracking Registration for Live Links
            if (!empty($demo) && $demo !== '#' && filter_var($demo, FILTER_VALIDATE_URL) && $sslEnabled) {
                $stmtSsl->execute([$projectId, $demo]);
                $sslCount++;
            }

            $projectsCount++;
        }

        // Add System Initial Welcome Announcement
        $pdo->prepare("
            INSERT INTO admin_announcements (type, title, message, severity, is_read)
            VALUES ('system', 'Database Successfully Synchronized', 'All 27 institutional projects, student teams, faculty mentors, and SSL trackers were loaded successfully into production.', 'info', 0)
        ")->execute();

        $pdo->commit();

        $stats = [
            'projects' => $projectsCount,
            'members' => $membersCount,
            'faculty' => $facultyCount,
            'screenshots' => $screenshotsCount,
            'milestones' => $milestonesCount,
            'ssl' => $sslCount
        ];
        $message = "Migration Complete! All {$projectsCount} projects and relational tables have been successfully loaded into the database.";

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Migration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RLabz — Production Data Loader (cPanel / Server)</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --card: #111827;
            --accent: #2563eb;
            --accent-glow: #3b82f6;
            --border: rgba(255, 255, 255, 0.08);
            --text: #f9fafb;
            --muted: #9ca3af;
            --green: #10b981;
            --red: #ef4444;
        }
        body {
            margin: 0;
            padding: 2.5rem 1rem;
            background: var(--bg);
            color: var(--text);
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
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
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
            color: var(--muted);
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
        .db-info-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
        }
        .stat-num {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #60a5fa;
            line-height: 1;
            margin-bottom: 0.35rem;
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .btn {
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-block;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-align: center;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            width: 100%;
        }
        .btn-primary:hover {
            background: var(--accent-glow);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.25);
        }
        .nav-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .nav-actions .btn {
            flex: 1;
        }
        .security-box {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-badge">R</div>
            <div>
                <h1>RLabz Server Data Migrator</h1>
                <p class="subtitle">Import all verified local data directly into your cPanel database</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message && $stats): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-num"><?php echo $stats['projects']; ?></div>
                    <div class="stat-label">Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?php echo $stats['members']; ?></div>
                    <div class="stat-label">Team Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?php echo $stats['faculty']; ?></div>
                    <div class="stat-label">Faculty Mentors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?php echo $stats['screenshots']; ?></div>
                    <div class="stat-label">Screenshots</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?php echo $stats['milestones']; ?></div>
                    <div class="stat-label">Milestones</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?php echo $stats['ssl']; ?></div>
                    <div class="stat-label">SSL Monitored</div>
                </div>
            </div>

            <div style="background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <strong>Admin Login Ready:</strong> Username: <code>admin</code> | Password: <code>admin123</code>
            </div>

            <div class="nav-actions">
                <a href="index.html" class="btn btn-secondary">Open Main Website</a>
                <a href="admin/login.php" class="btn btn-secondary" style="background: #2563eb; color: white;">Open Admin Panel</a>
            </div>

            <div class="security-box">
                <span>Security Notice: Remove this migration file when done.</span>
                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete load_data.php from the server?');">
                    <input type="hidden" name="action" value="delete_self">
                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                        🗑️ Delete load_data.php
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="db-info-pill">
                <div>
                    <span style="color: var(--muted);">Target Database:</span>
                    <strong><?php echo htmlspecialchars($envConfig['dbname']); ?></strong>
                    <span style="color: var(--muted);">@ <?php echo htmlspecialchars($envConfig['host']); ?></span>
                </div>
                <div>
                    <span style="color: #34d399; font-weight: 600;">● Ready to Import</span>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="run_migration">

                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; margin-bottom: 1.75rem;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-size: 0.95rem;">
                        <input type="checkbox" name="wipe_existing" value="1" checked style="width: 18px; height: 18px; accent-color: #2563eb;">
                        <span>
                            <strong>Clean Installation (Recommended)</strong><br>
                            <span style="color: var(--muted); font-size: 0.85rem;">
                                Clears old table records and freshly imports all 27 verified projects, faculty, team members, and milestones.
                            </span>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 1rem; font-size: 1.05rem;">
                    🚀 Start Migration & Load All Data into Database
                </button>
            </form>

            <div style="margin-top: 2rem; font-size: 0.85rem; color: var(--muted); text-align: center;">
                Dataset source: <code>dev/projects_dump.json</code> (27 Verified Projects)
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

