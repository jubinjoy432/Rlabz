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
        <div class="form-group">
            <div class="faculty-members-container" id="faculty-members-container">
                <?php if (empty($faculties)): ?>
                    <div class="faculty-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                        <input type="text" name="faculty_names[]" placeholder="Faculty Name" style="flex: 1;">
                        <input type="text" name="faculty_designations[]" placeholder="Designation" style="flex: 1;">
                        <input type="text" name="faculty_linkedin[]" placeholder="LinkedIn URL" style="flex: 1;">
                        <input type="file" name="faculty_photos[]" accept="image/*" style="flex: 1;">
                        <input type="hidden" name="existing_faculty_photos[]" value="">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($faculties as $f): ?>
                        <div class="faculty-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items: center;">
                            <input type="text" name="faculty_names[]" value="<?= htmlspecialchars($f['name'] ?? '') ?>" placeholder="Faculty Name" style="flex: 1;">
                            <input type="text" name="faculty_designations[]" value="<?= htmlspecialchars($f['designation'] ?? '') ?>" placeholder="Designation" style="flex: 1;">
                            <input type="text" name="faculty_linkedin[]" value="<?= htmlspecialchars($f['linkedin_link'] ?? '') ?>" placeholder="LinkedIn URL" style="flex: 1;">
                            
                            <div style="display:flex; flex-direction:column; flex:1;">
                                <input type="file" name="faculty_photos[]" accept="image/*">
                                <?php if(!empty($f['photo_path'])): ?>
                                    <div style="display:flex; align-items:center; margin-top:4px; gap:6px;">
                                        <img src="../<?= htmlspecialchars($f['photo_path']) ?>" alt="Current image" style="width:24px; height:24px; object-fit:cover; border-radius:50%; border:1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 0.7rem; color: #64748b;">Current image uploaded</span>
                                    </div>
                                <?php endif; ?>
                            </div>
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
                    <div class="team-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                        <input type="text" name="member_names[]" placeholder="Student Name" style="flex: 1;">
                        <input type="text" name="member_roles[]" placeholder="Role (optional)" style="flex: 1;">
                        <input type="text" name="member_linkedin[]" placeholder="LinkedIn URL (optional)" style="flex: 1.5;">
                        <input type="file" name="member_photos[]" accept="image/*" style="flex: 1;">
                        <input type="hidden" name="existing_photos[]" value="">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                        <div class="team-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items: center;">
                            <input type="text" name="member_names[]" value="<?= htmlspecialchars($m['name'] ?? '') ?>" placeholder="Student Name" style="flex: 1;">
                            <input type="text" name="member_roles[]" value="<?= htmlspecialchars($m['role'] ?? '') ?>" placeholder="Role (optional)" style="flex: 1;">
                            <input type="text" name="member_linkedin[]" value="<?= htmlspecialchars($m['linkedin_link'] ?? '') ?>" placeholder="LinkedIn URL (optional)" style="flex: 1.5;">
                            
                            <div style="display:flex; flex-direction:column; flex:1;">
                                <input type="file" name="member_photos[]" accept="image/*">
                                <?php if(!empty($m['photo_path'])): ?>
                                    <div style="display:flex; align-items:center; margin-top:4px; gap:6px;">
                                        <img src="../<?= htmlspecialchars($m['photo_path']) ?>" alt="Current image" style="width:24px; height:24px; object-fit:cover; border-radius:50%; border:1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 0.7rem; color: #64748b;">Current image uploaded</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="existing_photos[]" value="<?= htmlspecialchars($m['photo_path'] ?? '') ?>">
                            
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add" onclick="addMemberRow()" style="margin-top:0.5rem;"><i class="fas fa-plus"></i> Add Member</button>
        </div>

        <div class="form-section-title"><i class="fa-solid fa-images"></i> Images</div>
        <div class="form-group">
            <label>Project Cover Image (Upload new to replace)</label>
            <input type="file" name="image" accept="image/*">
            <?php if($project['image_path']): ?>
                <div style="margin-top: 10px; display: flex; align-items: center; gap: 15px; background: rgba(15, 23, 42, 0.4); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);">
                    <img src="../<?= htmlspecialchars($project['image_path']) ?>" alt="Cover" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                    <div style="flex: 1;">
                        <span style="font-size: 0.8rem; color: #94a3b8; display: block; margin-bottom: 4px;">Current: <?= htmlspecialchars(basename($project['image_path'])) ?></span>
                        <label style="font-size: 0.85rem; color: #fca5a5; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                            <input type="checkbox" name="delete_image" value="1" style="accent-color: #ef4444; width: 14px; height: 14px; margin: 0;"> Delete cover image
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Project Thumbnail (Upload new to replace)</label>
            <input type="file" name="thumbnail" accept="image/*">
            <?php if($project['thumbnail_path']): ?>
                <div style="margin-top: 10px; display: flex; align-items: center; gap: 15px; background: rgba(15, 23, 42, 0.4); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);">
                    <img src="../<?= htmlspecialchars($project['thumbnail_path']) ?>" alt="Thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                    <div style="flex: 1;">
                        <span style="font-size: 0.8rem; color: #94a3b8; display: block; margin-bottom: 4px;">Current: <?= htmlspecialchars(basename($project['thumbnail_path'])) ?></span>
                        <label style="font-size: 0.85rem; color: #fca5a5; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                            <input type="checkbox" name="delete_thumbnail" value="1" style="accent-color: #ef4444; width: 14px; height: 14px; margin: 0;"> Delete thumbnail image
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Project Poster (Upload new to replace)</label>
            <input type="file" name="poster" accept="image/*">
            <?php if($project['poster_path']): ?>
                <div style="margin-top: 10px; display: flex; align-items: center; gap: 15px; background: rgba(15, 23, 42, 0.4); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);">
                    <img src="../<?= htmlspecialchars($project['poster_path']) ?>" alt="Poster" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                    <div style="flex: 1;">
                        <span style="font-size: 0.8rem; color: #94a3b8; display: block; margin-bottom: 4px;">Current: <?= htmlspecialchars(basename($project['poster_path'])) ?></span>
                        <label style="font-size: 0.85rem; color: #fca5a5; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                            <input type="checkbox" name="delete_poster" value="1" style="accent-color: #ef4444; width: 14px; height: 14px; margin: 0;"> Delete poster image
                        </label>
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
        <div class="form-row" style="align-items: flex-end;">
            <div class="form-group" style="flex: 2;">
                <label for="ssl_domain">Domain URL</label>
                <input type="url" id="ssl_domain" name="ssl_domain" value="<?= htmlspecialchars($ssl['domain_url'] ?? '') ?>" placeholder="https://example.com">
            </div>
            <div class="form-group" style="flex: 1;">
                <button type="button" class="btn-action primary" id="btn-fetch-ssl" style="width: 100%; padding: 0.75rem;"><i class="fa-solid fa-cloud-arrow-down"></i> Fetch SSL Details</button>
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
        row.innerHTML = `
            <input type="text" name="member_names[]" placeholder="Student Name" style="flex: 1;">
            <input type="text" name="member_roles[]" placeholder="Role (optional)" style="flex: 1;">
            <input type="text" name="member_linkedin[]" placeholder="LinkedIn URL (optional)" style="flex: 1.5;">
            <input type="file" name="member_photos[]" accept="image/*" style="flex: 1;">
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
        row.innerHTML = `
            <input type="text" name="faculty_names[]" placeholder="Faculty Name" style="flex: 1;">
            <input type="text" name="faculty_designations[]" placeholder="Designation" style="flex: 1;">
            <input type="text" name="faculty_linkedin[]" placeholder="LinkedIn URL" style="flex: 1;">
            <input type="file" name="faculty_photos[]" accept="image/*" style="flex: 1;">
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
</script>
