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

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <h2 style="margin: 0; padding: 0; border: none;"><i class="fa-solid fa-folder-open"></i> Existing Projects (<?php echo count($projects); ?>)</h2>
        <div class="search-bar" style="margin: 0; width: 300px;">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="projectSearch" placeholder="Search projects...">
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
            <li class="project-item">
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
                        <span class="badge badge-default" style="background: rgba(139,92,246,0.12); color: #a78bfa;"><?php echo htmlspecialchars($p['category']); ?></span>
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

    const searchInput  = document.getElementById('projectSearch');
    const projectList  = document.getElementById('projectList');
    const pagination   = document.getElementById('adminPagination');
    const admPrev      = document.getElementById('admPrev');
    const admNext      = document.getElementById('admNext');
    const admPageNums  = document.getElementById('admPageNums');
    const admPgInfo    = document.getElementById('admPgInfo');

    if (!projectList) return;

    allItems     = Array.from(projectList.querySelectorAll('.project-item'));
    filteredItems = allItems.slice();

    function totalPages() {
        return Math.max(1, Math.ceil(filteredItems.length / ITEMS_PER_PAGE));
    }

    function renderPage() {
        // Hide all then show only the current page slice
        allItems.forEach(item => { item.style.display = 'none'; });
        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end   = Math.min(start + ITEMS_PER_PAGE, filteredItems.length);
        filteredItems.slice(start, end).forEach(item => { item.style.display = 'flex'; });

        // Show / hide pagination
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

    // Search — refilter and reset to page 1
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            filteredItems = term
                ? allItems.filter(item => item.textContent.toLowerCase().includes(term))
                : allItems.slice();
            currentPage = 1;
            renderPage();
        });
    }

    // Initial render
    renderPage();
})();
</script>

<?php require_once '../includes/layout_footer.php'; ?>
