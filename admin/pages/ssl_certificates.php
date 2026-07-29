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
    $stmt = $pdo->query("SELECT p.id as project_id, p.title, s.domain_url, s.provider, s.issue_date, s.expiry_date, DATEDIFF(s.expiry_date, CURDATE()) as days_left 
                         FROM project_ssl_certs s 
                         JOIN projects p ON s.project_id = p.id 
                         ORDER BY s.expiry_date ASC");
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
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding: 2rem;">No SSL certificates tracked. Add one by editing a project.</td></tr>
                    <?php else: ?>
                        <?php foreach($certs as $cert): ?>
                            <?php 
                            $daysLeft = $cert['days_left'];
                            $statusBadge = '<span class="badge badge-completed">Valid</span>';
                            if ($daysLeft !== null) {
                                if ($daysLeft <= 0) {
                                    $statusBadge = '<span class="badge badge-dev" style="background:#7f1d1d; color:#fca5a5;">Expired</span>';
                                } elseif ($daysLeft <= 30) {
                                    $statusBadge = '<span class="badge badge-dev" style="background:#b45309; color:#fde68a;">Expiring Soon (' . $daysLeft . ' days)</span>';
                                } else {
                                    $statusBadge = '<span class="badge badge-deployed">' . $daysLeft . ' days left</span>';
                                }
                            } else {
                                $statusBadge = '<span class="badge badge-default">Unknown</span>';
                            }
                            ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cert['title']); ?></strong></td>
                            <td><a href="<?php echo htmlspecialchars($cert['domain_url']); ?>" target="_blank" style="color:#38bdf8;"><?php echo htmlspecialchars($cert['domain_url']); ?></a></td>
                            <td><?php echo htmlspecialchars($cert['provider'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cert['issue_date'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cert['expiry_date'] ?: 'N/A'); ?></td>
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
