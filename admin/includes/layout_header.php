<?php
// admin/includes/layout_header.php
$currentPage = basename($_SERVER['PHP_SELF']);
// Determine path offset based on current location
$isRootAdmin = ($currentPage === 'edit_project.php');
$cssPath = $isRootAdmin ? 'assets/css/admin_style.css' : '../assets/css/admin_style.css';
$homePath = $isRootAdmin ? 'pages/overview.php' : 'overview.php';
$managePath = $isRootAdmin ? 'pages/manage_projects.php' : 'manage_projects.php';
$addPath = $isRootAdmin ? 'pages/add_project.php' : 'add_project.php';
$sitePath = $isRootAdmin ? '../index.html' : '../../index.html';
$logoutPath = $isRootAdmin ? 'api/logout.php' : '../api/logout.php';
$usersPath = $isRootAdmin ? 'pages/users.php' : 'users.php';
$sslPath = $isRootAdmin ? 'pages/ssl_certificates.php' : 'ssl_certificates.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - RLabz Admin' : 'Admin Dashboard - RLabz'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?php echo $cssPath; ?>">
</head>
<body>

    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?php echo $homePath; ?>" class="sidebar-logo">
            <div class="sidebar-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="sidebar-logo-text">RLabz</div>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Core</div>
            <a href="<?php echo $homePath; ?>" class="sidebar-link <?php echo $currentPage == 'overview.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i> Overview
            </a>
            
            <div class="sidebar-section-label">Projects</div>
            <a href="<?php echo $managePath; ?>" class="sidebar-link <?php echo ($currentPage == 'manage_projects.php' || $currentPage == 'edit_project.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-folder-open"></i> Manage Projects
            </a>
            <a href="<?php echo $addPath; ?>" class="sidebar-link <?php echo $currentPage == 'add_project.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-plus-circle"></i> Add New Project
            </a>
            <a href="<?php echo $sslPath; ?>" class="sidebar-link <?php echo $currentPage == 'ssl_certificates.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-shield-halved"></i> SSL Certificates
            </a>
            
            <div class="sidebar-section-label">Admin</div>
            <a href="<?php echo $usersPath; ?>" class="sidebar-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users-cog"></i> User Management
            </a>
            
            <div class="sidebar-divider"></div>
            <a href="<?php echo $sitePath; ?>" class="sidebar-link" target="_blank">
                <i class="fa-solid fa-external-link-alt"></i> View Public Site
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name">Administrator</div>
                    <div class="sidebar-user-role">RLabz Admin</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <div class="header-page-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></div>
                    <div class="header-breadcrumb">
                        <i class="fa-solid fa-home"></i> / Admin / <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <a href="<?php echo $logoutPath; ?>" class="btn-header">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <!-- Page Content Starts -->
        <main class="admin-content">
