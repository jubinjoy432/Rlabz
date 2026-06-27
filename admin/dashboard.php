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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
        }
        .navbar h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .navbar-actions { display: flex; align-items: center; gap: 1rem; }
        .btn-view-site {
            color: #7dd3fc;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(125,211,252,0.2);
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-view-site:hover { background: rgba(125,211,252,0.1); }
        .btn-logout {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .btn-logout:hover { color: white; }

        .dashboard-container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
        }

        .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 2rem;
        }
        .card h2 {
            font-family: 'Space Grotesk', sans-serif;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .card h2 i {
            color: #38bdf8;
            font-size: 1.1rem;
        }

        /* Form */
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            font-family: inherit;
            box-sizing: border-box;
            color: #e2e8f0;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(56,189,248,0.4);
            box-shadow: 0 0 0 3px rgba(56,189,248,0.08);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #475569;
        }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .form-group select option { background: #1e293b; color: #e2e8f0; }
        .form-help { font-size: 0.75rem; color: #64748b; margin-top: 0.3rem; display: block; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }

        /* Section Divider */
        .form-section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: #7dd3fc;
            margin: 1.5rem 0 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.04);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-section-title i { font-size: 0.8rem; }

        .btn-submit {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37,99,235,0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success {
            background: rgba(34,197,94,0.12);
            color: #4ade80;
            border: 1px solid rgba(34,197,94,0.2);
        }
        .alert-error {
            background: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.2);
        }

        /* Team Members */
        .team-members-container {
            border: 1px solid rgba(255,255,255,0.06);
            padding: 1rem;
            border-radius: 10px;
            background: rgba(255,255,255,0.02);
            margin-bottom: 0.5rem;
        }
        .team-member-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto auto;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed rgba(255,255,255,0.04);
        }
        .team-member-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        @media (max-width: 600px) {
            .team-member-row {
                grid-template-columns: 1fr;
            }
        }

        .btn-add {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            border: 1px solid rgba(34,197,94,0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        .btn-add:hover { background: rgba(34,197,94,0.25); }
        .btn-remove {
            background: rgba(239,68,68,0.15);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.2);
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-remove:hover { background: rgba(239,68,68,0.25); }

        /* Tech Tag Input */
        .tech-tags-input-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            padding: 0.5rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            min-height: 42px;
            align-items: center;
            cursor: text;
        }
        .tech-tags-input-wrapper:focus-within {
            border-color: rgba(56,189,248,0.4);
            box-shadow: 0 0 0 3px rgba(56,189,248,0.08);
        }
        .tech-tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.65rem;
            background: rgba(139,92,246,0.15);
            border: 1px solid rgba(139,92,246,0.25);
            border-radius: 50px;
            color: #c4b5fd;
            font-size: 0.78rem;
            font-family: 'Outfit', sans-serif;
        }
        .tech-tag-chip button {
            background: none;
            border: none;
            color: #c4b5fd;
            cursor: pointer;
            font-size: 0.75rem;
            padding: 0;
            opacity: 0.7;
        }
        .tech-tag-chip button:hover { opacity: 1; }
        .tech-tags-text-input {
            border: none;
            background: none;
            color: #e2e8f0;
            font-size: 0.85rem;
            outline: none;
            min-width: 120px;
            flex: 1;
            font-family: inherit;
        }
        .tech-tags-text-input::placeholder { color: #475569; }

        /* Project List */
        .project-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .project-item {
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            transition: border-color 0.2s;
            background: rgba(255,255,255,0.02);
        }
        .project-item:hover {
            border-color: rgba(255,255,255,0.1);
        }
        .project-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            flex-shrink: 0;
        }
        .project-info { flex: 1; min-width: 0; }
        .project-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            color: #ffffff;
            font-family: 'Space Grotesk', sans-serif;
        }
        .project-info p {
            margin: 0;
            color: #64748b;
            font-size: 0.82rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .project-meta-badges {
            display: flex;
            gap: 0.35rem;
            margin-top: 0.35rem;
            flex-wrap: wrap;
        }
        .project-meta-badge {
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .project-meta-badge.badge-year {
            background: rgba(14,165,233,0.12);
            color: #38bdf8;
        }
        .project-meta-badge.badge-status {
            background: rgba(34,197,94,0.12);
            color: #4ade80;
        }
        .project-meta-badge.badge-category {
            background: rgba(139,92,246,0.12);
            color: #a78bfa;
        }
        .project-actions {
            display: flex;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        .btn-action {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-action.btn-delete {
            background: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.15);
        }
        .btn-action.btn-delete:hover { background: rgba(239,68,68,0.25); }
        .btn-action.btn-edit {
            background: rgba(14,165,233,0.12);
            color: #38bdf8;
            border: 1px solid rgba(14,165,233,0.15);
        }
        .btn-action.btn-edit:hover { background: rgba(14,165,233,0.25); }
        .btn-action.btn-preview {
            background: rgba(34,197,94,0.12);
            color: #4ade80;
            border: 1px solid rgba(34,197,94,0.15);
        }
        .btn-action.btn-preview:hover { background: rgba(34,197,94,0.25); }

        /* Screenshots Upload */
        .screenshots-upload-area {
            border: 2px dashed rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            color: #475569;
            font-size: 0.85rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        .screenshots-upload-area:hover {
            border-color: rgba(56,189,248,0.3);
            color: #7dd3fc;
        }
        .screenshots-upload-area i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #475569;
        }
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .empty-state p { font-size: 0.9rem; }
    </style>
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
