<?php
global $conn;
require_once '../config.php';

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Enhanced search and filter functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$difficulty = isset($_GET['difficulty']) ? mysqli_real_escape_string($conn, $_GET['difficulty']) : '';
$tuning = isset($_GET['tuning']) ? mysqli_real_escape_string($conn, $_GET['tuning']) : '';
$artist = isset($_GET['artist']) ? mysqli_real_escape_string($conn, $_GET['artist']) : '';
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'newest';

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereConditions = [];

if (!empty($search)) {
    $whereConditions[] = "(t.song_name LIKE '%$search%' OR t.artist_name LIKE '%$search%' OR u.username LIKE '%$search%')";
}

if (!empty($difficulty)) {
    $whereConditions[] = "t.difficulty = '$difficulty'";
}

if (!empty($tuning)) {
    $whereConditions[] = "t.tuning = '$tuning'";
}

if (!empty($artist)) {
    $whereConditions[] = "t.artist_name LIKE '%$artist%'";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Build ORDER BY clause
$orderClause = '';
switch ($sort) {
    case 'newest':
        $orderClause = 'ORDER BY t.created_at DESC';
        break;
    case 'oldest':
        $orderClause = 'ORDER BY t.created_at ASC';
        break;
    case 'favorites':
        $orderClause = 'ORDER BY t.favorite_count DESC';
        break;
    case 'alphabetical':
        $orderClause = 'ORDER BY t.song_name ASC';
        break;
    case 'artist':
        $orderClause = 'ORDER BY t.artist_name ASC, t.song_name ASC';
        break;
    default:
        $orderClause = 'ORDER BY t.created_at DESC';
}

// Fetch tabs with author information and favorite status
$tabsQuery = "SELECT t.*, u.username as author_name,
              (SELECT COUNT(*) FROM favorites f WHERE f.tab_id = t.id AND f.user_id = $userId) as is_favorited
              FROM tabs t 
              JOIN users u ON t.author_id = u.id 
              $whereClause
              $orderClause
              LIMIT $limit OFFSET $offset";
$tabsResult = mysqli_query($conn, $tabsQuery);
$tabs = mysqli_fetch_all($tabsResult, MYSQLI_ASSOC);

// Count total tabs for pagination
$countQuery = "SELECT COUNT(*) as total FROM tabs t JOIN users u ON t.author_id = u.id $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalTabs = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalTabs / $limit);

// Get filter options
$difficultiesQuery = mysqli_query($conn, "SELECT DISTINCT difficulty FROM tabs WHERE difficulty IS NOT NULL AND difficulty != '' ORDER BY FIELD(difficulty, 'Beginner', 'Intermediate', 'Advanced')");
$difficulties = mysqli_fetch_all($difficultiesQuery, MYSQLI_ASSOC);

$tuningsQuery = mysqli_query($conn, "SELECT DISTINCT tuning FROM tabs WHERE tuning IS NOT NULL AND tuning != '' ORDER BY tuning");
$tunings = mysqli_fetch_all($tuningsQuery, MYSQLI_ASSOC);

// Get popular artists for filtering
$artistsQuery = mysqli_query($conn, "SELECT DISTINCT artist_name FROM tabs WHERE artist_name IS NOT NULL AND artist_name != '' ORDER BY artist_name LIMIT 20");
$artists = mysqli_fetch_all($artistsQuery, MYSQLI_ASSOC);

// Check if any filters are active
$hasActiveFilters = !empty($search) || !empty($difficulty) || !empty($tuning) || !empty($artist);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guitar Tabs - Guitar Master Admin</title>
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

        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background-color: #5c3d2e;
            border-color: #5c3d2e;
        }

        .btn-primary:hover {
            background-color: #3e2f1c;
            border-color: #3e2f1c;
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

        .form-control:focus, .form-select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.25);
        }

        .quick-filter-btn {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #5c3d2e;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.2s;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .quick-filter-btn:hover {
            background-color: #5c3d2e;
            color: white;
            text-decoration: none;
        }

        .quick-filter-btn.active {
            background-color: #f97316;
            color: white;
            border-color: #f97316;
        }

        .filter-badge {
            background-color: #f97316;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            margin-left: 0.5rem;
        }

        .artist-filter-btn {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #5c3d2e;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.2s;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            display: inline-block;
            font-size: 0.875rem;
        }

        .artist-filter-btn:hover {
            background-color: #5c3d2e;
            color: white;
        }

        .artist-filter-btn.active {
            background-color: #f97316;
            color: white;
            border-color: #f97316;
        }
    </style>
