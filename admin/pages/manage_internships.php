<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../api/db.php';

$pageTitle = "Manage Internships";

// Fetch all non-deleted internships
$internships = [];
$stats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'closed' => 0,
    'expired' => 0
];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM internship_opportunities 
            WHERE deleted_at IS NULL 
            ORDER BY id DESC
        ");
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');
        foreach ($internships as $item) {
            $stats['total']++;
            $isExpired = (!empty($item['application_deadline']) && $item['application_deadline'] < $today);

            if ($item['status'] === 'Published') {
                if ($isExpired) {
                    $stats['expired']++;
                } else {
                    $stats['published']++;
                }
            } elseif ($item['status'] === 'Draft') {
                $stats['draft']++;
            } elseif ($item['status'] === 'Closed') {
                $stats['closed']++;
            }
        }
    } catch (PDOException $e) {
        $error = "Failed to load internships: " . $e->getMessage();
    }
} else {
    $error = "Database not connected.";
}

// Get unique categories for filter
$categories = array_filter(array_unique(array_map(function($i) { return trim($i['category'] ?? ''); }, $internships)));
sort($categories);

require_once '../includes/layout_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Internship Opportunities Management</h1>
        <p>Publish, edit, monitor, and manage verified internship positions.</p>
    </div>
    <div class="page-header-actions">
        <a href="add_internship.php" class="btn-header primary"><i class="fa-solid fa-plus"></i> Add Internship</a>
    </div>
</div>

