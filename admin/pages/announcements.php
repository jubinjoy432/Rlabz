<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../api/db.php';
$pageTitle = 'Announcements';
require_once '../includes/layout_header.php';

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    $pdo->exec("UPDATE admin_announcements SET is_read = 1 WHERE is_read = 0");
    header("Location: announcements.php");
    exit;
}

// Clear all if requested
if (isset($_POST['clear_all'])) {
    $pdo->exec("DELETE FROM admin_announcements");
    header("Location: announcements.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM admin_announcements ORDER BY created_at DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load announcements: " . $e->getMessage();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Announcements</h1>
        <p>View all system alerts, notifications, and SSL warnings.</p>
    </div>
    <div class="page-header-right" style="display:flex; gap:10px; align-items:center;">
        <form method="POST" style="margin:0;">
            <button type="submit" name="mark_all_read" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 7px; padding: 0.45rem 0.95rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;">
                <i class="fa-solid fa-check-double"></i> Mark All as Read
            </button>
        </form>
        <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to clear all announcements? This cannot be undone.');">
            <button type="submit" name="clear_all" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #ef4444; color: #ffffff; border: 1px solid transparent; border-radius: 7px; padding: 0.45rem 0.95rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25); white-space: nowrap;">
                <i class="fa-solid fa-trash"></i> Clear All
            </button>
        </form>
    </div>
</div>

<div class="card" style="padding: 1.5rem;">
    <?php if(isset($error)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php else: ?>
        <?php if(empty($announcements)): ?>
            <div style="text-align:center; padding: 4rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-bell-slash" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                <p>No announcements found.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach($announcements as $a): 
                    $icon = 'fa-info-circle';
                    $color = '#3b82f6'; // info
                    $bg = 'rgba(59, 130, 246, 0.12)';
                    
                    if ($a['severity'] === 'success') {
                        $icon = 'fa-check-circle';
                        $color = '#22c55e';
                        $bg = 'rgba(34, 197, 94, 0.12)';
                    } elseif ($a['severity'] === 'warning') {
                        $icon = 'fa-triangle-exclamation';
                        $color = '#f59e0b';
                        $bg = 'rgba(245, 158, 11, 0.12)';
                    } elseif ($a['severity'] === 'danger') {
                        $icon = 'fa-circle-exclamation';
                        $color = '#ef4444';
                        $bg = 'rgba(239, 68, 68, 0.12)';
                    }
                    
                    $opacity = $a['is_read'] ? '0.65' : '1';
                    $borderLeft = $a['is_read'] ? '4px solid #cbd5e1' : "4px solid $color";
                ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px 20px; display: flex; gap: 15px; border-left: <?= $borderLeft ?>; opacity: <?= $opacity ?>; transition: all 0.2s ease;">
                    <div style="color: <?= $color ?>; background: <?= $bg ?>; width: 42px; height: 42px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.15rem;">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #1e293b; font-size: 1.05rem; margin-bottom: 6px; display:flex; justify-content:space-between; align-items:center;">
                            <?= htmlspecialchars($a['title']) ?>
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;"><?= date('M d, Y h:i A', strtotime($a['created_at'])) ?></span>
                        </div>
                        <div style="color: #475569; font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;"><?= htmlspecialchars($a['message']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/layout_footer.php'; ?>
