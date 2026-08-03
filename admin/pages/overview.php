<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../api/db.php';

$pageTitle = "Overview";

// Fetch Stats
$totalProjects = 0;
$completedProjects = 0;
$deployedProjects = 0;
$inDevProjects = 0;
$totalMembers = 0;
$recentProjects = [];

$chartDataStatus = ['Completed' => 0, 'Deployed' => 0, 'In Development' => 0];
$chartDataYear = [];

if ($pdo) {
    try {
        // Stats queries
        $stmt = $pdo->query("SELECT status, year, title, category FROM projects ORDER BY year DESC, id DESC");
        $allProjects = $stmt->fetchAll();
        $totalProjects = count($allProjects);
        
        foreach ($allProjects as $p) {
            if ($p['status'] === 'Completed') $completedProjects++;
            if ($p['status'] === 'Deployed') $deployedProjects++;
            if ($p['status'] === 'In Development') $inDevProjects++;
            
            $chartDataStatus[$p['status']] = ($chartDataStatus[$p['status']] ?? 0) + 1;
            
            $y = $p['year'] ? $p['year'] : 'N/A';
            $chartDataYear[$y] = ($chartDataYear[$y] ?? 0) + 1;
        }

        $stmtMembers = $pdo->query("SELECT COUNT(*) FROM project_members");
        $totalMembers = $stmtMembers->fetchColumn();

        // Recent 5
        $stmtRecent = $pdo->query("SELECT id, title, slug, status, year, image_path, category FROM projects ORDER BY id DESC LIMIT 5");
        $recentProjects = $stmtRecent->fetchAll();

        // Expiring SSL Certificates
        $stmtExpiring = $pdo->query("SELECT p.title, s.domain_url, s.expiry_date, DATEDIFF(s.expiry_date, CURDATE()) as days_left 
                                     FROM project_ssl_certs s 
                                     JOIN projects p ON s.project_id = p.id 
                                     WHERE s.expiry_date IS NOT NULL 
                                     AND s.expiry_date >= CURDATE() AND s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                                     ORDER BY s.expiry_date ASC");
        $expiringSsl = $stmtExpiring->fetchAll();

    } catch (PDOException $e) {
        $error = "Failed to load stats: " . $e->getMessage();
    }
}

// Format for JS
$statusLabels = json_encode(array_keys($chartDataStatus));
$statusData = json_encode(array_values($chartDataStatus));

ksort($chartDataYear);
$yearLabels = json_encode(array_keys($chartDataYear));
$yearData = json_encode(array_values($chartDataYear));

require_once '../includes/layout_header.php';
?>

<?php if(isset($error)): ?>
    <div class="alert alert-error"><i class="fa-solid fa-exclamation-triangle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Dashboard Overview</h1>
        <p>Welcome back! Here's what's happening with your projects.</p>
    </div>
    <div>
        <a href="add_project.php" class="btn-header primary"><i class="fa-solid fa-plus"></i> New Project</a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-card-icon"><i class="fa-solid fa-folder-open"></i></div>
        <div class="stat-card-value"><?php echo $totalProjects; ?></div>
        <div class="stat-card-label">Total Projects</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-card-icon"><i class="fa-solid fa-check-circle"></i></div>
        <div class="stat-card-value"><?php echo $completedProjects; ?></div>
        <div class="stat-card-label">Completed</div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-card-icon"><i class="fa-solid fa-rocket"></i></div>
        <div class="stat-card-value"><?php echo $deployedProjects; ?></div>
        <div class="stat-card-label">Deployed</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-card-icon"><i class="fa-solid fa-code"></i></div>
        <div class="stat-card-value"><?php echo $inDevProjects; ?></div>
        <div class="stat-card-label">In Development</div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-card-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-card-value"><?php echo $totalMembers; ?></div>
        <div class="stat-card-label">Team Members</div>
    </div>

</div>

<!-- Charts Row -->
<div class="charts-grid">
    <div class="chart-container">
        <h3><i class="fa-solid fa-chart-pie"></i> Projects by Status</h3>
        <div class="chart-wrapper donut">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="chart-container">
        <h3><i class="fa-solid fa-chart-bar"></i> Projects by Year</h3>
        <div class="chart-wrapper">
            <canvas id="yearChart" style="height: 250px;"></canvas>
        </div>
    </div>
</div>

<!-- Expiring SSL -->
<?php if (!empty($expiringSsl)): ?>
<div class="card" style="border: 1px solid #ef4444;">
    <h2 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Expiring SSL Certificates (Next 30 Days)</h2>
    <div style="overflow-x:auto;">
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Domain</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($expiringSsl as $ssl): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ssl['title']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($ssl['domain_url']); ?>" target="_blank" style="color:#38bdf8;"><?php echo htmlspecialchars($ssl['domain_url']); ?></a></td>
                    <td><?php echo htmlspecialchars($ssl['expiry_date']); ?></td>
                    <td><span class="badge" style="background:#7f1d1d; color:#fca5a5;"><?php echo htmlspecialchars($ssl['days_left']); ?> Days</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent Projects -->
<div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Recently Added</h2>
    <div style="overflow-x:auto;">
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Category</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($recentProjects)): ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No projects found.</td></tr>
                <?php else: ?>
                    <?php foreach($recentProjects as $p): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <img src="../../<?php echo htmlspecialchars($p['image_path']); ?>" class="project-thumb" alt="thumb" onerror="this.src='../../assets/images/rz-logo.webp'">
                                <div>
                                    <div class="project-title-cell"><?php echo htmlspecialchars($p['title']); ?></div>
                                    <div class="project-title-meta">/<?php echo htmlspecialchars($p['slug']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);"><?php echo htmlspecialchars($p['category']); ?></td>
                        <td>
                            <span class="badge badge-default"><?php echo htmlspecialchars($p['year']); ?></span>
                        </td>
                        <td>
                            <?php
                                $statusClass = 'badge-default';
                                if($p['status'] == 'Deployed') $statusClass = 'badge-deployed';
                                if($p['status'] == 'Completed') $statusClass = 'badge-completed';
                                if($p['status'] == 'In Development') $statusClass = 'badge-dev';
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                        </td>
                        <td>
                            <div class="project-actions">
                                <a href="../edit_project.php?id=<?php echo $p['id']; ?>" class="btn-action btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <a href="../../public/project-details.html?id=<?php echo htmlspecialchars($p['slug']); ?>" class="btn-action btn-preview" title="View" target="_blank"><i class="fa-solid fa-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Chart.js Default Config for Dark Theme
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;

    // Status Chart (Doughnut)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo $statusLabels; ?>,
            datasets: [{
                data: <?php echo $statusData; ?>,
                backgroundColor: ['#38bdf8', '#4ade80', '#fbbf24', '#a78bfa'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
            }
        }
    });

    // Year Chart (Bar)
    const ctxYear = document.getElementById('yearChart').getContext('2d');
    new Chart(ctxYear, {
        type: 'bar',
        data: {
            labels: <?php echo $yearLabels; ?>,
            datasets: [{
                label: 'Projects',
                data: <?php echo $yearData; ?>,
                backgroundColor: 'rgba(56, 189, 248, 0.2)',
                borderColor: '#38bdf8',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php require_once '../includes/layout_footer.php'; ?>
