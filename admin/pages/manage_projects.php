<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../api/db.php';

$pageTitle = "Manage Projects";

// Fetch existing projects
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY year DESC, id DESC");
        $projects = $stmt->fetchAll();
    } catch (PDOException $e) {
        $projects = [];
        $error = "Failed to load projects: " . $e->getMessage();
    }
} else {
    $projects = [];
    $error = "Database not connected.";
}

require_once '../includes/layout_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Manage Projects</h1>
        <p>View, edit, and delete existing projects.</p>
    </div>
    <div>
        <a href="add_project.php" class="btn-header primary"><i class="fa-solid fa-plus"></i> New Project</a>
    </div>
</div>

<?php
$categories = array_filter(array_unique(array_map(function($p) { return trim($p['category'] ?? ''); }, $projects)));
sort($categories);
$departments = array_filter(array_unique(array_map(function($p) { return trim($p['department'] ?? ''); }, $projects)));
sort($departments);
$statuses = ['Deployed', 'Completed', 'In Development'];
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h2 style="margin: 0; padding: 0; border: none;"><i class="fa-solid fa-folder-open"></i> Existing Projects (<span id="projectCountLabel"><?php echo count($projects); ?></span>)</h2>
    </div>

    <!-- Filter Bar -->
    <div class="projects-filter-bar" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.25rem; background: #f8fafc; padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
        <div class="search-bar" style="margin: 0; min-width: 240px; flex: 1;">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="projectSearch" placeholder="Search title, tech, description...">
        </div>
        
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <select id="filterCategory" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="filterDepartment" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Departments</option>
                <?php foreach($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="filterStatus" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-primary); font-size: 0.85rem; font-weight: 500; cursor: pointer;">
                <option value="">All Statuses</option>
                <?php foreach($statuses as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="button" id="btnResetFilters" style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff; color: var(--text-secondary); font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; transition: all 0.2s ease;">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
        </div>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Action completed successfully!</div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(empty($projects)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>No projects found. Add one to get started!</p>
        </div>
    <?php else: ?>
        <ul class="project-list" id="projectList">
            <?php foreach($projects as $p): ?>
            <li class="project-item" 
                data-status="<?php echo htmlspecialchars($p['status'] ?? ''); ?>"
                data-category="<?php echo htmlspecialchars($p['category'] ?? ''); ?>"
                data-department="<?php echo htmlspecialchars($p['department'] ?? ''); ?>"
                data-year="<?php echo htmlspecialchars($p['year'] ?? ''); ?>">
                <img src="../../<?php echo htmlspecialchars($p['image_path']); ?>" alt="Project image" class="project-img" onerror="this.src='../../assets/images/logo1.png'">
                <div class="project-info">
                    <h3 class="project-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($p['description'], 0, 100)); ?>...</p>
                    <div class="project-meta-badges">
                        <span class="badge badge-default"><?php echo htmlspecialchars($p['year']); ?></span>
                        <?php if(!empty($p['status'])): ?>
                            <?php
                                $statusClass = 'badge-default';
                                if($p['status'] == 'Deployed') $statusClass = 'badge-deployed';
                                if($p['status'] == 'Completed') $statusClass = 'badge-completed';
                                if($p['status'] == 'In Development') $statusClass = 'badge-dev';
                            ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                        <?php endif; ?>
                        <?php if(!empty($p['category'])): ?>
                        <span class="badge badge-default" style="background: rgba(139,92,246,0.12); color: #7c3aed;"><?php echo htmlspecialchars($p['category']); ?></span>
                        <?php endif; ?>
                        <?php if(!empty($p['department'])): ?>
                        <span class="badge badge-default" style="background: rgba(14,165,233,0.12); color: #0284c7;"><?php echo htmlspecialchars($p['department']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="project-actions">
                    <?php if(!empty($p['slug'])): ?>
                    <a href="../../public/project-details.html?id=<?php echo htmlspecialchars($p['slug']); ?>" class="btn-action btn-preview" target="_blank" title="Preview"><i class="fas fa-eye"></i></a>
                    <?php endif; ?>
                    <a href="../edit_project.php?id=<?php echo $p['id']; ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                    <a href="../api/delete_project.php?id=<?php echo $p['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this project?');" title="Delete"><i class="fas fa-trash"></i></a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Pagination Controls (client-side) -->
<div class="admin-pagination" id="adminPagination" style="display:none;">
    <button class="adm-pg-btn adm-pg-prev" id="admPrev" disabled>
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <div class="adm-pg-numbers" id="admPageNums"></div>
    <button class="adm-pg-btn adm-pg-next" id="admNext">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
    <span class="adm-pg-info" id="admPgInfo"></span>
</div>

<script>
(function () {
    const ITEMS_PER_PAGE = 10;
    let currentPage = 1;
    let allItems = [];
    let filteredItems = [];

    const searchInput      = document.getElementById('projectSearch');
    const filterCategory   = document.getElementById('filterCategory');
    const filterDepartment = document.getElementById('filterDepartment');
    const filterStatus     = document.getElementById('filterStatus');
    const btnResetFilters  = document.getElementById('btnResetFilters');
    const countLabel       = document.getElementById('projectCountLabel');

    const projectList  = document.getElementById('projectList');
    const pagination   = document.getElementById('adminPagination');
    const admPrev      = document.getElementById('admPrev');
    const admNext      = document.getElementById('admNext');
    const admPageNums  = document.getElementById('admPageNums');
    const admPgInfo    = document.getElementById('admPgInfo');

    if (!projectList) return;

    allItems     = Array.from(projectList.querySelectorAll('.project-item'));
    filteredItems = allItems.slice();

    function applyFilters() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const selectedCat = filterCategory ? filterCategory.value.trim() : '';
        const selectedDept = filterDepartment ? filterDepartment.value.trim() : '';
        const selectedStatus = filterStatus ? filterStatus.value.trim() : '';

        filteredItems = allItems.filter(item => {
            const itemText = item.textContent.toLowerCase();
            const itemCat  = item.getAttribute('data-category') || '';
            const itemDept = item.getAttribute('data-department') || '';
            const itemStat = item.getAttribute('data-status') || '';

            const matchQuery = !query || itemText.includes(query);
            const matchCat   = !selectedCat || (itemCat === selectedCat);
            const matchDept  = !selectedDept || (itemDept === selectedDept);
            const matchStat  = !selectedStatus || (itemStat === selectedStatus);

            return matchQuery && matchCat && matchDept && matchStat;
        });

        if (countLabel) {
            countLabel.textContent = filteredItems.length;
        }

        currentPage = 1;
        renderPage();
    }

    function totalPages() {
        return Math.max(1, Math.ceil(filteredItems.length / ITEMS_PER_PAGE));
    }

    function renderPage() {
        allItems.forEach(item => { item.style.display = 'none'; });
        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end   = Math.min(start + ITEMS_PER_PAGE, filteredItems.length);
        filteredItems.slice(start, end).forEach(item => { item.style.display = 'flex'; });

        if (filteredItems.length <= ITEMS_PER_PAGE) {
            pagination.style.display = 'none';
        } else {
            pagination.style.display = 'flex';
            renderPaginationControls(start + 1, end);
        }
    }

    function renderPaginationControls(start, end) {
        const total = totalPages();
        admPrev.disabled = currentPage === 1;
        admNext.disabled = currentPage === total;
        admPgInfo.textContent = `${start}–${end} of ${filteredItems.length}`;

        admPageNums.innerHTML = '';
        getPageRange(currentPage, total).forEach(p => {
            if (p === '...') {
                const el = document.createElement('span');
                el.className = 'adm-pg-ellipsis';
                el.textContent = '…';
                admPageNums.appendChild(el);
            } else {
                const btn = document.createElement('button');
                btn.className = 'adm-pg-btn adm-pg-num' + (p === currentPage ? ' active' : '');
                btn.textContent = p;
                btn.addEventListener('click', () => goToPage(p));
                admPageNums.appendChild(btn);
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

    function goToPage(page) {
        const total = totalPages();
        if (page < 1 || page > total) return;
        currentPage = page;
        renderPage();
    }

    admPrev.addEventListener('click', () => goToPage(currentPage - 1));
    admNext.addEventListener('click', () => goToPage(currentPage + 1));

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (filterCategory) filterCategory.addEventListener('change', applyFilters);
    if (filterDepartment) filterDepartment.addEventListener('change', applyFilters);
    if (filterStatus) filterStatus.addEventListener('change', applyFilters);

    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterCategory) filterCategory.value = '';
            if (filterDepartment) filterDepartment.value = '';
            if (filterStatus) filterStatus.value = '';
            applyFilters();
        });
    }

    // Initial render
    applyFilters();
})();
</script>

<?php require_once '../includes/layout_footer.php'; ?>
