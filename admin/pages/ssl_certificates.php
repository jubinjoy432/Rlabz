<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../api/db.php';
$pageTitle = 'SSL Certificates';
require_once '../includes/layout_header.php';

$certs = [];
$totalCount = 0;
$validCount = 0;
$expiringCount = 0;
$expiredCount = 0;
$failedCount = 0;
$providers = [];

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
    $totalCount = count($certs);

    foreach ($certs as $c) {
        $st = trim($c['status'] ?? '');
        $err = trim($c['last_error'] ?? '');
        $exp = $c['expiry_date'];
        $days = $c['days_left'];
        $prov = trim($c['provider'] ?? '');
        if ($prov && $prov !== 'N/A' && !in_array($prov, $providers)) {
            $providers[] = $prov;
        }

        if ($st === 'Check Failed' || !empty($err) || empty($exp)) {
            $failedCount++;
        } elseif ($days !== null && $days <= 0) {
            $expiredCount++;
        } elseif ($days !== null && $days <= 30) {
            $expiringCount++;
        } else {
            $validCount++;
        }
    }
    sort($providers);
} catch (PDOException $e) {
    $error = "Failed to load SSL Certificates: " . $e->getMessage();
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>SSL Certificates</h1>
        <p>Monitor, search, filter, and track website domain certificates.</p>
    </div>
    <div class="page-header-right">
        <button id="btnFetchAllSsl" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #0b5394; color: #ffffff; border: 1px solid rgba(255,255,255,0.15); border-radius: 7px; padding: 0.45rem 0.95rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" onclick="fetchAllSsl()">
            <i class="fa-solid fa-sync"></i> Refresh SSL Status
        </button>
    </div>
</div>

<!-- SSL Status Stats Counters -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 1.5rem;">
    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.12); color: #2563eb;">
            <i class="fa-solid fa-globe"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $totalCount; ?></div>
            <div class="stat-label">Total Monitored</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #10b981;">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $validCount; ?></div>
            <div class="stat-label">Valid (&gt; 30 Days)</div>
        </div>
    </div>

    <!-- MAIN PURPOSE: Expiring SSL -->
    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $expiringCount; ?></div>
            <div class="stat-label">Expiring Soon (&le; 30 Days)</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #ef4444;">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $expiredCount; ?></div>
            <div class="stat-label">Expired (&le; 0 Days)</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #e11d48;">
        <div class="stat-icon" style="background: rgba(225, 29, 72, 0.12); color: #be123c;">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $failedCount; ?></div>
            <div class="stat-label">Not Able to Fetch</div>
        </div>
    </div>
</div>

<div class="card">
    <!-- Filter and Search Toolbar -->
    <div style="background: #f8fafc; padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
        
        <!-- Search Row with Search Button and Reset Button -->
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <div class="search-bar" style="margin: 0; min-width: 250px; flex: 1;">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="sslSearchInput" placeholder="Search project title, domain URL, provider...">
            </div>

            <button type="button" id="btnSslSearch" style="padding: 0.45rem 1rem; background: var(--accent-blue); color: #ffffff; border: 1px solid var(--accent-blue); border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s ease;">
                <i class="fa-solid fa-search"></i> Search
            </button>

            <?php if(!empty($providers)): ?>
            <select id="filterSslProvider" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Providers</option>
                <?php foreach($providers as $p): ?>
                    <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <button type="button" id="btnSslReset" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-secondary); font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; transition: all 0.2s ease;">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
        </div>

        <!-- Quick Filter Buttons / Pills -->
        <div class="filter-pills" id="sslFilterPills" style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
            <button class="filter-pill active" data-filter="all">All (<?php echo $totalCount; ?>)</button>
            <button class="filter-pill" data-filter="valid" style="--pill-color: #059669;">Valid (<?php echo $validCount; ?>)</button>
            <button class="filter-pill" data-filter="expiring" style="--pill-color: #d97706;">Expiring Soon (<?php echo $expiringCount; ?>)</button>
            <button class="filter-pill" data-filter="expired" style="--pill-color: #dc2626;">Expired (<?php echo $expiredCount; ?>)</button>
            <button class="filter-pill" data-filter="failed" style="--pill-color: #e11d48;">Not Able to Fetch (<?php echo $failedCount; ?>)</button>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="recent-table" id="sslTable">
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
                <tbody id="sslTableBody">
                    <?php if(empty($certs)): ?>
                        <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding: 2.5rem;">No website links or SSL certificates tracked. Enable SSL Fetch on projects to track.</td></tr>
                    <?php else: ?>
                        <?php foreach($certs as $cert): ?>
                            <?php 
                            $domainUrl = trim($cert['domain_url'] ?? '');
                            $daysLeft = $cert['days_left'];
                            $status = trim($cert['status'] ?? '');
                            $lastError = trim($cert['last_error'] ?? '');
                            $expiryDate = $cert['expiry_date'];
                            $provider = trim($cert['provider'] ?? '');

                            // Categorize for filtering
                            $isFetchFailed = ($status === 'Check Failed' || !empty($lastError) || empty($expiryDate));
                            $statusCategory = 'valid';

                            if ($isFetchFailed) {
                                $statusCategory = 'failed';
                                $statusBadge = '<span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.35); font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-circle-xmark"></i> Not able to fetch</span>';
                                if (!empty($lastError)) {
                                    $statusBadge .= '<div style="font-size: 0.72rem; color: #dc2626; margin-top: 3px; max-width: 220px; line-height: 1.25;" title="' . htmlspecialchars($lastError) . '">' . htmlspecialchars(mb_strimwidth($lastError, 0, 48, '...')) . '</div>';
                                }
                            } else {
                                if ($daysLeft !== null && $daysLeft <= 0) {
                                    $statusCategory = 'expired';
                                    $statusBadge = '<span class="badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-triangle-exclamation"></i> Expired</span>';
                                } elseif ($daysLeft !== null && $daysLeft <= 30) {
                                    $statusCategory = 'expiring';
                                    $statusBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-clock"></i> Expiring Soon (' . $daysLeft . ' days)</span>';
                                } else {
                                    $statusCategory = 'valid';
                                    $statusBadge = '<span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fa-solid fa-circle-check"></i> ' . ($daysLeft !== null ? $daysLeft . ' days left' : 'Valid') . '</span>';
                                }
                            }

                            $searchContent = strtolower($cert['title'] . ' ' . $domainUrl . ' ' . $provider . ' ' . $statusCategory);
                            ?>
                        <tr class="ssl-row" 
                            data-status="<?php echo $statusCategory; ?>"
                            data-provider="<?php echo htmlspecialchars($provider); ?>"
                            data-search="<?php echo htmlspecialchars($searchContent); ?>">
                            <td><strong><?php echo htmlspecialchars($cert['title']); ?></strong></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($domainUrl); ?>" target="_blank" rel="noopener" style="color: var(--accent-blue); font-weight: 500; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <?php echo htmlspecialchars($domainUrl); ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars((!$isFetchFailed && $provider) ? $provider : 'N/A'); ?></td>
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

        <div id="sslNoResults" style="display: none; text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
            <i class="fa-solid fa-filter-circle-xmark" style="font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem; display: block;"></i>
            No certificates match the selected filters or search terms.
        </div>
    <?php endif; ?>
