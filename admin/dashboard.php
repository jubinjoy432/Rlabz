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
            'image_path' => 'images/rz-logo.webp'
        ],
        [
            'id' => 2,
            'title' => 'Example Project 2',
            'year' => '2023',
            'description' => 'Another preview item. Imagine your real uploaded images here.',
            'image_path' => 'images/rz-logo.webp'
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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
            color: #334155;
            min-height: 100vh;
        }
        .navbar {
            background: #0f172a;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
        }
        .btn-logout {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-logout:hover { color: white; }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #475569;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-help { font-size: 0.8rem; color: #64748b; margin-top: 0.2rem; display: block; }
        
        .btn-submit {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #1d4ed8; }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .project-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .project-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .project-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            background: #f1f5f9;
        }
        .project-info { flex: 1; }
        .project-info h3 { margin: 0 0 0.25rem 0; font-size: 1.1rem; }
        .project-info p { margin: 0; color: #64748b; font-size: 0.9rem; }
        .project-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .btn-delete:hover { background: #dc2626; }
        
        /* Team Members UI */
        .team-members-container {
            border: 1px solid #cbd5e1;
            padding: 1rem;
            border-radius: 6px;
            background: #f8fafc;
            margin-bottom: 1rem;
        }
        .team-member-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #cbd5e1;
        }
        .team-member-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .btn-add-member {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .btn-remove-member {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <h1>RLabz Admin</h1>
        <a href="api/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>

    <div class="container">
        <!-- Add Project Form -->
        <div class="card">
            <h2>Add New Project</h2>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Project added successfully!</div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form action="api/add_project.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Project Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. Euphoria 2024">
                </div>
                
                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="text" id="year" name="year" required placeholder="e.g. 2024">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required placeholder="Project description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="tech_stack">Tech Stack</label>
                    <input type="text" id="tech_stack" name="tech_stack" placeholder="e.g. React, Node.js, AWS">
                    <span class="form-help">Comma separated list</span>
                </div>
                
                <div class="form-group">
                    <label>Team Members</label>
                    <div class="team-members-container" id="team-members-container">
                        <div class="team-member-row">
                            <input type="text" name="member_names[]" placeholder="Member Name" required style="flex: 1;">
                            <input type="file" name="member_photos[]" accept="image/*" required style="flex: 1;">
                            <button type="button" class="btn-remove-member" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-member" onclick="addMemberRow()"><i class="fas fa-plus"></i> Add Another Member</button>
                </div>
                
                <div class="form-group">
                    <label for="image">Project Cover Image</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    <span class="form-help">Max size 2MB. Recommended format: WebP or JPG.</span>
                </div>
                
                <button type="submit" class="btn-submit">Add Project</button>
            </form>
        </div>

        <!-- Project List -->
        <div class="card">
            <h2>Existing Projects</h2>
            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(empty($projects)): ?>
                <p style="color: #64748b;">No projects found in the database. Add one to get started!</p>
            <?php else: ?>
                <ul class="project-list">
                    <?php foreach($projects as $p): ?>
                    <li class="project-item">
                        <img src="../<?php echo htmlspecialchars($p['image_path']); ?>" alt="Project image" class="project-img" onerror="this.src='../images/rz-logo.webp'">
                        <div class="project-info">
                            <h3><?php echo htmlspecialchars($p['title']); ?> (<?php echo htmlspecialchars($p['year']); ?>)</h3>
                            <p><?php echo htmlspecialchars(substr($p['description'], 0, 80)); ?>...</p>
                        </div>
                        <div class="project-actions">
                            <a href="api/delete_project.php?id=<?php echo $p['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this project?');"><i class="fas fa-trash"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function addMemberRow() {
            const container = document.getElementById('team-members-container');
            const row = document.createElement('div');
            row.className = 'team-member-row';
            row.innerHTML = `
                <input type="text" name="member_names[]" placeholder="Member Name" required style="flex: 1;">
                <input type="file" name="member_photos[]" accept="image/*" required style="flex: 1;">
                <button type="button" class="btn-remove-member" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>
