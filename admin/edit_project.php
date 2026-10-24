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

$stmtFaculties = $pdo->prepare("SELECT * FROM project_faculty WHERE project_id = ? ORDER BY id");
$stmtFaculties->execute([$id]);
$faculties = $stmtFaculties->fetchAll(PDO::FETCH_ASSOC);

$stmtSsl = $pdo->prepare("SELECT * FROM project_ssl_certs WHERE project_id = ?");
$stmtSsl->execute([$id]);
$ssl = $stmtSsl->fetch(PDO::FETCH_ASSOC);

$stmtScreenshots = $pdo->prepare("SELECT * FROM project_screenshots WHERE project_id = ? ORDER BY sort_order, id");
$stmtScreenshots->execute([$id]);
$screenshots = $stmtScreenshots->fetchAll(PDO::FETCH_ASSOC);

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
                <label>Project Year</label>
                <input type="text" name="year" required value="<?= htmlspecialchars($project['year']) ?>">
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

        <!-- 2. CLASSIFICATION -->
        <div class="form-section-title"><i class="fa-solid fa-tags"></i> Classification</div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <?php
                    $predefinedCategories = ['Web Application', 'Mobile Application', 'Desktop Application', 'Platform'];
                    $savedCategory = trim($project['category'] ?? 'Web Application');
                    $isCustomCategory = !empty($savedCategory) && !in_array($savedCategory, $predefinedCategories);
                ?>
                <select name="category" id="categorySelect" onchange="toggleCategoryOther(this.value)">
                    <option value="Web Application" <?= $savedCategory === 'Web Application' ? 'selected' : '' ?>>Web Application</option>
                    <option value="Mobile Application" <?= $savedCategory === 'Mobile Application' ? 'selected' : '' ?>>Mobile Application</option>
                    <option value="Desktop Application" <?= $savedCategory === 'Desktop Application' ? 'selected' : '' ?>>Desktop Application</option>
                    <option value="Platform" <?= $savedCategory === 'Platform' ? 'selected' : '' ?>>Platform</option>
                    <option value="Other" <?= ($isCustomCategory || $savedCategory === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
                <div id="categoryOtherGroup" style="margin-top: 0.5rem; display: <?= ($isCustomCategory || $savedCategory === 'Other') ? 'block' : 'none' ?>;">
                    <label style="font-size: 0.85rem; color: #475569;">Specify Category</label>
                    <input type="text" name="category_custom" id="categoryCustomInput" value="<?= $isCustomCategory ? htmlspecialchars($savedCategory) : '' ?>" placeholder="Enter custom category">
                </div>
            </div>
            <div class="form-group">
                <label>Project Type</label>
                <select name="project_type">
                    <option value="Academic" <?= $project['project_type']=='Academic'?'selected':'' ?>>Academic</option>
                    <option value="Client" <?= $project['project_type']=='Client'?'selected':'' ?>>Client</option>
                    <option value="Internal" <?= $project['project_type']=='Internal'?'selected':'' ?>>Internal</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Project Status</label>
                <?php 
                    $currentStatus = trim($project['status'] ?? '');
                    if (!in_array($currentStatus, ['In Development', 'Completed'])) {
                        $currentStatus = 'Completed';
                    }
                ?>
                <select name="status">
                    <option value="In Development" <?= $currentStatus === 'In Development' ? 'selected' : '' ?>>In Development</option>
                    <option value="Completed" <?= $currentStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
                <span class="form-help" style="font-size: 0.8rem; color: #64748b;">Current saved status: <strong><?= htmlspecialchars($project['status'] ?? 'None') ?></strong></span>
            </div>
        </div>

        <!-- 3. TEAM DETAILS -->
        <div class="form-section-title"><i class="fa-solid fa-users-gear"></i> Team Details</div>
        <div class="form-row">
            <div class="form-group">
                <label>Department</label>
                <?php
                    $predefinedDepartments = ['MCA', 'BCA', 'Computer Science'];
                    $savedDept = trim($project['department'] ?? 'MCA');
                    $isCustomDept = !empty($savedDept) && !in_array($savedDept, $predefinedDepartments);
                ?>
                <select name="department" id="departmentSelect" onchange="toggleDepartmentOther(this.value)">
                    <option value="MCA" <?= $savedDept === 'MCA' ? 'selected' : '' ?>>MCA</option>
                    <option value="BCA" <?= $savedDept === 'BCA' ? 'selected' : '' ?>>BCA</option>
                    <option value="Computer Science" <?= $savedDept === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                    <option value="Other" <?= ($isCustomDept || $savedDept === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
                <div id="departmentOtherGroup" style="margin-top: 0.5rem; display: <?= ($isCustomDept || $savedDept === 'Other') ? 'block' : 'none' ?>;">
                    <label style="font-size: 0.85rem; color: #475569;">Specify Department</label>
                    <input type="text" name="department_custom" id="departmentCustomInput" value="<?= $isCustomDept ? htmlspecialchars($savedDept) : '' ?>" placeholder="Enter custom department">
                </div>
            </div>
            <div class="form-group">
                <label>Batch</label>
                <input type="text" name="batch" value="<?= htmlspecialchars($project['batch']) ?>" placeholder="e.g. 2024-2026">
            </div>
        </div>
        <div class="form-group">
            <div class="faculty-members-container" id="faculty-members-container">
                <?php if (empty($faculties)): ?>
                    <div class="faculty-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; flex-wrap: wrap;">
                        <input type="text" name="faculty_names[]" placeholder="Faculty Name" style="flex: 1; min-width: 140px;">
                        <input type="text" name="faculty_designations[]" placeholder="Designation" style="flex: 1; min-width: 130px;">
                        <input type="text" name="faculty_linkedin[]" placeholder="LinkedIn URL" style="flex: 1; min-width: 130px;">
                        <input type="file" name="faculty_photos[]" accept="image/*" style="flex: 1; min-width: 130px;">
                        <input type="url" name="faculty_photo_urls[]" placeholder="Or Photo URL / Drive Link" style="flex: 1.5; min-width: 160px;">
                        <input type="hidden" name="existing_faculty_photos[]" value="">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($faculties as $f): ?>
                        <div class="faculty-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="text" name="faculty_names[]" value="<?= htmlspecialchars($f['name'] ?? '') ?>" placeholder="Faculty Name" style="flex: 1; min-width: 140px;">
                            <input type="text" name="faculty_designations[]" value="<?= htmlspecialchars($f['designation'] ?? '') ?>" placeholder="Designation" style="flex: 1; min-width: 130px;">
                            <input type="text" name="faculty_linkedin[]" value="<?= htmlspecialchars($f['linkedin_link'] ?? '') ?>" placeholder="LinkedIn URL" style="flex: 1; min-width: 130px;">
                            
                            <div style="display:flex; flex-direction:column; flex:1; min-width: 130px;">
                                <input type="file" name="faculty_photos[]" accept="image/*" title="Upload new file to replace">
                                <?php if(!empty($f['photo_path'])): ?>
                                    <?php 
                                        $fImg = $f['photo_path'];
                                        $fSrc = (str_starts_with($fImg, 'http://') || str_starts_with($fImg, 'https://')) ? $fImg : '../' . $fImg;
                                    ?>
                                    <div style="display:flex; align-items:center; margin-top:4px; gap:6px;">
                                        <img src="<?= htmlspecialchars($fSrc) ?>" alt="Current image" style="width:24px; height:24px; object-fit:cover; border-radius:50%; border:1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 0.7rem; color: #64748b;">Current photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="url" name="faculty_photo_urls[]" value="<?= (str_starts_with($f['photo_path'] ?? '', 'http://') || str_starts_with($f['photo_path'] ?? '', 'https://')) ? htmlspecialchars($f['photo_path']) : '' ?>" placeholder="Or Photo URL / Drive Link" style="flex: 1.5; min-width: 160px;">
                            <input type="hidden" name="existing_faculty_photos[]" value="<?= htmlspecialchars($f['photo_path'] ?? '') ?>">
                            
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add" onclick="addFacultyRow()" style="margin-top:0.5rem;"><i class="fas fa-plus"></i> Add Faculty</button>
        </div>

        <div class="form-section-title"><i class="fa-solid fa-users"></i> Team Members</div>
        <div class="form-group">
            <div class="team-members-container" id="team-members-container">
                <?php if (empty($members)): ?>
                    <div class="team-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; flex-wrap: wrap;">
                        <input type="text" name="member_names[]" placeholder="Student Name" style="flex: 1; min-width: 140px;">
                        <input type="text" name="member_roles[]" placeholder="Role (optional)" style="flex: 1; min-width: 130px;">
                        <input type="text" name="member_linkedin[]" placeholder="LinkedIn URL (optional)" style="flex: 1; min-width: 130px;">
                        <input type="file" name="member_photos[]" accept="image/*" style="flex: 1; min-width: 130px;">
                        <input type="url" name="member_photo_urls[]" placeholder="Or Photo URL / Drive Link" style="flex: 1.5; min-width: 160px;">
                        <input type="hidden" name="existing_photos[]" value="">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                        <div class="team-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="text" name="member_names[]" value="<?= htmlspecialchars($m['name'] ?? '') ?>" placeholder="Student Name" style="flex: 1; min-width: 140px;">
                            <input type="text" name="member_roles[]" value="<?= htmlspecialchars($m['role'] ?? '') ?>" placeholder="Role (optional)" style="flex: 1; min-width: 130px;">
                            <input type="text" name="member_linkedin[]" value="<?= htmlspecialchars($m['linkedin_link'] ?? '') ?>" placeholder="LinkedIn URL (optional)" style="flex: 1; min-width: 130px;">
                            
                            <div style="display:flex; flex-direction:column; flex:1; min-width: 130px;">
                                <input type="file" name="member_photos[]" accept="image/*" title="Upload new file to replace">
                                <?php if(!empty($m['photo_path'])): ?>
                                    <?php 
                                        $mImg = $m['photo_path'];
                                        $mSrc = (str_starts_with($mImg, 'http://') || str_starts_with($mImg, 'https://')) ? $mImg : '../' . $mImg;
                                    ?>
                                    <div style="display:flex; align-items:center; margin-top:4px; gap:6px;">
                                        <img src="<?= htmlspecialchars($mSrc) ?>" alt="Current image" style="width:24px; height:24px; object-fit:cover; border-radius:50%; border:1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 0.7rem; color: #64748b;">Current photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="url" name="member_photo_urls[]" value="<?= (str_starts_with($m['photo_path'] ?? '', 'http://') || str_starts_with($m['photo_path'] ?? '', 'https://')) ? htmlspecialchars($m['photo_path']) : '' ?>" placeholder="Or Photo URL / Drive Link" style="flex: 1.5; min-width: 160px;">
                            <input type="hidden" name="existing_photos[]" value="<?= htmlspecialchars($m['photo_path'] ?? '') ?>">
                            
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add" onclick="addMemberRow()" style="margin-top:0.5rem;"><i class="fas fa-plus"></i> Add Member</button>
        </div>

        <!-- 4. OTHER PROJECT DETAILS -->
        <div class="form-section-title"><i class="fa-solid fa-sliders"></i> Other Project Details</div>
        <div class="form-group">
            <label>Duration</label>
            <input type="text" name="duration" value="<?= htmlspecialchars($project['duration']) ?>" placeholder="e.g. 6 months">
        </div>
        <div class="form-group">
            <label>Technologies (Comma separated)</label>
            <input type="text" name="tech_stack" value="<?= htmlspecialchars($project['tech_stack']) ?>" placeholder="e.g. PHP, HTML, CSS">
        </div>
        <div class="form-group">
            <label>Key Features (Comma separated)</label>
            <input type="text" name="key_features" value="<?= htmlspecialchars($project['key_features']) ?>" placeholder="e.g. Authentication, Dashboard">
        </div>

        <div class="form-section-title"><i class="fa-solid fa-images"></i> Images</div>

        <div class="form-group">
            <label>Project Thumbnail (Upload new or provide URL/Drive link)</label>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.35rem;">
                <input type="file" name="thumbnail" accept="image/*" style="flex: 1; min-width: 200px;">
                <input type="url" name="thumbnail_url" value="<?= (str_starts_with($project['thumbnail_path'] ?? '', 'http://') || str_starts_with($project['thumbnail_path'] ?? '', 'https://')) ? htmlspecialchars($project['thumbnail_path']) : '' ?>" placeholder="Or paste image URL / Google Drive link" style="flex: 1.5; min-width: 250px;">
            </div>
            <?php if($project['thumbnail_path']): ?>
                <?php 
                    $thumbSrc = (str_starts_with($project['thumbnail_path'], 'http://') || str_starts_with($project['thumbnail_path'], 'https://')) ? $project['thumbnail_path'] : '../' . $project['thumbnail_path'];
                ?>
                <div id="thumbnail-preview-card" style="margin-top: 10px; display: flex; align-items: center; gap: 15px; background: var(--white); padding: 10px; border-radius: 8px; border: 1px solid var(--hairline-slate);">
                    <img src="<?= htmlspecialchars($thumbSrc) ?>" alt="Thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                    <div style="flex: 1;">
                        <span style="font-size: 0.8rem; color: var(--slate-text); display: block; margin-bottom: 4px;">Current: <?= htmlspecialchars($project['thumbnail_path']) ?></span>
                        <button type="button" class="btn-action" style="background: #F9440D; color: white; border: none; padding: 6px 16px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; display: inline-block; white-space: nowrap; margin-top: 4px; height: auto; width: auto;" onclick="document.getElementById('thumbnail-preview-card').style.display='none'; const input = document.createElement('input'); input.type='hidden'; input.name='delete_thumbnail'; input.value='1'; this.parentElement.appendChild(input);">
                            Delete
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>


        <div class="form-group" style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
            <label>Project Screenshots (Upload multiple)</label>
            <input type="file" name="new_screenshots[]" accept="image/*" multiple>
            
            <?php if (!empty($screenshots)): ?>
                <div style="margin-top: 15px;">
                    <label style="margin-bottom: 10px; display: block;">Existing Screenshots</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                        <?php foreach ($screenshots as $ss): ?>
                            <div class="screenshot-card" id="screenshot-card-<?= $ss['id'] ?>" style="background: var(--white); border: 1px solid var(--hairline-slate); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                                <div style="height: 100px; overflow: hidden; background: #f8fafc;">
                                    <img src="../<?= htmlspecialchars($ss['image_path']) ?>" alt="Screenshot" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="padding: 10px; text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <span style="font-size: 0.75rem; color: var(--slate-text); display: block; margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars(basename($ss['image_path'])) ?>">
                                        <?= htmlspecialchars(basename($ss['image_path'])) ?>
                                    </span>
                                    <button type="button" class="btn-action" style="background: #F9440D; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; width: 100%; height: auto;" onclick="document.getElementById('screenshot-card-<?= $ss['id'] ?>').style.display='none'; const input = document.createElement('input'); input.type='hidden'; input.name='delete_screenshots[]'; input.value='<?= $ss['id'] ?>'; this.parentElement.appendChild(input);">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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

        <!-- SSL Certificate Module -->
        <div class="form-section-title"><i class="fa-solid fa-shield-halved"></i> SSL Certificate Details (Optional)</div>
        <div class="form-row" style="align-items: flex-end; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 2; min-width: 250px;">
                <label for="ssl_domain">Domain URL</label>
                <input type="url" id="ssl_domain" name="ssl_domain" value="<?= htmlspecialchars($ssl['domain_url'] ?? '') ?>" placeholder="https://example.com">
            </div>
            <div class="form-group" style="flex: 1.2; min-width: 200px;">
                <!-- Highlighted Checkbox & SSL Fetch Text -->
                <label class="ssl-highlight-box" for="ssl_tracking_enabled" style="display: flex; align-items: center; gap: 0.65rem; background: linear-gradient(135deg, rgba(14, 165, 233, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%); border: 2px solid #0284c7; border-radius: 8px; padding: 0.65rem 0.85rem; cursor: pointer; box-shadow: 0 0 14px rgba(14, 165, 233, 0.25); height: 46px; box-sizing: border-box; margin: 0;">
                    <input type="checkbox" id="ssl_tracking_enabled" name="ssl_tracking_enabled" value="1" <?= (!isset($project['ssl_tracking_enabled']) || $project['ssl_tracking_enabled'] != 0) ? 'checked' : '' ?> style="width: 1.25rem; height: 1.25rem; accent-color: #0284c7; cursor: pointer; margin: 0;">
                    <span style="font-size: 0.88rem; font-weight: 700; color: #38bdf8; letter-spacing: 0.02em; white-space: nowrap;">
                        <i class="fa-solid fa-shield-check" style="margin-right: 4px;"></i> Enable SSL Fetch
                    </span>
                </label>
            </div>
            <div class="form-group" style="flex: 1; min-width: 170px;">
                <button type="button" class="btn-action primary" id="btn-fetch-ssl" style="width: 100%; height: 46px; padding: 0 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"><i class="fa-solid fa-cloud-arrow-down"></i> Fetch SSL Details</button>
            </div>
        </div>
        
        <div id="ssl-status-message" style="margin-bottom: 1rem; font-size: 0.9rem;"></div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="ssl_provider">Provider</label>
                <input type="text" id="ssl_provider" name="ssl_provider" value="<?= htmlspecialchars($ssl['provider'] ?? '') ?>" placeholder="Auto-filled on fetch" style="background: #1e293b; color: #94a3b8; border-color: #334155;">
            </div>
            <div class="form-group">
                <label>Status / Days Remaining</label>
                <input type="text" id="ssl_status_display" value="" placeholder="Auto-filled on fetch" style="background: #1e293b; color: #94a3b8; border-color: #334155;">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="ssl_issue_date">Issue Date</label>
                <input type="date" id="ssl_issue_date" name="ssl_issue_date" value="<?= htmlspecialchars($ssl['issue_date'] ?? '') ?>" style="background: #1e293b; color: #94a3b8; border-color: #334155;">
            </div>
            <div class="form-group">
                <label for="ssl_expiry_date">Expiry Date</label>
                <input type="date" id="ssl_expiry_date" name="ssl_expiry_date" value="<?= htmlspecialchars($ssl['expiry_date'] ?? '') ?>" style="background: #1e293b; color: #94a3b8; border-color: #334155;">
            </div>
        </div>
        <input type="hidden" id="ssl_fingerprint" name="ssl_fingerprint" value="<?= htmlspecialchars($ssl['certificate_fingerprint'] ?? '') ?>">

        <button type="submit" class="btn-submit full-width"><i class="fa-solid fa-save"></i> Save Changes</button>
    </form>

</div>

<?php require_once 'includes/layout_footer.php'; ?>
<script>
    function addMemberRow() {
        const container = document.getElementById('team-members-container');
        const row = document.createElement('div');
        row.className = 'team-member-row';
        row.style.display = 'flex';
        row.style.gap = '0.5rem';
        row.style.marginBottom = '0.5rem';
        row.style.alignItems = 'center';
        row.style.flexWrap = 'wrap';
        row.innerHTML = `
            <input type="text" name="member_names[]" placeholder="Student Name" style="flex: 1; min-width: 140px;">
            <input type="text" name="member_roles[]" placeholder="Role (optional)" style="flex: 1; min-width: 130px;">
            <input type="text" name="member_linkedin[]" placeholder="LinkedIn URL (optional)" style="flex: 1; min-width: 130px;">
            <input type="file" name="member_photos[]" accept="image/*" style="flex: 1; min-width: 130px;">
            <input type="url" name="member_photo_urls[]" placeholder="Or Photo URL / Drive Link" style="flex: 1.5; min-width: 160px;">
            <input type="hidden" name="existing_photos[]" value="">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
    }

    function addFacultyRow() {
        const container = document.getElementById('faculty-members-container');
        const row = document.createElement('div');
        row.className = 'faculty-member-row';
        row.style.display = 'flex';
        row.style.gap = '0.5rem';
        row.style.marginBottom = '0.5rem';
        row.style.alignItems = 'center';
        row.style.flexWrap = 'wrap';
        row.innerHTML = `
            <input type="text" name="faculty_names[]" placeholder="Faculty Name" style="flex: 1; min-width: 140px;">
            <input type="text" name="faculty_designations[]" placeholder="Designation" style="flex: 1; min-width: 130px;">
            <input type="text" name="faculty_linkedin[]" placeholder="LinkedIn URL" style="flex: 1; min-width: 130px;">
            <input type="file" name="faculty_photos[]" accept="image/*" style="flex: 1; min-width: 130px;">
            <input type="url" name="faculty_photo_urls[]" placeholder="Or Photo URL / Drive Link" style="flex: 1.5; min-width: 160px;">
            <input type="hidden" name="existing_faculty_photos[]" value="">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
    }

    // ---- SSL Fetch Details ----
    document.getElementById('btn-fetch-ssl')?.addEventListener('click', async function() {
        const domainInput = document.getElementById('ssl_domain').value.trim();
        const msgDiv = document.getElementById('ssl-status-message');
        const btn = this;
        
        if (!domainInput) {
            msgDiv.innerHTML = '<span style="color: #fca5a5;"><i class="fa-solid fa-triangle-exclamation"></i> Please enter a Domain URL first.</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching...';
        msgDiv.innerHTML = '<span style="color: #94a3b8;">Connecting to domain...</span>';

        try {
            const response = await fetch(`api/fetch_ssl.php?domain=${encodeURIComponent(domainInput)}`);
            const data = await response.json();
            
            if (data.error) {
                msgDiv.innerHTML = `<span style="color: #fca5a5;"><i class="fa-solid fa-circle-xmark"></i> ${data.error}</span>`;
                document.getElementById('ssl_provider').value = '';
                document.getElementById('ssl_issue_date').value = '';
                document.getElementById('ssl_expiry_date').value = '';
                document.getElementById('ssl_status_display').value = '';
                document.getElementById('ssl_fingerprint').value = '';
            } else {
                msgDiv.innerHTML = '<span style="color: #4ade80;"><i class="fa-solid fa-circle-check"></i> Certificate fetched successfully!</span>';
                document.getElementById('ssl_provider').value = data.provider;
                document.getElementById('ssl_issue_date').value = data.issue_date;
                document.getElementById('ssl_expiry_date').value = data.expiry_date;
                document.getElementById('ssl_status_display').value = `${data.status} (${data.days_left} days left)`;
                document.getElementById('ssl_fingerprint').value = data.fingerprint;
            }
        } catch (err) {
            msgDiv.innerHTML = '<span style="color: #fca5a5;"><i class="fa-solid fa-circle-xmark"></i> Network error occurred.</span>';
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Fetch SSL Details';
    });

    function toggleDepartmentOther(val) {
        const group = document.getElementById('departmentOtherGroup');
        const input = document.getElementById('departmentCustomInput');
        if (!group) return;
        if (val === 'Other') {
            group.style.display = 'block';
            if (input) input.focus();
        } else {
            group.style.display = 'none';
        }
    }

    function toggleCategoryOther(val) {
        const group = document.getElementById('categoryOtherGroup');
        const input = document.getElementById('categoryCustomInput');
        if (!group) return;
        if (val === 'Other') {
            group.style.display = 'block';
            if (input) input.focus();
        } else {
            group.style.display = 'none';
        }
    }
</script>
