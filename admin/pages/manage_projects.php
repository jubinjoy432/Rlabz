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
                <img src="../../<?php echo htmlspecialchars($p['image_path']); ?>" alt="Project image" class="project-img" onerror="this.src='../../assets/images/rz-logo.webp'">
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

<script>
    // Simple client-side search filter
    const searchInput = document.getElementById('projectSearch');
    const projectList = document.getElementById('projectList');
    if (searchInput && projectList) {
        const items = projectList.querySelectorAll('.project-item');
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            items.forEach(item => {
                const title = item.querySelector('.project-title').textContent.toLowerCase();
                if (title.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
</script>

<?php require_once '../includes/layout_footer.php'; ?>