<!-- Summary Statistics Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 1.5rem;">
    <div class="stat-card stat-blue">
        <div class="stat-card-icon"><i class="fa-solid fa-briefcase"></i></div>
        <div class="stat-card-value"><?php echo $stats['total']; ?></div>
        <div class="stat-card-label">Total Internships</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-card-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-card-value"><?php echo $stats['published']; ?></div>
        <div class="stat-card-label">Active Published</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-card-icon"><i class="fa-solid fa-file-pen"></i></div>
        <div class="stat-card-value"><?php echo $stats['draft']; ?></div>
        <div class="stat-card-label">Drafts</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #64748b;">
        <div class="stat-card-icon" style="color: #64748b;"><i class="fa-solid fa-lock"></i></div>
        <div class="stat-card-value"><?php echo $stats['closed']; ?></div>
        <div class="stat-card-label">Closed</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid #dc2626;">
        <div class="stat-card-icon" style="color: #dc2626;"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="stat-card-value"><?php echo $stats['expired']; ?></div>
        <div class="stat-card-label">Expired</div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h2 style="margin: 0; padding: 0; border: none;">
            <i class="fa-solid fa-list-check"></i> Manage Opportunities (<span id="internshipCountLabel"><?php echo count($internships); ?></span>)
        </h2>
    </div>

    <!-- Filter & Search Bar -->
    <div class="projects-filter-bar" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.25rem; background: #f8fafc; padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
        <div class="search-bar" style="margin: 0; flex: 1 1 220px; width: 100%;">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="internshipSearch" placeholder="Search title, company, category, location...">
        </div>
        
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <!-- Category Filter -->
            <select id="filterCategory" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Status Filter -->
            <select id="filterStatus" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Statuses</option>
                <option value="Published">Published (Active)</option>
                <option value="Draft">Draft</option>
                <option value="Closed">Closed</option>
                <option value="Expired">Expired</option>
            </select>

            <!-- Deadline Filter -->
            <select id="filterDeadline" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Deadlines</option>
                <option value="today">Expiring Today</option>
                <option value="urgent">Ending Soon (≤ 7 days)</option>
                <option value="open">Active (> 7 days)</option>
                <option value="expired">Expired</option>
            </select>

            <button type="button" id="btnResetFilters" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-secondary); font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; transition: all 0.2s ease;">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
        </div>
    </div>

    <?php if (empty($internships)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-briefcase"></i>
            <p>No internship opportunities created yet. Click "Add Internship" to publish your first opening.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="recent-table" id="internshipsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">Logo</th>
                        <th>Internship & Company</th>
                        <th>Category</th>
                        <th>Work Mode</th>
                        <th>Application Deadline</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="internshipTableBody">
                    <?php 
                    $today = date('Y-m-d');
                    foreach($internships as $item): 
                        $isExpired = (!empty($item['application_deadline']) && $item['application_deadline'] < $today);
                        
                        $daysLeft = 999;
                        $deadlineTag = 'open';
                        if (!empty($item['application_deadline'])) {
                            $daysLeft = (int)ceil((strtotime($item['application_deadline'] . ' 23:59:59') - time()) / 86400);
                            if ($daysLeft < 0) {
                                $deadlineTag = 'expired';
                            } elseif ($daysLeft === 0) {
                                $deadlineTag = 'today';
                            } elseif ($daysLeft <= 7) {
                                $deadlineTag = 'urgent';
                            } else {
                                $deadlineTag = 'open';
                            }
                        }

                        // Normalized status tag for filtering
                        $filterStatusVal = $item['status'];
                        if ($item['status'] === 'Published' && $isExpired) {
                            $filterStatusVal = 'Expired';
                        }

                        $logoPath = $item['company_logo'] ?? '';
                        $logoSrc = empty($logoPath)
                            ? ''
                            : (preg_match('#^https?://#i', $logoPath) ? $logoPath : '../../' . ltrim($logoPath, '/'));
                    ?>
                    <tr class="internship-row" 
                        data-status="<?php echo htmlspecialchars($filterStatusVal); ?>"
                        data-category="<?php echo htmlspecialchars($item['category'] ?? ''); ?>"
                        data-days-left="<?php echo $daysLeft; ?>"
                        data-deadline-tag="<?php echo $deadlineTag; ?>"
                        data-title="<?php echo htmlspecialchars(strtolower($item['internship_title'])); ?>"
                        data-company="<?php echo htmlspecialchars(strtolower($item['company_name'])); ?>"
                        data-location="<?php echo htmlspecialchars(strtolower($item['location'] . ' ' . $item['company_location'])); ?>">
                        
                        <td>
                            <div class="internship-table-logo">
                                <?php if (!empty($logoSrc)): ?>
                                    <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\'fa-solid fa-building\' style=\'color: #94a3b8;\'></i>';">
                                <?php else: ?>
                                    <i class="fa-solid fa-building" style="color: #94a3b8;"></i>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td>
                            <div class="project-title-cell">
                                <a href="edit_internship.php?id=<?php echo $item['id']; ?>" style="color: inherit; font-weight: 700;">
                                    <?php echo htmlspecialchars($item['internship_title']); ?>
                                </a>
                            </div>
                            <div class="project-title-meta">
                                <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($item['company_name']); ?>
                                <?php if (!empty($item['company_location'])): ?>
                                    &bull; <?php echo htmlspecialchars($item['company_location']); ?>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td>
                            <span class="project-meta-badge badge-category">
                                <?php echo htmlspecialchars($item['category']); ?>
                            </span>
                        </td>

                        <td>
                            <span class="meta-work-mode">
                                <?php echo htmlspecialchars($item['work_mode']); ?>
                            </span>
                        </td>

                        <td>
                            <?php 
                            $dl = $item['application_deadline'];
                            $dlDisplay = !empty($dl) ? date('M d, Y', strtotime($dl)) : 'None';
                            ?>
                            <div style="font-weight: 600; font-size: 0.82rem; color: var(--text-primary);">
                                <i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($dlDisplay); ?>
                            </div>
                            <?php if ($daysLeft < 0): ?>
                                <div class="admin-deadline-badge badge-expired"><i class="fa-solid fa-circle-exclamation"></i> Expired</div>
                            <?php elseif ($daysLeft === 0): ?>
                                <div class="admin-deadline-badge badge-urgent"><i class="fa-solid fa-hourglass-end"></i> Expires Today</div>
                            <?php elseif ($daysLeft <= 3): ?>
                                <div class="admin-deadline-badge badge-urgent"><i class="fa-solid fa-hourglass-half"></i> Ending Soon (<?php echo $daysLeft; ?>d left)</div>
                            <?php elseif ($daysLeft <= 7): ?>
                                <div class="admin-deadline-badge badge-warning"><i class="fa-solid fa-clock"></i> Closing Soon (<?php echo $daysLeft; ?>d left)</div>
                            <?php else: ?>
                                <div class="admin-deadline-badge badge-open"><i class="fa-regular fa-calendar-check"></i> <?php echo $daysLeft; ?>d left</div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($item['status'] === 'Published'): ?>
                                <?php if ($isExpired): ?>
                                    <span class="badge badge-expired">Expired</span>
                                <?php else: ?>
                                    <span class="badge badge-published">Published</span>
                                <?php endif; ?>
                            <?php elseif ($item['status'] === 'Draft'): ?>
                                <span class="badge badge-draft">Draft</span>
                            <?php elseif ($item['status'] === 'Closed'): ?>
                                <span class="badge badge-closed">Closed</span>
                            <?php endif; ?>
                        </td>

                        <td style="font-size: 0.78rem; color: #94a3b8;">
                            <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                        </td>

                        <td style="text-align: right;">
                            <div class="project-actions" style="justify-content: flex-end;">
                                <?php if ($item['status'] === 'Published' && !$isExpired): ?>
                                    <!-- View on site -->
                                    <a href="../../public/internship-details.html?id=<?php echo $item['id']; ?>" target="_blank" class="btn-action btn-preview" title="View Public Page">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                    <!-- Quick Close Button -->
                                    <button type="button" class="btn-action" style="background: rgba(100,116,139,0.1); color: #64748b;" onclick="toggleStatus(<?php echo $item['id']; ?>, 'close')" title="Close Opportunity">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                <?php elseif ($item['status'] === 'Closed' || $isExpired): ?>
                                    <!-- Quick Reopen Button -->
                                    <button type="button" class="btn-action" style="background: rgba(16,185,129,0.1); color: #059669;" onclick="toggleStatus(<?php echo $item['id']; ?>, 'reopen')" title="Reopen / Publish Opportunity">
                                        <i class="fa-solid fa-lock-open"></i>
                                    </button>
                                <?php elseif ($item['status'] === 'Draft'): ?>
                                    <!-- Quick Publish Button -->
                                    <button type="button" class="btn-action" style="background: rgba(16,185,129,0.1); color: #059669;" onclick="toggleStatus(<?php echo $item['id']; ?>, 'publish')" title="Publish Opportunity">
                                        <i class="fa-solid fa-globe"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Edit -->
                                <a href="edit_internship.php?id=<?php echo $item['id']; ?>" class="btn-action btn-edit" title="Edit Opportunity">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <!-- Delete -->
                                <a href="../api/delete_internship.php?id=<?php echo $item['id']; ?>" class="btn-action btn-delete" title="Delete Opportunity">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('internshipSearch');
    const filterCat = document.getElementById('filterCategory');
    const filterSt = document.getElementById('filterStatus');
    const filterDl = document.getElementById('filterDeadline');
    const btnReset = document.getElementById('btnResetFilters');
    const rows = document.querySelectorAll('.internship-row');
    const countLabel = document.getElementById('internshipCountLabel');

    function applyFilters() {
        const q = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const cat = (filterCat ? filterCat.value : '').trim();
        const st = (filterSt ? filterSt.value : '').trim();
        const dl = (filterDl ? filterDl.value : '').trim();

        let visibleCount = 0;
        rows.forEach(r => {
            const rowTitle = r.getAttribute('data-title') || '';
            const rowCompany = r.getAttribute('data-company') || '';
            const rowLoc = r.getAttribute('data-location') || '';
            const rowCat = r.getAttribute('data-category') || '';
            const rowSt = r.getAttribute('data-status') || '';
            const rowDays = parseInt(r.getAttribute('data-days-left') || '999', 10);

            const matchesSearch = !q || (rowTitle.includes(q) || rowCompany.includes(q) || rowLoc.includes(q) || rowCat.toLowerCase().includes(q));
            const matchesCat = !cat || rowCat === cat;
            const matchesSt = !st || rowSt === st;

            let matchesDl = true;
            if (dl === 'today') matchesDl = (rowDays === 0);
            else if (dl === 'urgent') matchesDl = (rowDays >= 0 && rowDays <= 7);
            else if (dl === 'open') matchesDl = (rowDays > 7);
            else if (dl === 'expired') matchesDl = (rowDays < 0);

            if (matchesSearch && matchesCat && matchesSt && matchesDl) {
                r.style.display = '';
                visibleCount++;
            } else {
                r.style.display = 'none';
            }
        });

        if (countLabel) countLabel.textContent = visibleCount;
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (filterCat) filterCat.addEventListener('change', applyFilters);
    if (filterSt) filterSt.addEventListener('change', applyFilters);
    if (filterDl) filterDl.addEventListener('change', applyFilters);
    if (btnReset) {
        btnReset.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (filterCat) filterCat.value = '';
            if (filterSt) filterSt.value = '';
            if (filterDl) filterDl.value = '';
            applyFilters();
        });
    }

    // Check URL parameters for alerts
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');

    if (successMsg) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMsg,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (errorMsg) {
        Swal.fire({
            icon: 'error',
            title: 'Notice',
            text: errorMsg,
            confirmButtonColor: '#0b5394'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function toggleStatus(id, action) {
    const actionLabel = action.charAt(0).toUpperCase() + action.slice(1);
    Swal.fire({
        title: `${actionLabel} Opportunity?`,
        text: `Are you sure you want to ${action} this internship opportunity?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0b5394',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Yes, ${action} it`
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `../api/update_internship_status.php?id=${id}&action=${action}`;
        }
    });
}
</script>

<?php require_once '../includes/layout_footer.php'; ?>
