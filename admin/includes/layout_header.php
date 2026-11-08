<?php
// admin/includes/layout_header.php
$currentPage = basename($_SERVER['PHP_SELF']);
// Determine path offset based on current location
$isRootAdmin = (basename(dirname($_SERVER['PHP_SELF'])) === 'admin');
$faviconPath = $isRootAdmin ? "../assets/images/rlabz-icon.png?v=2" : "../../assets/images/rlabz-icon.png?v=2";
$cssPath = $isRootAdmin ? 'assets/css/admin_style.css?v=2.3' : '../assets/css/admin_style.css?v=2.3';
$homePath = $isRootAdmin ? 'pages/overview.php' : 'overview.php';
$managePath = $isRootAdmin ? 'pages/manage_projects.php' : 'manage_projects.php';
$addPath = $isRootAdmin ? 'pages/add_project.php' : 'add_project.php';
$sitePath = $isRootAdmin ? '../index.html' : '../../index.html';
$logoutPath = $isRootAdmin ? 'api/logout.php' : '../api/logout.php';
$usersPath = $isRootAdmin ? 'pages/users.php' : 'users.php';
$sslPath = $isRootAdmin ? 'pages/ssl_certificates.php' : 'ssl_certificates.php';
$announcementsPath = $isRootAdmin ? 'pages/announcements.php' : 'announcements.php';

$sidebarUnreadCount = 0;
if (isset($pdo) && $pdo) {
    try {
        $sidebarUnreadCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_announcements WHERE is_read = 0")->fetchColumn();
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - RLabz Admin' : 'Admin Dashboard - RLabz'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $faviconPath; ?>">
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
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding: 0.6rem 0.75rem;">
            <a href="<?php echo $homePath; ?>" class="sidebar-logo" style="justify-content: flex-start; padding: 0; border-bottom: none;">
                <?php $logoWhitePath = $isRootAdmin ? "../assets/images/rlabz-logo-white.png" : "../../assets/images/rlabz-logo-white.png"; ?>
                <img src="<?php echo $logoWhitePath; ?>" alt="RLabz Logo" style="width: 200px; max-width: 100%; height: auto; object-fit: contain; filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.12)); margin-left: -15px;">
            </a>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close Sidebar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Core</div>
            <a href="<?php echo $homePath; ?>" class="sidebar-link <?php echo $currentPage == 'overview.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i> Overview
            </a>
            <a href="<?php echo $announcementsPath; ?>" class="sidebar-link <?php echo $currentPage == 'announcements.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> Announcements
                <?php if($sidebarUnreadCount > 0): ?>
                    <span style="margin-left: auto; background: #f59e0b; color: #ffffff; font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 9999px;"><?php echo $sidebarUnreadCount; ?></span>
                <?php endif; ?>
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
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <?php if ($currentPage !== 'overview.php' && $currentPage !== 'dashboard.php'): ?>
                <button type="button" class="btn-back" onclick="if(window.history.length > 1 && document.referrer){ window.history.back(); } else { window.location.href='<?php echo $homePath; ?>'; }" title="Go back" aria-label="Go back">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <?php endif; ?>
                <div class="header-title-box">
                    <div class="header-page-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></div>
                    <div class="header-breadcrumb">
                        <i class="fa-solid fa-home"></i> / Admin / <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <a href="<?php echo $logoutPath; ?>" class="btn-header btn-logout" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Logout</span>
                </a>
            </div>
        </header>

        <!-- Page Content Starts -->
        <main class="admin-content">
