<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../api/db.php';
$pageTitle = 'SSL Certificates';
require_once '../includes/layout_header.php';

try {
    // 1. Sync any projects with a website/demo link and ssl_tracking_enabled = 1 into project_ssl_certs if missing
    $pdo->exec("
        INSERT INTO project_ssl_certs (project_id, domain_url, status)
        SELECT p.id, p.demo_link, 'Pending'
        FROM projects p
        LEFT JOIN project_ssl_certs s ON p.id = s.project_id
        WHERE s.id IS NULL 
          AND p.demo_link IS NOT NULL 
          AND p.demo_link != '' 
          AND p.demo_link != '#'
          AND (p.ssl_tracking_enabled = 1 OR p.ssl_tracking_enabled IS NULL)
    ");

    // 2. Fetch all website links stored in database where ssl_tracking_enabled != 0
    $stmt = $pdo->query("
        SELECT 
            p.id as project_id, 
            p.title, 
            p.ssl_tracking_enabled,
            COALESCE(NULLIF(s.domain_url, ''), NULLIF(p.demo_link, '')) as domain_url,
            s.provider, 
            s.issue_date, 
            s.expiry_date, 
            s.status,
            s.last_error,
            DATEDIFF(s.expiry_date, CURDATE()) as days_left 
        FROM projects p
        LEFT JOIN project_ssl_certs s ON s.project_id = p.id 
        WHERE (p.ssl_tracking_enabled = 1 OR p.ssl_tracking_enabled IS NULL)
          AND (
            (s.domain_url IS NOT NULL AND s.domain_url != '' AND s.domain_url != '#')
            OR
            (p.demo_link IS NOT NULL AND p.demo_link != '' AND p.demo_link != '#')
          )
        ORDER BY 
            CASE 
                WHEN s.status = 'Check Failed' OR s.expiry_date IS NULL OR s.status = 'Pending' OR s.last_error IS NOT NULL THEN 1 
                ELSE 0 
            END DESC,
            s.expiry_date ASC
    ");
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load SSL Certificates: " . $e->getMessage();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>SSL Certificates</h1>
        <p>Monitor and manage project SSL certificates.</p>
    </div>
    <div class="page-header-right">
        <button id="btnFetchAllSsl" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #0b5394; color: #ffffff; border: 1px solid rgba(255,255,255,0.15); border-radius: 7px; padding: 0.45rem 0.95rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" onclick="fetchAllSsl()"><i class="fa-solid fa-sync"></i> Refresh SSL Status</button>
    </div>
</div>

<div class="card">
    <?php if(isset($error)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Domain URL</th>
                        <th>Provider</th>
                        <th>Issue Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($certs)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding: 2rem;">No website links or SSL certificates tracked. Enable SSL Fetch on projects to track.</td></tr>
                    <?php else: ?>
                        <?php foreach($certs as $cert): ?>
                            <?php 
                            $domainUrl = trim($cert['domain_url'] ?? '');
                            $daysLeft = $cert['days_left'];
                            $status = trim($cert['status'] ?? '');
                            $lastError = trim($cert['last_error'] ?? '');
                            $expiryDate = $cert['expiry_date'];

                            // Determine if SSL could not be fetched
                            $isFetchFailed = ($status === 'Check Failed' || !empty($lastError) || empty($expiryDate));

                            if ($isFetchFailed) {
                                $statusBadge = '<span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-circle-xmark"></i> Not able to fetch</span>';
                                if (!empty($lastError)) {
                                    $statusBadge .= '<div style="font-size: 0.72rem; color: #f87171; margin-top: 3px; max-width: 220px; line-height: 1.25;" title="' . htmlspecialchars($lastError) . '">' . htmlspecialchars(mb_strimwidth($lastError, 0, 48, '...')) . '</div>';
                                }
                            } else {
                                if ($daysLeft !== null && $daysLeft <= 0) {
                                    $statusBadge = '<span class="badge badge-dev" style="background:#7f1d1d; color:#fca5a5; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-triangle-exclamation"></i> Expired</span>';
                                } elseif ($daysLeft !== null && $daysLeft <= 30) {
                                    $statusBadge = '<span class="badge badge-dev" style="background:#b45309; color:#fde68a; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-clock"></i> Expiring Soon (' . $daysLeft . ' days)</span>';
                                } else {
                                    $statusBadge = '<span class="badge badge-deployed" style="background:#064e3b; color:#6ee7b7; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-circle-check"></i> ' . ($daysLeft !== null ? $daysLeft . ' days left' : 'Valid') . '</span>';
                                }
                            }
                            ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cert['title']); ?></strong></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($domainUrl); ?>" target="_blank" rel="noopener" style="color:#38bdf8; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <?php echo htmlspecialchars($domainUrl); ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars((!$isFetchFailed && $cert['provider']) ? $cert['provider'] : 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars((!$isFetchFailed && $cert['issue_date']) ? $cert['issue_date'] : 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars((!$isFetchFailed && $cert['expiry_date']) ? $cert['expiry_date'] : 'N/A'); ?></td>
                            <td><?php echo $statusBadge; ?></td>
                            <td>
                                <div class="project-actions">
                                    <a href="../edit_project.php?id=<?php echo $cert['project_id']; ?>" class="btn-action btn-edit" title="Edit Project"><i class="fa-solid fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/layout_footer.php'; ?>

<script>
function fetchAllSsl() {
    const btn = document.getElementById('btnFetchAllSsl');
    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
        btn.disabled = true;
    }
    fetch('../api/cron_ssl_reminder.php')
        .then(r => r.text())
        .then(res => {
            window.location.reload();
        })
        .catch(e => {
            alert("Failed to refresh SSL");
            window.location.reload();
        });
}

// Auto-fetch in background without needing the button
window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        fetch('../api/cron_ssl_reminder.php').catch(e => console.error('Auto SSL update failed', e));
    }, 2000); // Small delay to let page render first
});
</script>
