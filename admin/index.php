<?php
global $conn;
require_once '../config.php';


// Ensure only admin can access
if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

$username = $_SESSION['username'];

// Get real statistics from database
$stats = [];

// Total users
$userCountQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$stats['total_users'] = mysqli_fetch_assoc($userCountQuery)['count'];

// Total tabs
$tabCountQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM tabs");
$stats['total_tabs'] = mysqli_fetch_assoc($tabCountQuery)['count'];

//// Total views
//$viewCountQuery = mysqli_query($conn, "SELECT SUM(view_count) as count FROM tabs");
//$stats['total_views'] = mysqli_fetch_assoc($viewCountQuery)['count'] ?? 0;


// Recent tabs (last 7 days)
$recentTabsQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM tabs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['recent_tabs'] = mysqli_fetch_assoc($recentTabsQuery)['count'];

// New users (last 7 days)
$newUsersQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_users'] = mysqli_fetch_assoc($newUsersQuery)['count'];

// Get user role distribution
$adminCountQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role_id = 1");
$regularCountQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role_id = 2");
$adminCount = mysqli_fetch_assoc($adminCountQuery)['count'];
$regularCount = mysqli_fetch_assoc($regularCountQuery)['count'];

// Get recent activity
$recentTabsListQuery = mysqli_query($conn, "
    SELECT t.*, u.username as author_name 
    FROM tabs t 
    JOIN users u ON t.author_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$recentTabsList = mysqli_fetch_all($recentTabsListQuery, MYSQLI_ASSOC);

$recentUsersQuery = mysqli_query($conn, "
    SELECT username, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentUsersList = mysqli_fetch_all($recentUsersQuery, MYSQLI_ASSOC);

// Get monthly tab creation data for chart
$monthlyDataQuery = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM tabs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$monthlyData = mysqli_fetch_all($monthlyDataQuery, MYSQLI_ASSOC);

// Get top artists
$topArtistsQuery = mysqli_query($conn, "
    SELECT artist_name, COUNT(*) as tab_count
    FROM tabs 
    GROUP BY artist_name 
    ORDER BY tab_count DESC 
    LIMIT 5
");
$topArtists = mysqli_fetch_all($topArtistsQuery, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Guitar Master</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
<div id="header-container"> <?php include "header.php"?>></div>

<div class="d-flex">

    <?php include "sidebar.php"?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" id="mainContent">
        <!-- Sidebar Toggle Button -->
        <button class="btn sidebar-toggle mb-4" style="background-color: #5c3d2e; color: white; border: none;" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="color: #5c3d2e;">Admin Dashboard</h2>
                <p class="text-gray-600 mb-0">Welcome back! Here's what's happening with Guitar Master.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; background-color: #5c3d2e;">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 fw-bold" style="color: #5c3d2e;"><?= number_format($stats['total_users']) ?></h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; background-color: #f97316;">
                                    <i class="fas fa-music text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0 fw-bold" style="color: #5c3d2e;"><?= number_format($stats['total_tabs']) ?></h3>
                                <p class="text-muted mb-0">Guitar Tabs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Charts and Data -->
        <div class="row mb-4">
            <!-- Monthly Tab Creation Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-chart-line me-2"></i>Tab Creation Over Time
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Distribution Pie Chart -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-chart-pie me-2"></i>User Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <!-- Recent Tabs -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-music me-2"></i>Recent Tabs
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($recentTabsList) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentTabsList as $tab): ?>
                                    <div class="list-group-item border-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-semibold">
                                                    <a href="view_tab.php?id=<?= $tab['id'] ?>" style="color: #5c3d2e; text-decoration: none;">
                                                        <?= htmlspecialchars($tab['song_name']) ?>
                                                    </a>
                                                </h6>
                                                <p class="mb-1 text-muted">by <?= htmlspecialchars($tab['artist_name']) ?></p>
                                                <small class="text-muted">
                                                    Created by <?= htmlspecialchars($tab['author_name']) ?> •
                                                    <?= date('M j, Y', strtotime($tab['created_at'])) ?>
                                                </small>
                                            </div>
                                            <?php if (!empty($tab['difficulty'])): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($tab['difficulty']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-music text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted">No tabs created yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Artists -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-star me-2"></i>Top Artists
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($topArtists) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topArtists as $index => $artist): ?>
                                    <div class="list-group-item border-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                                     style="width: 30px; height: 30px; background-color: #f97316; color: white; font-size: 0.8rem;">
                                                    <?= $index + 1 ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($artist['artist_name']) ?></h6>
                                                </div>
                                            </div>
                                            <span class="badge" style="background-color: #5c3d2e;">
                                                    <?= $artist['tab_count'] ?> tabs
                                                </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted">No artist data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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

    // Monthly Tab Creation Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = <?= json_encode($monthlyData) ?>;

    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Tabs Created',
                data: monthlyData.map(item => item.count),
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // User Distribution Pie Chart
    const userCtx = document.getElementById('userChart').getContext('2d');

    new Chart(userCtx, {
        type: 'doughnut',
        data: {
            labels: ['Regular Users', 'Administrators'],
            datasets: [{
                data: [<?= $regularCount ?>, <?= $adminCount ?>],
                backgroundColor: ['#f97316', '#5c3d2e'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
</body>
</html>