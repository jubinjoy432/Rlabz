<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../api/db.php';

$pageTitle = "Announcements";

$announcements = [];
$totalCount = 0;
$unreadCount = 0;
$expiringCount = 0;
$expiredCount = 0;
$failedCount = 0;

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM admin_announcements ORDER BY is_read ASC, created_at DESC");
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalCount = count($announcements);
        
        foreach ($announcements as $a) {
            if (!$a['is_read']) $unreadCount++;
            $type = $a['type'] ?? '';
            if (in_array($type, ['ssl_notice', 'ssl_warning', 'ssl_critical']) || stripos($a['title'], 'Expiring') !== false) {
                $expiringCount++;
            } elseif ($type === 'ssl_expired' || stripos($a['title'], 'Expired') !== false) {
                $expiredCount++;
            } elseif ($type === 'ssl_failed' || $a['severity'] === 'danger') {
                $failedCount++;
            }
        }
    } catch (PDOException $e) {
        $error = "Failed to load announcements: " . $e->getMessage();
    }
} else {
    $error = "Database not connected.";
}

require_once '../includes/layout_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Announcements & Alerts</h1>
        <p>Monitor SSL certificate expiry warnings, failed checks, and system notifications.</p>
    </div>
    <div class="page-header-right" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <?php if($unreadCount > 0): ?>
        <button type="button" class="btn-header" style="background: #ecfdf5; color: #047857; border-color: #a7f3d0;" onclick="markAllRead()">
            <i class="fa-solid fa-check-double"></i> Mark All as Read
        </button>
        <?php endif; ?>
        <button type="button" class="btn-header" style="background: #fff1f2; color: #e11d48; border-color: #fecdd3;" onclick="clearReadAnnouncements()">
            <i class="fa-solid fa-trash-can"></i> Clear Read
        </button>
        <button type="button" class="btn-header primary" id="btnRefreshSsl" onclick="refreshSslCron()">
            <i class="fa-solid fa-rotate"></i> Check SSL Now
        </button>
    </div>
</div>

<!-- Stats Counter Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.12); color: #2563eb;">
            <i class="fa-solid fa-bell"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $totalCount; ?></div>
            <div class="stat-label">Total Notifications</div>
        </div>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid #6366f1;">
        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5;">
            <i class="fa-solid fa-envelope-open-text"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $unreadCount; ?></div>
            <div class="stat-label">Unread Alerts</div>
        </div>
    </div>

    <!-- MAIN PURPOSE: Expiring Soon -->
    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $expiringCount; ?></div>
            <div class="stat-label">SSL Expiring Soon</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #ef4444;">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $expiredCount; ?></div>
            <div class="stat-label">SSL Expired (&le; 0 days)</div>
        </div>
    </div>
</div>