</div>

<!-- SSL Pagination Controls -->
<div class="admin-pagination" id="sslPagination" style="display:none; margin-top: 1.5rem;">
    <button class="adm-pg-btn adm-pg-prev" id="sslPrev" disabled>
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <div class="adm-pg-numbers" id="sslPageNums"></div>
    <button class="adm-pg-btn adm-pg-next" id="sslNext">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
    <span class="adm-pg-info" id="sslPgInfo"></span>
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
    let selectedProvider = '';
    let allRows = [];
    let visibleRows = [];

    const tableBody = document.getElementById('sslTableBody');
    const pagination = document.getElementById('sslPagination');
    const prevBtn = document.getElementById('sslPrev');
    const nextBtn = document.getElementById('sslNext');
    const pageNums = document.getElementById('sslPageNums');
    const pgInfo = document.getElementById('sslPgInfo');
    const searchInput = document.getElementById('sslSearchInput');
    const btnSearch = document.getElementById('btnSslSearch');
    const btnReset = document.getElementById('btnSslReset');
    const filterPills = document.querySelectorAll('#sslFilterPills .filter-pill');
    const providerSelect = document.getElementById('filterSslProvider');
    const noResults = document.getElementById('sslNoResults');

    if (!tableBody) return;

    allRows = Array.from(tableBody.querySelectorAll('.ssl-row'));

    function applyFilters() {
        visibleRows = allRows.filter(row => {
            const status = row.getAttribute('data-status');
            const provider = row.getAttribute('data-provider') || '';
            const searchData = row.getAttribute('data-search') || '';

            let matchStatus = (activeFilter === 'all') || (status === activeFilter);
            let matchProvider = !selectedProvider || (provider === selectedProvider);
            let matchSearch = !searchQuery || searchData.includes(searchQuery);

            return matchStatus && matchProvider && matchSearch;
        });

        if (noResults) {
            noResults.style.display = (visibleRows.length === 0 && allRows.length > 0) ? 'block' : 'none';
        }

        currentPage = 1;
        renderPage();
    }

    function totalPages() {
        return Math.max(1, Math.ceil(visibleRows.length / ITEMS_PER_PAGE));
    }

    function renderPage() {
        allRows.forEach(r => r.style.display = 'none');

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end = Math.min(start + ITEMS_PER_PAGE, visibleRows.length);

        visibleRows.slice(start, end).forEach(r => r.style.display = '');

        if (visibleRows.length <= ITEMS_PER_PAGE) {
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
        pgInfo.textContent = `${start}–${end} of ${visibleRows.length}`;

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

    // Filter Pills
    filterPills.forEach(pill => {
        pill.addEventListener('click', function() {
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });

    // Provider dropdown
    if (providerSelect) {
        providerSelect.addEventListener('change', function() {
            selectedProvider = this.value;
            applyFilters();
        });
    }

    // Search events
    function triggerSearch() {
        if (searchInput) searchQuery = searchInput.value.trim().toLowerCase();
        applyFilters();
    }

    if (btnSearch) btnSearch.addEventListener('click', triggerSearch);
    if (searchInput) {
        searchInput.addEventListener('input', triggerSearch);
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerSearch();
            }
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (providerSelect) providerSelect.value = '';
            searchQuery = '';
            selectedProvider = '';
            activeFilter = 'all';
            filterPills.forEach(p => p.classList.remove('active'));
            if (filterPills[0]) filterPills[0].classList.add('active');
            applyFilters();
        });
    }

    // Initial render
    applyFilters();
})();

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
    }, 2000);
});
</script>

<?php require_once '../includes/layout_footer.php'; ?>
