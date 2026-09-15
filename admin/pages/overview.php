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
$activeSslMonitors = 0;
$sslIssues = 0;
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

        // SSL Monitoring Stats
        $stmtSslStats = $pdo->query("
            SELECT 
                COUNT(*) as total_monitored,
                SUM(CASE WHEN status != 'active' OR (expiry_date IS NOT NULL AND DATEDIFF(expiry_date, CURDATE()) < 30) THEN 1 ELSE 0 END) as issues
            FROM project_ssl_certs
        ");
        $sslStats = $stmtSslStats->fetch();
        $activeSslMonitors = $sslStats['total_monitored'] ?: 0;
        $sslIssues = $sslStats['issues'] ?: 0;

        // Recent 5
        $stmtRecent = $pdo->query("SELECT id, title, slug, status, year, image_path, category FROM projects ORDER BY id DESC LIMIT 5");
        $recentProjects = $stmtRecent->fetchAll();

        // Admin Announcements
        $stmtAnnouncements = $pdo->query("SELECT * FROM admin_announcements ORDER BY created_at DESC LIMIT 50");
        $announcements = $stmtAnnouncements->fetchAll();
        $unreadCount = 0;
        foreach ($announcements as $a) {
            if (!$a['is_read']) $unreadCount++;
        }

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

<!-- Admin Announcements -->
<div class="card" style="display: flex; flex-direction: column; max-height: 400px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2><i class="fa-solid fa-bullhorn"></i> Announcements</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge badge-default" id="unread-count" style="background: <?php echo $unreadCount > 0 ? '#3b82f6' : '#334155'; ?>;"><?php echo $unreadCount; ?> new</span>
            <button class="btn-action primary" onclick="refreshSslNow()" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" id="btn-refresh-cron"><i class="fa-solid fa-rotate"></i> Check SSL</button>
        </div>
    </div>
    <div class="announcements-container" style="flex: 1; overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 10px;">
        <?php if(empty($announcements)): ?>
            <div style="text-align:center; padding: 2rem; color: var(--text-muted);">No announcements yet.</div>
        <?php else: ?>
            <?php foreach($announcements as $a): 
                $icon = 'fa-info-circle';
                $color = '#3b82f6'; // info
                $bg = 'rgba(59, 130, 246, 0.1)';
                
                if ($a['severity'] === 'success') {
                    $icon = 'fa-check-circle';
                    $color = '#4ade80';
                    $bg = 'rgba(74, 222, 128, 0.1)';
                } elseif ($a['severity'] === 'warning') {
                    $icon = 'fa-triangle-exclamation';
                    $color = '#fbbf24';
                    $bg = 'rgba(251, 191, 36, 0.1)';
                } elseif ($a['severity'] === 'danger') {
                    $icon = 'fa-circle-exclamation';
                    $color = '#ef4444';
                    $bg = 'rgba(239, 68, 68, 0.1)';
                }
                
                $opacity = $a['is_read'] ? '0.6' : '1';
                $borderLeft = $a['is_read'] ? '3px solid transparent' : "3px solid $color";
            ?>
            <div class="announcement-item" id="announcement-<?= $a['id'] ?>" style="background: #1e293b; border-radius: 8px; padding: 12px; display: flex; gap: 12px; border-left: <?= $borderLeft ?>; opacity: <?= $opacity ?>; transition: opacity 0.2s;">
                <div style="color: <?= $color ?>; background: <?= $bg ?>; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid <?= $icon ?>"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #f8fafc; margin-bottom: 4px; display:flex; justify-content:space-between;">
                        <?= htmlspecialchars($a['title']) ?>
                        <span style="font-size:0.75rem; color:#64748b; font-weight:normal;"><?= date('M j, Y H:i', strtotime($a['created_at'])) ?></span>
                    </div>
                    <div style="font-size: 0.85rem; color: #cbd5e1; white-space: pre-line; line-height: 1.4;"><?= htmlspecialchars($a['message']) ?></div>
                </div>
                <?php if(!$a['is_read']): ?>
                    <button class="btn-action" style="align-self: center;" title="Mark as Read" onclick="markRead(<?= $a['id'] ?>)">
                        <i class="fa-solid fa-check"></i>
                    </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
async function markRead(id) {
    try {
        const res = await fetch(`../api/mark_announcement_read.php?id=${id}`);
        const data = await res.json();
        if(data.success) {
            const item = document.getElementById(`announcement-${id}`);
            item.style.opacity = '0.6';
            item.style.borderLeft = '3px solid transparent';
            const btn = item.querySelector('button');
            if(btn) btn.remove();
            
            const badge = document.getElementById('unread-count');
            let count = parseInt(badge.textContent);
            if(count > 0) {
                count--;
                badge.textContent = count + ' new';
                if(count === 0) badge.style.background = '#334155';
            }
        }
    } catch(e) { console.error('Failed to mark read', e); }
}

async function refreshSslNow() {
    const btn = document.getElementById('btn-refresh-cron');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;
    try {
        await fetch(`../api/cron_ssl_reminder.php`);
        window.location.reload();
    } catch(e) {
        alert('Failed to run SSL check.');
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Check SSL';
        btn.disabled = false;
    }
}
</script>

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
                                <img src="../../<?php echo htmlspecialchars($p['image_path']); ?>" class="project-thumb" alt="thumb" onerror="this.src='../../assets/images/logo1.png'">
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