<div class="card">
    <!-- Filter and Search Toolbar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.85rem;">
        <!-- Quick Filter Buttons -->
        <div class="filter-pills" id="annFilterPills" style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
            <button class="filter-pill active" data-filter="all">All (<?php echo $totalCount; ?>)</button>
            <button class="filter-pill" data-filter="unread">Unread (<?php echo $unreadCount; ?>)</button>
            <button class="filter-pill" data-filter="expiring" style="--pill-color: #d97706;">Expiring Soon (<?php echo $expiringCount; ?>)</button>
            <button class="filter-pill" data-filter="expired" style="--pill-color: #dc2626;">Expired (<?php echo $expiredCount; ?>)</button>
        </div>

        <!-- Search Bar -->
        <div class="search-bar" style="margin: 0; width: 280px;">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="annSearch" placeholder="Search announcements...">
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if(empty($announcements)): ?>
        <div class="empty-state" style="padding: 3rem 1rem; text-align: center; color: var(--text-muted);">
            <i class="fa-solid fa-circle-check" style="font-size: 2.2rem; color: #10b981; margin-bottom: 0.5rem; display: block;"></i>
            <h3 style="font-size: 1.1rem; color: var(--text-primary);">No Announcements Found</h3>
            <p style="font-size: 0.85rem; margin-top: 0.25rem;">All monitored project SSL certificates and servers are operating normally.</p>
        </div>
    <?php else: ?>
        <div class="announcements-list" id="announcementsList" style="display: flex; flex-direction: column; gap: 0.55rem;">
            <?php foreach($announcements as $a): 
                $type = $a['type'] ?? '';
                $severity = $a['severity'] ?? 'info';
                $isRead = (bool)$a['is_read'];

                // Filter Category
                $category = 'other';
                if (in_array($type, ['ssl_notice', 'ssl_warning', 'ssl_critical']) || stripos($a['title'], 'Expiring') !== false) {
                    $category = 'expiring';
                    $icon = 'fa-hourglass-half';
                    $accentColor = '#d97706';
                    $badgeBg = '#fef3c7';
                    $badgeText = '#92400e';
                    $label = 'Expiring Soon';
                } elseif ($type === 'ssl_expired' || stripos($a['title'], 'Expired') !== false) {
                    $category = 'expired';
                    $icon = 'fa-triangle-exclamation';
                    $accentColor = '#dc2626';
                    $badgeBg = '#fee2e2';
                    $badgeText = '#991b1b';
                    $label = 'Expired';
                } elseif ($type === 'ssl_renewed' || $severity === 'success') {
                    $category = 'renewed';
                    $icon = 'fa-shield-heart';
                    $accentColor = '#16a34a';
                    $badgeBg = '#dcfce7';
                    $badgeText = '#166534';
                    $label = 'Renewed';
                } else {
                    $icon = 'fa-info-circle';
                    $accentColor = '#2563eb';
                    $badgeBg = '#dbeafe';
                    $badgeText = '#1e40af';
                    $label = 'Notice';
                }
            ?>
            <div class="announcement-card-item" 
                 id="annItem-<?php echo $a['id']; ?>"
                 data-id="<?php echo $a['id']; ?>"
                 data-category="<?php echo $category; ?>"
                 data-read="<?php echo $isRead ? '1' : '0'; ?>"
                 style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 13px; display: flex; align-items: center; gap: 10px; border-left: 4px solid <?php echo $isRead ? '#cbd5e1' : $accentColor; ?>; opacity: <?php echo $isRead ? '0.75' : '1'; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s ease;">
                
                <div style="width: 30px; height: 30px; border-radius: 6px; background: <?php echo $badgeBg; ?>; color: <?php echo $accentColor; ?>; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;">
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                </div>

                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: nowrap;">
                        <span style="display: inline-flex; align-items: center; gap: 0.45rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <strong style="color: #0f172a; font-size: 0.88rem;"><?php echo htmlspecialchars($a['title']); ?></strong>
                            <span class="badge" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeText; ?>; font-weight: 700; font-size: 0.68rem; padding: 0.1rem 0.45rem; border-radius: 9999px; flex-shrink: 0;">
                                <?php echo $label; ?>
                            </span>
                            <?php if(!$isRead): ?>
                                <span class="badge" style="background: #dbeafe; color: #1e40af; font-size: 0.64rem; font-weight: 700; flex-shrink: 0;">NEW</span>
                            <?php endif; ?>
                        </span>
                        <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 500; flex-shrink: 0;">
                            <i class="fa-regular fa-clock" style="font-size: 0.68rem;"></i> <?php echo date('M j, Y — g:i A', strtotime($a['created_at'])); ?>
                        </span>
                    </div>

                    <div style="font-size: 0.81rem; color: #475569; line-height: 1.35; white-space: pre-line; word-break: break-word; margin-top: 2px;">
                        <?php echo htmlspecialchars($a['message']); ?>
                    </div>
                </div>

                <div style="display: flex; gap: 0.35rem; align-items: center; flex-shrink: 0;">
                    <button class="btn-action" 
                            style="<?php echo $isRead ? 'background:#f1f5f9; color:#475569;' : 'background:#ecfdf5; color:#047857; border: 1px solid #a7f3d0;'; ?> width: 28px; height: 28px; border-radius: 5px; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;"
                            title="<?php echo $isRead ? 'Mark as Unread' : 'Mark as Read'; ?>" 
                            onclick="toggleRead(<?php echo $a['id']; ?>, <?php echo $isRead ? '0' : '1'; ?>)">
                        <i class="fa-solid <?php echo $isRead ? 'fa-envelope' : 'fa-check'; ?>"></i>
                    </button>
                    <button class="btn-action btn-delete" 
                            style="width: 28px; height: 28px; border-radius: 5px; font-size: 0.75rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;"
                            title="Delete" 
                            onclick="deleteAnnouncement(<?php echo $a['id']; ?>)">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination Controls -->
<div class="admin-pagination" id="annPagination" style="display:none; margin-top: 1.5rem;">
    <button class="adm-pg-btn adm-pg-prev" id="annPrev" disabled>
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <div class="adm-pg-numbers" id="annPageNums"></div>
    <button class="adm-pg-btn adm-pg-next" id="annNext">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
    <span class="adm-pg-info" id="annPgInfo"></span>
</div>

<style>
.filter-pill {
    background: #f1f5f9;
    color: var(--text-secondary);
    border: 1px solid var(--border);
    border-radius: 9999px;
    padding: 0.35rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s ease;
}
.filter-pill:hover {
    background: #e2e8f0;
    color: var(--text-primary);
}
.filter-pill.active {
    background: var(--pill-color, var(--accent-blue));
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 2px 5px rgba(0,0,0,0.12);
}
</style>

