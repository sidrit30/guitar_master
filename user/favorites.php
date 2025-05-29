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
    header("Location: admin/index.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch favorited tabs
$favoritesQuery = "SELECT t.*, u.username as author_name, f.created_at as favorited_at
                   FROM favorites f
                   JOIN tabs t ON f.tab_id = t.id
                   JOIN users u ON t.author_id = u.id
                   WHERE f.user_id = $userId
                   ORDER BY f.created_at DESC
                   LIMIT $limit OFFSET $offset";
$favoritesResult = mysqli_query($conn, $favoritesQuery);
$favorites = mysqli_fetch_all($favoritesResult, MYSQLI_ASSOC);

// Count total favorites for pagination
$countQuery = "SELECT COUNT(*) as total FROM favorites WHERE user_id = $userId";
$countResult = mysqli_query($conn, $countQuery);
$totalFavorites = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalFavorites / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Guitar Master</title>
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
            color: #dc3545;
        }

        .favorite-btn:hover {
            background: white;
            transform: scale(1.1);
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

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="color: #5c3d2e;">
                    <i class="fas fa-heart me-2" style="color: #dc3545;"></i>My Favorites
                </h2>
                <p class="text-gray-600 mb-0">Your collection of favorite guitar tabs</p>
            </div>
            <div>
                    <span class="badge" style="background-color: #5c3d2e; font-size: 1rem;">
                        <?= number_format($totalFavorites) ?> favorites
                    </span>
            </div>
        </div>

        <!-- Favorites Grid -->
        <?php if (count($favorites) > 0): ?>
            <div class="row">
                <?php foreach ($favorites as $tab): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card tab-card shadow-sm position-relative">
                            <!-- Favorite Button (Always favorited in this view) -->
                            <button class="favorite-btn" onclick="removeFavorite(<?= $tab['id'] ?>, this)">
                                <i class="fas fa-heart"></i>
                            </button>

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

                                    <?php if (!empty($tab['capo'])): ?>
                                        <span class="badge" style="background-color: #f97316;">
                                                Capo <?= htmlspecialchars($tab['capo']) ?>
                                            </span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                        <span>
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($tab['author_name']) ?>
                                        </span>
                                    <span>
                                            <i class="fas fa-heart me-1" style="color: #dc3545;"></i>
                                            <?= date('M j, Y', strtotime($tab['favorited_at'])) ?>
                                        </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                        <span>
                                            <i class="fas fa-eye me-1"></i>
                                            <?= number_format($tab['view_count']) ?> views
                                        </span>
                                    <span>
                                            <i class="fas fa-heart me-1"></i>
                                            <?= number_format($tab['favorite_count']) ?> favorites
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Favorites pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <!-- No Favorites -->
            <div class="text-center py-5">
                <i class="fas fa-heart text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted mb-3">No favorites yet</h4>
                <p class="text-muted mb-4">
                    Start exploring tabs and click the heart icon to add them to your favorites!
                </p>
                <a href="tabs.php" class="btn btn-orange">
                    <i class="fas fa-compass me-2"></i>Explore Tabs
                </a>
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

    // Remove favorite
    function removeFavorite(tabId, button) {
        if (confirm('Remove this tab from your favorites?')) {
            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ tab_id: tabId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the card from the view
                        const card = button.closest('.col-lg-4');
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();

                            // Check if no more favorites
                            const remainingCards = document.querySelectorAll('.tab-card').length;
                            if (remainingCards === 0) {
                                location.reload();
                            }
                        }, 300);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing favorite.');
                });
        }
    }
</script>
</body>
</html>