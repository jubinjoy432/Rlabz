<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'api/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Project ID is required.");
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die("Project not found.");
}

$stmtMembers = $pdo->prepare("SELECT * FROM project_members WHERE project_id = ? ORDER BY id");
$stmtMembers->execute([$id]);
$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Edit Project";
require_once 'includes/layout_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Project</h1>
        <p>Update details for <?= htmlspecialchars($project['title']) ?></p>
    </div>
</div>

<div class="card">
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Project updated successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> Error: <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="api/edit_project_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $project['id'] ?>">
        
        <div class="form-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="form-group">
            <label>URL Slug</label>
            <input type="text" name="slug" required value="<?= htmlspecialchars($project['slug']) ?>">
        </div>
        <div class="form-group">
            <label>Project Title</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($project['title']) ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Academic Year</label>
                <input type="text" name="year" required value="<?= htmlspecialchars($project['year']) ?>">
            </div>
            <div class="form-group">
                <label>Batch</label>
                <input type="text" name="batch" value="<?= htmlspecialchars($project['batch']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Short Description</label>
            <input type="text" name="short_description" value="<?= htmlspecialchars($project['short_description']) ?>" maxlength="300">
        </div>
        <div class="form-group">
            <label>Full Description</label>
            <textarea name="description" required><?= htmlspecialchars($project['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Objectives</label>
            <textarea name="objectives"><?= htmlspecialchars($project['objectives']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Problem Statement</label>
            <textarea name="problem_statement"><?= htmlspecialchars($project['problem_statement']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Expected Outcome</label>
            <textarea name="expected_outcome"><?= htmlspecialchars($project['expected_outcome']) ?></textarea>
        </div>

        <div class="form-section-title"><i class="fa-solid fa-tags"></i> Classification</div>
        <div class="form-row">
            <div class="form-group">
                <label>Department</label>
                <select name="department">
                    <option value="MCA" <?= $project['department']=='MCA'?'selected':'' ?>>MCA</option>
                    <option value="BCA" <?= $project['department']=='BCA'?'selected':'' ?>>BCA</option>
                    <option value="Computer Science" <?= $project['department']=='Computer Science'?'selected':'' ?>>Computer Science</option>
                    <option value="Other" <?= $project['department']=='Other'?'selected':'' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="Web Application" <?= $project['category']=='Web Application'?'selected':'' ?>>Web Application</option>
                    <option value="Mobile Application" <?= $project['category']=='Mobile Application'?'selected':'' ?>>Mobile Application</option>
                    <option value="Desktop Application" <?= $project['category']=='Desktop Application'?'selected':'' ?>>Desktop Application</option>
                    <option value="Platform" <?= $project['category']=='Platform'?'selected':'' ?>>Platform</option>
                    <option value="Other" <?= $project['category']=='Other'?'selected':'' ?>>Other</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Project Type</label>
                <select name="project_type">
                    <option value="Academic" <?= $project['project_type']=='Academic'?'selected':'' ?>>Academic</option>
                    <option value="Client" <?= $project['project_type']=='Client'?'selected':'' ?>>Client</option>
                    <option value="Internal" <?= $project['project_type']=='Internal'?'selected':'' ?>>Internal</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Completed" <?= $project['status']=='Completed'?'selected':'' ?>>Completed</option>
                    <option value="Deployed" <?= $project['status']=='Deployed'?'selected':'' ?>>Deployed</option>
                    <option value="In Development" <?= $project['status']=='In Development'?'selected':'' ?>>In Development</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Duration</label>
            <input type="text" name="duration" value="<?= htmlspecialchars($project['duration']) ?>">
        </div>

        <div class="form-section-title"><i class="fa-solid fa-microchip"></i> Technologies (Comma separated)</div>
        <div class="form-group">
            <input type="text" name="tech_stack" value="<?= htmlspecialchars($project['tech_stack']) ?>" placeholder="e.g. PHP, HTML, CSS">
        </div>

        <div class="form-section-title"><i class="fa-solid fa-list-check"></i> Key Features (Comma separated)</div>
        <div class="form-group">
            <input type="text" name="key_features" value="<?= htmlspecialchars($project['key_features']) ?>" placeholder="e.g. Authentication, Dashboard">
        </div>

        <div class="form-section-title"><i class="fa-solid fa-user-tie"></i> Faculty Information</div>
        <div class="form-row">
            <div class="form-group">
                <label>Faculty Name</label>
                <input type="text" name="faculty_name" value="<?= htmlspecialchars($project['faculty_name']) ?>">
            </div>
            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="faculty_designation" value="<?= htmlspecialchars($project['faculty_designation']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Faculty Photo (Upload new to replace)</label>
            <input type="file" name="faculty_photo" accept="image/*">
        </div>

        <div class="form-section-title"><i class="fa-solid fa-users"></i> Team Members (Comma separated names)</div>
        <div class="form-group">
            <?php 
                $member_names = array_map(function($m) { return trim($m['name']); }, $members);
                $team_str = implode(", ", $member_names);
            ?>
            <input type="text" name="team_members_str" value="<?= htmlspecialchars($team_str) ?>" placeholder="John Doe, Jane Smith">
            <span class="form-help">For simplicity in editing, please list names separated by commas.</span>
        </div>

        <div class="form-section-title"><i class="fa-solid fa-images"></i> Images</div>
        <div class="form-group">
            <label>Project Cover Image (Upload new to replace)</label>
            <input type="file" name="image" accept="image/*">
            <?php if($project['image_path']): ?>
                <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 5px;">Current: <?= htmlspecialchars($project['image_path']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Project Thumbnail (Upload new to replace)</label>
            <input type="file" name="thumbnail" accept="image/*">
            <?php if($project['thumbnail_path']): ?>
                <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 5px;">Current: <?= htmlspecialchars($project['thumbnail_path']) ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Project Poster (Upload new to replace)</label>
            <input type="file" name="poster" accept="image/*">
            <?php if($project['poster_path']): ?>
                <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 5px;">Current: <?= htmlspecialchars($project['poster_path']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-section-title"><i class="fa-solid fa-link"></i> Links</div>
        <div class="form-row">
            <div class="form-group">
                <label>GitHub Link</label>
                <input type="text" name="github_link" value="<?= htmlspecialchars($project['github_link']) ?>">
            </div>
            <div class="form-group">
                <label>Demo Link</label>
                <input type="text" name="demo_link" value="<?= htmlspecialchars($project['demo_link']) ?>">
            </div>
        </div>

        <button type="submit" class="btn-submit full-width"><i class="fa-solid fa-save"></i> Save Changes</button>
    </form>
</div>

<?php require_once 'includes/layout_footer.php'; ?>
