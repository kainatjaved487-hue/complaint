<?php
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/functions.php';

// 1. Fetch System Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Identify Current Page & Security Check
$current_url = substr($_SERVER['SCRIPT_NAME'], strlen('/complaint/')); // Adjust offset
// Clean URL for DB matching (assuming DB stores relative paths)
$db_url_match = $current_url; 
// If your script is in a folder, the DB url should match "dashboards/super_admin/file.php"

// Fetch Page Info
$pageStmt = $pdo->prepare("SELECT * FROM sys_pages WHERE page_url LIKE ? LIMIT 1");
$pageStmt->execute(["%$current_url%"]); 
$currentPageData = $pageStmt->fetch();

$pageTitle = $currentPageData['page_name'] ?? 'Dashboard';
$pageId = $currentPageData['id'] ?? 0;

// 3. Security Access Check (The Gatekeeper)
if ($pageId > 0 && $_SESSION['role'] !== 'super_admin') {
    $accessStmt = $pdo->prepare("SELECT * FROM role_access WHERE role_key = ? AND page_id = ?");
    $accessStmt->execute([$_SESSION['role'], $pageId]);
    if ($accessStmt->rowCount() == 0) {
        die('<div class="alert alert-danger m-5">⛔ Access Denied: You do not have permission to view this page.</div>');
    }
}

// 4. Breadcrumb Logic (Recursive Upwards)
$breadcrumbs = [];
if ($currentPageData) {
    $crumbId = $currentPageData['id'];
    while($crumbId != 0) {
        $crumbStmt = $pdo->prepare("SELECT id, parent_id, page_name, page_url FROM sys_pages WHERE id = ?");
        $crumbStmt->execute([$crumbId]);
        $crumb = $crumbStmt->fetch();
        array_unshift($breadcrumbs, $crumb); // Add to beginning
        $crumbId = $crumb['parent_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($settings['system_name']) ?></title>
    
    <script>
        // Immediately check local storage to prevent "White Flash"
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else {
            // Default to system preference if no choice made
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemTheme);
        }
    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/adminlte.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style> 
        .app-brand-logo { height: 30px; width: auto; } 
        .user-image { width: 30px; height: 30px; object-fit: cover; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"> <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a> </li>
                <li class="nav-item d-none d-md-block"> <a href="#" class="nav-link"><?= $pageTitle ?></a> </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <!-- Notifications Dropdown Menu -->
                <?php 
                $unreadCount = getUnreadCount($_SESSION['user_id']);
                $recentNotes = getRecentNotifications($_SESSION['user_id']);
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <i class="bi bi-bell-fill"></i>
                        <?php if($unreadCount > 0): ?>
                            <span class="navbar-badge badge text-bg-danger rounded-circle"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <span class="dropdown-item dropdown-header"><?= $unreadCount ?> Notifications</span>
                        <div class="dropdown-divider"></div>
                        <?php if(count($recentNotes) > 0): ?>
                            <?php foreach($recentNotes as $note): ?>
                                <a href="<?= BASE_URL . ($note['link'] ?? '#') ?>" class="dropdown-item">
                                    <i class="bi bi-info-circle-fill me-2 <?= $note['is_read'] ? 'text-muted' : 'text-primary' ?>"></i> 
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;"><?= htmlspecialchars($note['title']) ?></span>
                                    <span class="float-end text-muted text-sm"><?= date('H:i', strtotime($note['created_at'])) ?></span>
                                </a>
                                <div class="dropdown-divider"></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="#" class="dropdown-item text-center">No new notifications</a>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>notifications.php" class="dropdown-item dropdown-footer">See All Notifications</a>
                    </div>
                </li>

                 <li class="nav-item">
                    <button class="btn btn-link nav-link" id="theme-toggle" type="button">
                        <i class="bi bi-sun-fill" id="theme-icon"></i>
                    </button>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= !empty($_SESSION['avatar']) ? BASE_URL . $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="user-image rounded-circle shadow" alt="User Image">
                        <span class="d-none d-md-inline ms-1"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="<?= !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="rounded-circle shadow" alt="User Image">
                            <p>
                                <?= htmlspecialchars($_SESSION['name']) ?>
                                <small><?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?></small>
                            </p>
                        </li>
                        <li class="user-footer"> 
                            <a href="<?= BASE_URL ?>profile.php" class="btn btn-default btn-flat">Profile</a>
                            <a href="<?= BASE_URL ?>logout.php" class="btn btn-default btn-flat float-end">Sign out</a> 
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <?php include 'sidebar.php'; ?>
    
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?= $pageTitle ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                            <?php foreach($breadcrumbs as $b): ?>
                                <li class="breadcrumb-item <?= ($b['id'] == $pageId) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($b['page_name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">