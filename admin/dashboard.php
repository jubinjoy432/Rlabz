<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'api/db.php';

// Fetch existing projects
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY year DESC, id DESC");
        $projects = $stmt->fetchAll();
        // Fetch members
        $stmtMembers = $pdo->query("SELECT * FROM project_members ORDER BY project_id, id");
        $allMembers = $stmtMembers->fetchAll();
        $membersByProject = [];
        foreach ($allMembers as $m) {
            $membersByProject[$m['project_id']][] = $m;
        }
        // Fetch screenshots
        $stmtScreenshots = $pdo->query("SELECT * FROM project_screenshots ORDER BY project_id, sort_order");
        $allScreenshots = $stmtScreenshots ? $stmtScreenshots->fetchAll() : [];
        $screenshotsByProject = [];
        foreach ($allScreenshots as $s) {
            $screenshotsByProject[$s['project_id']][] = $s;
        }
    } catch (PDOException $e) {
        $projects = [];
        $error = "Failed to load projects: " . $e->getMessage();
    }
} else {
    // TEMPORARY BYPASS FOR UI PREVIEW
    $projects = [
        [
            'id' => 1,
            'title' => 'Example Project 1',
            'year' => '2024',
            'description' => 'This is a preview of how projects will look once the database is connected.',
            'image_path' => 'images/rz-logo.webp',
            'status' => 'Deployed',
            'category' => 'Web Application',
            'department' => 'MCA',
            'tech_stack' => 'PHP, HTML, CSS'
        ],
        [
            'id' => 2,
            'title' => 'Example Project 2',
            'year' => '2023',
            'description' => 'Another preview item. Imagine your real uploaded images here.',
            'image_path' => 'images/rz-logo.webp',
            'status' => 'In Development',
            'category' => 'Mobile Application',
            'department' => 'MCA',
            'tech_stack' => 'Flutter, Dart'
        ]
    ];
    $error = "Warning: MySQL Database is not connected yet! You are viewing placeholder data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RLabz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

    <nav class="navbar">
        <h1><i class="fa-solid fa-shield-halved"></i> RLabz Admin</h1>
        <div class="navbar-actions">
            <a href="../index.html" class="btn-view-site" target="_blank"><i class="fa-solid fa-external-link-alt"></i> View Site</a>
            <a href="api/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Add Project Form -->
        <div class="card">
            <h2><i class="fa-solid fa-plus-circle"></i> Add New Project</h2>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Project added successfully!</div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form action="api/add_project.php" method="POST" enctype="multipart/form-data" id="addProjectForm">
                
                <!-- Basic Info -->
                <div class="form-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>

                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" required placeholder="e.g. euphoria-2024 (auto-generated from title)">
                    <span class="form-help">Unique URL identifier. Use lowercase letters, numbers, and hyphens only.</span>
                </div>

                <div class="form-group">
                    <label for="title">Project Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. Euphoria 2024">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Academic Year</label>
                        <input type="text" id="year" name="year" required placeholder="e.g. 2024">
                    </div>
                    <div class="form-group">
                        <label for="batch">Batch</label>
                        <input type="text" id="batch" name="batch" placeholder="e.g. 2022-2024">
                    </div>
                </div>

                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <input type="text" id="short_description" name="short_description" placeholder="Brief description (max 300 chars)" maxlength="300">
                    <span class="form-help">Shown on project cards. Keep it concise.</span>
                </div>

                <div class="form-group">
                    <label for="description">Full Description</label>
                    <textarea id="description" name="description" required placeholder="Detailed project description..."></textarea>
                </div>

                <div class="form-group">
                    <label for="objectives">Objectives</label>
                    <textarea id="objectives" name="objectives" placeholder="Project objectives..."></textarea>
                </div>

                <div class="form-group">
                    <label for="problem_statement">Problem Statement</label>
                    <textarea id="problem_statement" name="problem_statement" placeholder="What problem does this project solve?"></textarea>
                </div>

                <div class="form-group">
                    <label for="expected_outcome">Expected Outcome</label>
                    <textarea id="expected_outcome" name="expected_outcome" placeholder="Expected results and impact..."></textarea>
                </div>

                <!-- Classification -->
                <div class="form-section-title"><i class="fa-solid fa-tags"></i> Classification</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="MCA">MCA</option>
                            <option value="BCA">BCA</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="Web Application">Web Application</option>
                            <option value="Mobile Application">Mobile Application</option>
                            <option value="Desktop Application">Desktop Application</option>
                            <option value="Platform">Platform</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="project_type">Project Type</label>
                        <select id="project_type" name="project_type">
                            <option value="Academic">Academic</option>
                            <option value="Client">Client</option>
                            <option value="Internal">Internal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="Completed">Completed</option>
                            <option value="Deployed">Deployed</option>
                            <option value="In Development">In Development</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="duration">Duration</label>
                    <input type="text" id="duration" name="duration" placeholder="e.g. 6 months">
                </div>

                <!-- Technologies -->
                <div class="form-section-title"><i class="fa-solid fa-microchip"></i> Technologies</div>

                <div class="form-group">
                    <label>Tech Stack</label>
                    <div class="tech-tags-input-wrapper" id="techTagsWrapper">
                        <input type="text" class="tech-tags-text-input" id="techTagsInput" placeholder="Type and press Enter...">
                    </div>
                    <input type="hidden" name="tech_stack" id="techStackHidden">
                    <span class="form-help">Press Enter or comma to add a technology tag.</span>
                </div>

                <!-- Key Features -->
                <div class="form-section-title"><i class="fa-solid fa-list-check"></i> Key Features</div>

                <div class="form-group">
                    <label>Features</label>
                    <div class="tech-tags-input-wrapper" id="featuresWrapper">
                        <input type="text" class="tech-tags-text-input" id="featuresInput" placeholder="Type a feature and press Enter...">
                    </div>
                    <input type="hidden" name="key_features" id="keyFeaturesHidden">
                    <span class="form-help">Add features like "Authentication", "Dashboard", "Reports", etc.</span>
                </div>

                <!-- Faculty -->
                <div class="form-section-title"><i class="fa-solid fa-user-tie"></i> Faculty Information</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="faculty_name">Faculty Name</label>
                        <input type="text" id="faculty_name" name="faculty_name" placeholder="e.g. Prof. Jubin Joy">
                    </div>
                    <div class="form-group">
                        <label for="faculty_designation">Designation</label>
                        <input type="text" id="faculty_designation" name="faculty_designation" placeholder="e.g. Assistant Professor">
                    </div>
                </div>

                <div class="form-group">
                    <label for="faculty_photo">Faculty Photo</label>
                    <input type="file" id="faculty_photo" name="faculty_photo" accept="image/*">
                </div>

                <!-- Team Members -->
                <div class="form-section-title"><i class="fa-solid fa-users"></i> Team Members</div>
                
                <div class="form-group">
                    <div class="team-members-container" id="team-members-container">
                        <div class="team-member-row">
                            <input type="text" name="member_names[]" placeholder="Student Name" style="padding:0.6rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:#e2e8f0; font-family:inherit;">
                            <input type="text" name="member_roles[]" placeholder="Role (optional)" style="padding:0.6rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:#e2e8f0; font-family:inherit;">
                            <input type="file" name="member_photos[]" accept="image/*" style="font-size:0.8rem; color:#94a3b8;">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn-add" onclick="addMemberRow()"><i class="fas fa-plus"></i> Add Member</button>
                </div>

                <!-- Images -->
                <div class="form-section-title"><i class="fa-solid fa-images"></i> Images</div>

                <div class="form-group">
                    <label for="image">Project Cover Image / Thumbnail</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    <span class="form-help">Max size 5MB. Recommended: WebP or JPG. This will be used as the project thumbnail.</span>
                </div>

                <div class="form-group">
                    <label for="screenshots">Additional Screenshots</label>
                    <input type="file" id="screenshots" name="screenshots[]" accept="image/*" multiple>
                    <span class="form-help">Select multiple images. These will be shown in the project gallery.</span>
                </div>

                <div class="form-group">
                    <label for="poster">Project Poster (optional)</label>
                    <input type="file" id="poster" name="poster" accept="image/*">
                </div>

                <!-- Links -->
                <div class="form-section-title"><i class="fa-solid fa-link"></i> Links</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="github_link">GitHub Link</label>
                        <input type="url" id="github_link" name="github_link" placeholder="https://github.com/...">
                    </div>
                    <div class="form-group">
                        <label for="demo_link">Demo Link</label>
                        <input type="url" id="demo_link" name="demo_link" placeholder="https://...">
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fa-solid fa-rocket"></i> Add Project</button>
            </form>
        </div>

        <!-- Project List -->
        <div class="card">
            <h2><i class="fa-solid fa-folder-open"></i> Existing Projects (<?php echo count($projects); ?>)</h2>
            <?php if(isset($error)): ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(empty($projects)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>No projects found. Add one to get started!</p>
                </div>
            <?php else: ?>
                <ul class="project-list">
                    <?php foreach($projects as $p): ?>
                    <li class="project-item">
                        <img src="../<?php echo htmlspecialchars($p['image_path']); ?>" alt="Project image" class="project-img" onerror="this.src='../images/rz-logo.webp'">
                        <div class="project-info">
                            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($p['description'], 0, 100)); ?>...</p>
                            <div class="project-meta-badges">
                                <span class="project-meta-badge badge-year"><?php echo htmlspecialchars($p['year']); ?></span>
                                <?php if(!empty($p['status'])): ?>
                                <span class="project-meta-badge badge-status"><?php echo htmlspecialchars($p['status']); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($p['category'])): ?>
                                <span class="project-meta-badge badge-category"><?php echo htmlspecialchars($p['category']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="project-actions">
                            <?php if(!empty($p['slug'])): ?>
                            <a href="../project-details.html?id=<?php echo htmlspecialchars($p['slug']); ?>" class="btn-action btn-preview" target="_blank" title="Preview"><i class="fas fa-eye"></i></a>
                            <?php endif; ?>
                            <a href="edit_project.php?id=<?php echo $p['id']; ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="api/delete_project.php?id=<?php echo $p['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this project?');" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ---- Team Member Rows ----
        function addMemberRow() {
            const container = document.getElementById('team-members-container');
            const row = document.createElement('div');
            row.className = 'team-member-row';
            row.innerHTML = `
                <input type="text" name="member_names[]" placeholder="Student Name" style="padding:0.6rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:#e2e8f0; font-family:inherit;">
                <input type="text" name="member_roles[]" placeholder="Role (optional)" style="padding:0.6rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:#e2e8f0; font-family:inherit;">
                <input type="file" name="member_photos[]" accept="image/*" style="font-size:0.8rem; color:#94a3b8;">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(row);
        }

        // ---- Dynamic Tag Input ----
        function setupTagInput(wrapperId, inputId, hiddenId) {
            const wrapper = document.getElementById(wrapperId);
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            if (!wrapper || !input || !hidden) return;

            const tags = [];

            function addTag(value) {
                value = value.trim();
                if (!value || tags.includes(value)) return;
                tags.push(value);
                updateUI();
            }

            function removeTag(value) {
                const idx = tags.indexOf(value);
                if (idx !== -1) tags.splice(idx, 1);
                updateUI();
            }

            function updateUI() {
                // Remove old chips
                wrapper.querySelectorAll('.tech-tag-chip').forEach(el => el.remove());
                // Add chips before input
                tags.forEach(tag => {
                    const chip = document.createElement('span');
                    chip.className = 'tech-tag-chip';
                    chip.innerHTML = `${tag} <button type="button" onclick="event.stopPropagation();">&times;</button>`;
                    chip.querySelector('button').addEventListener('click', () => removeTag(tag));
                    wrapper.insertBefore(chip, input);
                });
                hidden.value = tags.join(',');
            }

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addTag(input.value);
                    input.value = '';
                }
                if (e.key === 'Backspace' && !input.value && tags.length > 0) {
                    removeTag(tags[tags.length - 1]);
                }
            });

            wrapper.addEventListener('click', () => input.focus());
        }

        setupTagInput('techTagsWrapper', 'techTagsInput', 'techStackHidden');
        setupTagInput('featuresWrapper', 'featuresInput', 'keyFeaturesHidden');

        // ---- Auto-generate slug from title ----
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        if (titleInput && slugInput) {
            titleInput.addEventListener('input', () => {
                if (!slugInput.dataset.manual) {
                    slugInput.value = titleInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim();
                }
            });
            slugInput.addEventListener('input', () => {
                slugInput.dataset.manual = '1';
            });
        }
    </script>
</body>
</html>