<script>
(function() {
    const ITEMS_PER_PAGE = 10;
    let currentPage = 1;
    let activeFilter = 'all';
    let searchQuery = '';
    let allCards = [];
    let visibleCards = [];

    const listContainer = document.getElementById('announcementsList');
    const pagination = document.getElementById('annPagination');
    const prevBtn = document.getElementById('annPrev');
    const nextBtn = document.getElementById('annNext');
    const pageNums = document.getElementById('annPageNums');
    const pgInfo = document.getElementById('annPgInfo');
    const searchInput = document.getElementById('annSearch');
    const filterPills = document.querySelectorAll('#annFilterPills .filter-pill');

    if (!listContainer) return;

    allCards = Array.from(listContainer.querySelectorAll('.announcement-card-item'));

    function applyFilters() {
        visibleCards = allCards.filter(card => {
            const isRead = card.getAttribute('data-read') === '1';
            const category = card.getAttribute('data-category');
            const text = card.textContent.toLowerCase();

            // Status/Type filter
            let matchesFilter = true;
            if (activeFilter === 'unread') matchesFilter = !isRead;
            else if (activeFilter === 'expiring') matchesFilter = (category === 'expiring');
            else if (activeFilter === 'expired') matchesFilter = (category === 'expired');
            else if (activeFilter === 'failed') matchesFilter = (category === 'failed');

            // Search filter
            let matchesSearch = true;
            if (searchQuery) {
                matchesSearch = text.includes(searchQuery);
            }

            return matchesFilter && matchesSearch;
        });

        currentPage = 1;
        renderPage();
    }

    function totalPages() {
        return Math.max(1, Math.ceil(visibleCards.length / ITEMS_PER_PAGE));
    }

    function renderPage() {
        allCards.forEach(c => c.style.display = 'none');

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end = Math.min(start + ITEMS_PER_PAGE, visibleCards.length);

        visibleCards.slice(start, end).forEach(card => card.style.display = 'flex');

        if (visibleCards.length <= ITEMS_PER_PAGE) {
            pagination.style.display = 'none';
        } else {
            pagination.style.display = 'flex';
            renderControls(start + 1, end);
        }
    }

    function renderControls(start, end) {
        const total = totalPages();
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === total;
        pgInfo.textContent = `${start}–${end} of ${visibleCards.length}`;

        pageNums.innerHTML = '';
        const range = getPageRange(currentPage, total);
        range.forEach(p => {
            if (p === '...') {
                const el = document.createElement('span');
                el.className = 'adm-pg-ellipsis';
                el.textContent = '…';
                pageNums.appendChild(el);
            } else {
                const btn = document.createElement('button');
                btn.className = 'adm-pg-btn adm-pg-num' + (p === currentPage ? ' active' : '');
                btn.textContent = p;
                btn.addEventListener('click', () => { currentPage = p; renderPage(); });
                pageNums.appendChild(btn);
            }
        });
    }

    function getPageRange(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages = [1];
        if (current > 3) pages.push('...');
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) pages.push(i);
        if (current < total - 2) pages.push('...');
        pages.push(total);
        return pages;
    }

    prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
    nextBtn.addEventListener('click', () => { if (currentPage < totalPages()) { currentPage++; renderPage(); } });

    // Filter pill click
    filterPills.forEach(pill => {
        pill.addEventListener('click', function() {
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });

    // Search input
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchQuery = this.value.trim().toLowerCase();
            applyFilters();
        });
    }

    // Initial render
    applyFilters();
})();

async function toggleRead(id, markAsRead) {
    const action = markAsRead ? 'read' : 'unread';
    try {
        const res = await fetch(`../api/mark_announcement_read.php?id=${id}&action=${action}`);
        const data = await res.json();
        if(data.success) {
            window.location.reload();
        }
    } catch(e) { console.error(e); }
}

async function deleteAnnouncement(id) {
    if (!confirm('Are you sure you want to delete this announcement?')) return;
    try {
        const res = await fetch(`../api/mark_announcement_read.php?id=${id}&action=delete`);
        const data = await res.json();
        if(data.success) {
            const el = document.getElementById(`annItem-${id}`);
            if (el) el.remove();
            window.location.reload();
        }
    } catch(e) { console.error(e); }
}

async function markAllRead() {
    try {
        const res = await fetch(`../api/mark_announcement_read.php?action=mark_all_read`);
        const data = await res.json();
        if(data.success) {
            window.location.reload();
        }
    } catch(e) { console.error(e); }
}

async function clearReadAnnouncements() {
    if (!confirm('Permanently delete all read announcements?')) return;
    try {
        const res = await fetch(`../api/mark_announcement_read.php?action=delete_read`);
        const data = await res.json();
        if(data.success) {
            window.location.reload();
        }
    } catch(e) { console.error(e); }
}

async function refreshSslCron() {
    const btn = document.getElementById('btnRefreshSsl');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;
    try {
        await fetch(`../api/cron_ssl_reminder.php`);
        window.location.reload();
    } catch(e) {
        alert('SSL check failed.');
        window.location.reload();
    }
}
</script>

<?php require_once '../includes/layout_footer.php'; ?>
