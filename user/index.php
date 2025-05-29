<?php
global $conn;
require_once '../config.php';


// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Ensure regular user (not admin)
if ($_SESSION['role_id'] == 1) {
    header("Location: ../admin/index.php");
    exit;
}

$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$userId = $row['id'];

$userStats = [];

//get likes
$likes = mysqli_query($conn, "SELECT Count(*)
FROM favorites JOIN tabs ON favorites.tab_id = tabs.id
WHERE tabs.author_id = '$userId'");
$likesQuery = mysqli_fetch_assoc($likes);
$userStats['total_favorites_received'] = $likesQuery['Count(*)'];

// Get user's favorite tabs
$favoritesQuery = mysqli_query($conn, "
    SELECT t.*, u.username as author_name, f.created_at as favorited_at,
           (SELECT COUNT(*) FROM favorites f2 WHERE f2.tab_id = t.id AND f2.user_id = $userId) as is_favorited
    FROM favorites f
    JOIN tabs t ON f.tab_id = t.id
    JOIN users u ON t.author_id = u.id
    WHERE f.user_id = $userId
    ORDER BY f.created_at DESC
    LIMIT 6
");
$favoritesTabs = mysqli_fetch_all($favoritesQuery, MYSQLI_ASSOC);
$userStats['favorites_count'] = count($favoritesTabs);



// Get user's own tabs
$ownTabsQuery = mysqli_query($conn, "
    SELECT t.*, u.username as author_name,
           (SELECT COUNT(*) FROM favorites f WHERE f.tab_id = t.id AND f.user_id = $userId) as is_favorited
    FROM tabs t
    JOIN users u ON t.author_id = u.id
    WHERE t.author_id = $userId
    ORDER BY t.created_at DESC
    LIMIT 6
");
$ownTabs = mysqli_fetch_all($ownTabsQuery, MYSQLI_ASSOC);
$userStats['tabs_created'] = count($ownTabs);
// Get recent popular tabs (excluding user's own and favorites)
$popularTabsQuery = mysqli_query($conn, "
    SELECT t.*, u.username as author_name,
           (SELECT COUNT(*) FROM favorites f WHERE f.tab_id = t.id AND f.user_id = $userId) as is_favorited
    FROM tabs t
    JOIN users u ON t.author_id = u.id
    WHERE t.author_id != $userId
    AND t.id NOT IN (SELECT tab_id FROM favorites WHERE user_id = $userId)
    LIMIT 6
");
$popularTabs = mysqli_fetch_all($popularTabsQuery, MYSQLI_ASSOC);

// Get recent tabs (excluding user's own and favorites)
$recentTabsQuery = mysqli_query($conn, "
    SELECT t.*, u.username as author_name,
           (SELECT COUNT(*) FROM favorites f WHERE f.tab_id = t.id AND f.user_id = $userId) as is_favorited
    FROM tabs t
    JOIN users u ON t.author_id = u.id
    WHERE t.author_id != $userId
    AND t.id NOT IN (SELECT tab_id FROM favorites WHERE user_id = $userId)
    ORDER BY t.created_at DESC
    LIMIT 6
");
$recentTabs = mysqli_fetch_all($recentTabsQuery, MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Guitar Master</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-brown': '#5c3d2e',
                        'dark-brown': '#3e2f1c',
                        'light-brown': '#8b6f47',
                        'warm-orange': '#f97316',
                        'soft-orange': '#fed7aa'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            padding-top: 76px;
            background-color: #fef7ed;
        }

        .sidebar {
            min-height: calc(100vh - 76px);
            transition: all 0.3s;
            background-color: #5c3d2e;
            width: 250px;
            position: fixed;
            top: 76px;
            left: 0;
            bottom: 0;
            z-index: 100;
        }

        .sidebar.collapsed {
            margin-left: -250px;
        }

        .main-content {
            transition: all 0.3s;
            margin-left: 250px;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .nav-link-custom {
            color: #fed7aa !important;
            transition: all 0.3s;
        }

        .nav-link-custom:hover {
            background-color: #3e2f1c !important;
            color: #ffffff !important;
        }

        .nav-link-custom.active {
            background-color: #f97316 !important;
            color: #ffffff !important;
        }

        .tab-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            height: 100%;
        }

        .tab-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }

        .favorite-btn:hover {
            background: white;
            transform: scale(1.1);
        }

        .favorite-btn.favorited {
            color: #dc3545;
        }

        .favorite-btn:not(.favorited) {
            color: #6c757d;
        }

        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .btn-orange {
            background-color: #f97316;
            border-color: #f97316;
            color: white;
        }

        .btn-orange:hover {
            background-color: #ea580c;
            border-color: #ea580c;
            color: white;
        }

        .section-header {
            border-bottom: 3px solid #f97316;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div id="header-container"> <?php include "header.php"?>></div>



<div class="d-flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" id="mainContent">
        <!-- Sidebar Toggle Button -->
        <button class="btn sidebar-toggle mb-4" style="background-color: #5c3d2e; color: white; border: none;" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Welcome Section -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1" style="color: #5c3d2e;">Welcome back, <?= htmlspecialchars($username) ?>!</h2>
            <p class="text-gray-600 mb-0">Here's what's happening with your guitar tabs</p>
        </div>

        <!-- User Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                             style="width: 60px; height: 60px; background-color: #5c3d2e;">
                            <i class="fas fa-music text-white fa-lg"></i>
                        </div>
                        <h3 class="mb-1 fw-bold" style="color: #5c3d2e;"><?= $userStats['tabs_created'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Tabs Created</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                             style="width: 60px; height: 60px; background-color: #f97316;">
                            <i class="fas fa-heart text-white fa-lg"></i>
                        </div>
                        <h3 class="mb-1 fw-bold" style="color: #5c3d2e;"><?= $userStats['favorites_count'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Favorites</p>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                             style="width: 60px; height: 60px; background-color: #dc3545;">
                            <i class="fas fa-thumbs-up text-white fa-lg"></i>
                        </div>
                        <h3 class="mb-1 fw-bold" style="color: #5c3d2e;"><?= number_format($userStats['total_favorites_received'] ?? 0) ?></h3>
                        <p class="text-muted mb-0">Favorites Received</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Favorites Section -->
        <?php if (count($favoritesTabs) > 0): ?>
            <div class="mb-5">
                <div class="section-header">
                    <h3 class="fw-bold mb-0" style="color: #5c3d2e;">
                        <i class="fas fa-heart me-2"></i>My Favorites
                    </h3>
                </div>
                <div class="row">
                    <?php foreach ($favoritesTabs as $tab): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card tab-card shadow-sm position-relative">
                                <!-- Favorite Button -->
                                <a href="toggle_favorite.php?tab_id=<?= $tab['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                                   class="favorite-btn favorited">
                                    <i class="fas fa-heart"></i>
                                </a>

                                <div class="card-body">
                                    <h5 class="card-title mb-2" style="color: #5c3d2e;">
                                        <a href="view_tab.php?id=<?= $tab['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($tab['song_name']) ?>
                                        </a>
                                    </h5>
                                    <h6 class="card-subtitle mb-3 text-muted">
                                        by <?= htmlspecialchars($tab['artist_name']) ?>
                                    </h6>

                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php if (!empty($tab['difficulty'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($tab['difficulty']) ?></span>
                                        <?php endif; ?>

                                        <?php if (!empty($tab['tuning'])): ?>
                                            <span class="badge" style="background-color: #5c3d2e;">
                                                    <?= htmlspecialchars($tab['tuning']) ?>
                                                </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                            <span>
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($tab['author_name']) ?>
                                            </span>
                                        <span>
                                                <i class="fas fa-heart me-1"></i>
                                                Favorited <?= date('M j', strtotime($tab['favorited_at'])) ?>
                                            </span>
                                    </div>

                                    <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-orange w-100">
                                        <i class="fas fa-eye me-2"></i>View Tab
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <a href="favorites.php" class="btn btn-outline-primary">
                        <i class="fas fa-heart me-2"></i>View All Favorites
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- My Tabs Section -->
        <?php if (count($ownTabs) > 0): ?>
            <div class="mb-5">
                <div class="section-header">
                    <h3 class="fw-bold mb-0" style="color: #5c3d2e;">
                        <i class="fas fa-music me-2"></i>My Tabs
                    </h3>
                </div>
                <div class="row">
                    <?php foreach ($ownTabs as $tab): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card tab-card shadow-sm position-relative">
                                <div class="card-body">
                                    <h5 class="card-title mb-2" style="color: #5c3d2e;">
                                        <a href="view_tab.php?id=<?= $tab['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($tab['song_name']) ?>
                                        </a>
                                    </h5>
                                    <h6 class="card-subtitle mb-3 text-muted">
                                        by <?= htmlspecialchars($tab['artist_name']) ?>
                                    </h6>

                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php if (!empty($tab['difficulty'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($tab['difficulty']) ?></span>
                                        <?php endif; ?>

                                        <?php if (!empty($tab['tuning'])): ?>
                                            <span class="badge" style="background-color: #5c3d2e;">
                                                    <?= htmlspecialchars($tab['tuning']) ?>
                                                </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-3"
                                        <span>
                                                <i class="fas fa-heart me-1"></i>
                                                <?= number_format($tab['favorite_count']) ?> favorites
                                            </span>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-orange flex-fill">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <a href="edit_tab.php?id=<?= $tab['id'] ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-music me-2"></i>View All My Tabs
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Popular Tabs Section -->
        <?php if (count($popularTabs) > 0): ?>
            <div class="mb-5">
                <div class="section-header">
                    <h3 class="fw-bold mb-0" style="color: #5c3d2e;">
                        <i class="fas fa-fire me-2"></i>Popular Tabs
                    </h3>
                </div>
                <div class="row">
                    <?php foreach ($popularTabs as $tab): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card tab-card shadow-sm position-relative">
                                <!-- Favorite Button -->
                                <a href="toggle_favorite.php?tab_id=<?= $tab['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                                   class="favorite-btn <?= $tab['is_favorited'] ? 'favorited' : '' ?>">
                                    <i class="fas fa-heart"></i>
                                </a>

                                <div class="card-body">
                                    <h5 class="card-title mb-2" style="color: #5c3d2e;">
                                        <a href="view_tab.php?id=<?= $tab['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($tab['song_name']) ?>
                                        </a>
                                    </h5>
                                    <h6 class="card-subtitle mb-3 text-muted">
                                        by <?= htmlspecialchars($tab['artist_name']) ?>
                                    </h6>

                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php if (!empty($tab['difficulty'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($tab['difficulty']) ?></span>
                                        <?php endif; ?>

                                        <?php if (!empty($tab['tuning'])): ?>
                                            <span class="badge" style="background-color: #5c3d2e;">
                                                    <?= htmlspecialchars($tab['tuning']) ?>
                                                </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                            <span>
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($tab['author_name']) ?>
                                            </span>
                                    </div>

                                    <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-orange w-100">
                                        <i class="fas fa-eye me-2"></i>View Tab
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Tabs Section -->
        <?php if (count($recentTabs) > 0): ?>
            <div class="mb-5">
                <div class="section-header">
                    <h3 class="fw-bold mb-0" style="color: #5c3d2e;">
                        <i class="fas fa-clock me-2"></i>Recent Tabs
                    </h3>
                </div>
                <div class="row">
                    <?php foreach ($recentTabs as $tab): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card tab-card shadow-sm position-relative">
                                <!-- Favorite Button -->
                                <a href="toggle_favorite.php?tab_id=<?= $tab['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                                   class="favorite-btn <?= $tab['is_favorited'] ? 'favorited' : '' ?>">
                                    <i class="fas fa-heart"></i>
                                </a>

                                <div class="card-body">
                                    <h5 class="card-title mb-2" style="color: #5c3d2e;">
                                        <a href="view_tab.php?id=<?= $tab['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($tab['song_name']) ?>
                                        </a>
                                    </h5>
                                    <h6 class="card-subtitle mb-3 text-muted">
                                        by <?= htmlspecialchars($tab['artist_name']) ?>
                                    </h6>

                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php if (!empty($tab['difficulty'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($tab['difficulty']) ?></span>
                                        <?php endif; ?>

                                        <?php if (!empty($tab['tuning'])): ?>
                                            <span class="badge" style="background-color: #5c3d2e;">
                                                    <?= htmlspecialchars($tab['tuning']) ?>
                                                </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                            <span>
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($tab['author_name']) ?>
                                            </span>
                                        <span>
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('M j', strtotime($tab['created_at'])) ?>
                                            </span>
                                    </div>

                                    <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-orange w-100">
                                        <i class="fas fa-eye me-2"></i>View Tab
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <a href="tabs.php" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Explore All Tabs
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (count($favoritesTabs) === 0 && count($ownTabs) === 0 && count($popularTabs) === 0 && count($recentTabs) === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-guitar text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted mb-3">Welcome to Guitar Master!</h4>
                <p class="text-muted mb-4">Start by creating your first tab or exploring tabs from other users.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="edit_tab.php" class="btn btn-orange">
                        <i class="fas fa-plus me-2"></i>Create Your First Tab
                    </a>
                    <a href="tabs.php" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Explore Tabs
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
</script>
</body>
</html>