</head>
<body>
<div id="header-container"> <?php include "header.php"; ?>></div>


<div class="d-flex">
    <?php include "sidebar.php" ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" id="mainContent">
        <!-- Sidebar Toggle Button -->
        <button class="btn sidebar-toggle mb-4" style="background-color: #5c3d2e; color: white; border: none;" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="color: #5c3d2e;">Guitar Tabs</h2>
                <p class="text-gray-600 mb-0">Browse and manage all guitar tabs</p>
            </div>
            <a href="edit_tab.php" class="btn btn-orange">
                <i class="fas fa-plus me-2"></i>Create New Tab
            </a>
        </div>

        <!-- Quick Filters -->
        <div class="mb-3">
            <div class="d-flex flex-wrap">
                <a href="?sort=newest" class="quick-filter-btn <?= $sort === 'newest' ? 'active' : '' ?>">
                    <i class="fas fa-clock me-1"></i>Latest
                </a>
                <a href="?sort=favorites" class="quick-filter-btn <?= $sort === 'favorites' ? 'active' : '' ?>">
                    <i class="fas fa-heart me-1"></i>Most Loved
                </a>
                <a href="?difficulty=Beginner" class="quick-filter-btn <?= $difficulty === 'Beginner' ? 'active' : '' ?>">
                    <i class="fas fa-star me-1"></i>Beginner
                </a>
                <a href="?difficulty=Intermediate" class="quick-filter-btn <?= $difficulty === 'Intermediate' ? 'active' : '' ?>">
                    <i class="fas fa-star-half-alt me-1"></i>Intermediate
                </a>
                <a href="?difficulty=Advanced" class="quick-filter-btn <?= $difficulty === 'Advanced' ? 'active' : '' ?>">
                    <i class="fas fa-star me-1"></i>Advanced
                </a>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">
                        <i class="fas fa-search me-1"></i>Search
                    </label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Song name, artist, or author..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label for="difficulty" class="form-label">
                        <i class="fas fa-star me-1"></i>Difficulty
                    </label>
                    <select class="form-select" id="difficulty" name="difficulty">
                        <option value="">All Levels</option>
                        <?php foreach ($difficulties as $diff): ?>
                            <option value="<?= htmlspecialchars($diff['difficulty']) ?>"
                                <?= $difficulty === $diff['difficulty'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($diff['difficulty']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tuning" class="form-label">
                        <i class="fas fa-guitar me-1"></i>Tuning
                    </label>
                    <select class="form-select" id="tuning" name="tuning">
                        <option value="">All Tunings</option>
                        <?php foreach ($tunings as $tun): ?>
                            <option value="<?= htmlspecialchars($tun['tuning']) ?>"
                                <?= $tuning === $tun['tuning'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tun['tuning']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="artist" class="form-label">
                        <i class="fas fa-microphone me-1"></i>Artist
                    </label>
                    <input type="text" class="form-control" id="artist" name="artist"
                           placeholder="Filter by artist..."
                           value="<?= htmlspecialchars($artist) ?>"
                           list="artistList">
                    <datalist id="artistList">
                        <?php foreach ($artists as $art): ?>
                        <option value="<?= htmlspecialchars($art['artist_name']) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label for="sort" class="form-label">
                        <i class="fas fa-sort me-1"></i>Sort By
                    </label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="favorites" <?= $sort === 'favorites' ? 'selected' : '' ?>>Most Loved</option>
                        <option value="alphabetical" <?= $sort === 'alphabetical' ? 'selected' : '' ?>>A-Z</option>
                        <option value="artist" <?= $sort === 'artist' ? 'selected' : '' ?>>By Artist</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Search
                    </button>

                    <?php if ($hasActiveFilters): ?>
                        <a href="explore.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Popular Artists Quick Filters -->
            <?php if (count($artists) > 0): ?>
                <div class="mt-3">
                    <label class="form-label d-block">
                        <i class="fas fa-microphone me-1"></i>Popular Artists:
                    </label>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($artists as $index => $art): ?>
                            <?php if ($index < 10): ?>
                                <a href="?artist=<?= urlencode($art['artist_name']) ?>"
                                   class="artist-filter-btn <?= $artist === $art['artist_name'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($art['artist_name']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Filters Display -->
        <?php if ($hasActiveFilters): ?>
            <div class="mb-3">
                <div class="d-flex flex-wrap align-items-center">
                    <span class="me-2 text-muted">Active filters:</span>

                    <?php if (!empty($search)): ?>
                        <span class="badge bg-primary me-2 mb-1">
                                Search: "<?= htmlspecialchars($search) ?>"
                            </span>
                    <?php endif; ?>

                    <?php if (!empty($difficulty)): ?>
                        <span class="badge bg-secondary me-2 mb-1">
                                Difficulty: <?= htmlspecialchars($difficulty) ?>
                            </span>
                    <?php endif; ?>

                    <?php if (!empty($tuning)): ?>
                        <span class="badge bg-info me-2 mb-1">
                                Tuning: <?= htmlspecialchars($tuning) ?>
                            </span>
                    <?php endif; ?>

                    <?php if (!empty($artist)): ?>
                        <span class="badge bg-warning me-2 mb-1">
                                Artist: <?= htmlspecialchars($artist) ?>
                            </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Results Info -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
                <i class="fas fa-music me-1"></i>
                Showing <?= count($tabs) ?> of <?= number_format($totalTabs) ?> tabs
                <?php if (!empty($search)): ?>
                    for "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
            </p>
        </div>

        <!-- Tabs List -->
        <?php if (count($tabs) > 0): ?>
            <div class="row">
                <?php foreach ($tabs as $tab): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm h-100 tab-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0 fw-bold" style="color: #5c3d2e;">
                                        <?= htmlspecialchars($tab['song_name']) ?>
                                    </h5>
                                    <?php if (!empty($tab['difficulty'])): ?>
                                        <span class="difficulty-badge difficulty-<?= strtolower($tab['difficulty']) ?>">
                                                <?= htmlspecialchars($tab['difficulty']) ?>
                                            </span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="card-subtitle mb-3 text-muted">
                                    by <?= htmlspecialchars($tab['artist_name']) ?>
                                </h6>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php if (!empty($tab['tuning'])): ?>
                                        <span class="badge bg-secondary">
                                                <i class="fas fa-guitar me-1"></i>
                                                <?= htmlspecialchars($tab['tuning']) ?>
                                            </span>
                                    <?php endif; ?>

                                    <?php if (!empty($tab['capo'])): ?>
                                        <span class="badge" style="background-color: #f97316;">
                                                <i class="fas fa-grip-lines-vertical me-1"></i>
                                                Capo: <?= htmlspecialchars($tab['capo']) ?>
                                            </span>
                                    <?php endif; ?>
                                </div>

                                <p class="card-text small text-muted mb-3">
                                    Added by <?= htmlspecialchars($tab['author_name']) ?> on
                                    <?= date('M d, Y', strtotime($tab['created_at'])) ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0 pt-0">
                                <div class="d-flex justify-content-between">
                                    <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1"></i> View Tab
                                    </a>
                                    <?php if ($tab['author_id'] == $_SESSION['user_id'] || $_SESSION['role_id'] == 1): ?>
                                        <a href="edit_tab.php?id=<?= $tab['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-guitar text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-muted">No tabs found</h4>
                    <?php if (!empty($search)): ?>
                        <p class="text-muted">No tabs match your search criteria.</p>
                        <a href="tabs.php" class="btn btn-primary mt-2">Clear Search</a>
                    <?php else: ?>
                        <p class="text-muted">Start by creating your first guitar tab.</p>
                        <a href="edit_tab.php" class="btn btn-orange mt-2">
                            <i class="fas fa-plus me-2"></i>Create New Tab
                        </a>
                    <?php endif; ?>
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