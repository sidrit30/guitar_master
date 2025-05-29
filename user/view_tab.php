<?php
global $conn;
require_once '../config.php';

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Get tab ID from URL
if (!isset($_GET['id'])) {
    header("Location: tabs.php");
    exit;
}

$tabId = (int)$_GET['id'];
$username = $_SESSION['username'];

// Fetch tab details
$tabQuery = mysqli_query($conn, "SELECT t.*, u.username as author_name 
                                FROM tabs t 
                                JOIN users u ON t.author_id = u.id 
                                WHERE t.id = $tabId");

if (mysqli_num_rows($tabQuery) === 0) {
    header("Location: tabs.php?error=tab-not-found");
    exit;
}

$tab = mysqli_fetch_assoc($tabQuery);

if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];

    $query = "DELETE FROM favorites WHERE tab_id = $deleteId";
    mysqli_query($conn, $query);

    $query = "DELETE FROM tabs WHERE id = '$deleteId' ";
    $result = mysqli_query($conn, $query);
    header('Location: users.php?deleted=1');
    exit;
}


// Get tab content from file
$tabContent = "";
$tabFilePath = "../uploads/tabs/{$tab['file_path']}";
if (file_exists($tabFilePath)) {
    $tabContent = file_get_contents($tabFilePath);
} else {
    $tabContent = "Tab file not found.";
}

// Process tab content to identify sections
$sections = [];
$currentSection = [
    'type' => 'tab',
    'content' => []
];

$lines = explode("\n", $tabContent);
foreach ($lines as $line) {
    // Check if line is a section header (enclosed in [])
    if (preg_match('/^\s*\[(.*?)\]\s*$/', $line, $matches)) {
        // Save previous section if not empty
        if (!empty($currentSection['content'])) {
            $sections[] = $currentSection;
        }

        $sectionType = strtolower($matches[1]);
        // Determine section type
        if (str_contains($sectionType, 'chord')) {
            $currentSection = ['type' => 'chord', 'content' => []];
        } elseif (str_contains($sectionType, 'lyric') || str_contains($sectionType, 'verse')) {
            $currentSection = ['type' => 'lyrics', 'content' => []];
        } elseif (str_contains($sectionType, 'tab')) {
            $currentSection = ['type' => 'tab', 'content' => []];
        } else {
            $currentSection = ['type' => 'comment', 'title' => $matches[1], 'content' => []];
        }
    } else {
        $currentSection['content'][] = $line;
    }
}

// Add the last section
if (!empty($currentSection['content'])) {
    $sections[] = $currentSection;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tab['song_name']) ?> by <?= htmlspecialchars($tab['artist_name']) ?> - Guitar Tab</title>
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

        /* Tab display styles */
        .tab-container {
            font-family: 'Courier New', monospace;
            white-space: pre;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.2;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
        }

        .tab-section {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .tab-section.tab {
            background-color: #f8f9fa;
            border-left: 4px solid #5c3d2e;
        }

        .tab-section.chord {
            background-color: #e8f4f8;
            border-left: 4px solid #0d6efd;
        }

        .tab-section.lyrics {
            background-color: #f8f0e8;
            border-left: 4px solid #f97316;
            font-family: Arial, sans-serif;
            white-space: pre-wrap;
        }

        .tab-section.comment {
            background-color: #f0f8e8;
            border-left: 4px solid #198754;
            white-space: pre-wrap;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-family: Arial, sans-serif;
            color: #5c3d2e;
        }

        .chord-diagram {
            font-family: 'Courier New', monospace;
            white-space: pre;
        }

        .tab-meta {
            margin-bottom: 20px;
        }

        .tab-actions {
            position: sticky;
            bottom: 20px;
            text-align: right;
            z-index: 10;
        }

        .tab-actions .btn {
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .btn-edit {
            background-color: #5c3d2e;
            color: white;
        }

        .btn-edit:hover {
            background-color: #3e2f1c;
            color: white;
        }

        .btn-print {
            background-color: #f97316;
            color: white;
        }

        .btn-print:hover {
            background-color: #ea580c;
            color: white;
        }
    </style>
</head>
<body>


<div id="header-container"> <?php include "header.php"?>></div>

<div class="d-flex">
    <?php include "sidebar.php" ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" id="mainContent">
        <!-- Sidebar Toggle Button -->
        <button class="btn sidebar-toggle mb-4" style="background-color: #5c3d2e; color: white; border: none;" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" style="color: #5c3d2e;">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="tabs.php" style="color: #5c3d2e;">Tabs</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($tab['song_name']) ?></li>
            </ol>
        </nav>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> Tab deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tab Meta Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mb-1" style="color: #5c3d2e;"><?= htmlspecialchars($tab['song_name']) ?></h2>
                        <h5 class="text-muted mb-3">by <?= htmlspecialchars($tab['artist_name']) ?></h5>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php if (!empty($tab['difficulty'])): ?>
                                <span class="badge bg-secondary">
                                        <i class="fas fa-star me-1"></i>
                                        <?= htmlspecialchars($tab['difficulty']) ?>
                                    </span>
                            <?php endif; ?>

                            <?php if (!empty($tab['tuning'])): ?>
                                <span class="badge" style="background-color: #5c3d2e;">
                                        <i class="fas fa-guitar me-1"></i>
                                        Tuning: <?= htmlspecialchars($tab['tuning']) ?>
                                    </span>
                            <?php endif; ?>

                            <?php if (!empty($tab['capo'])): ?>
                                <span class="badge" style="background-color: #f97316;">
                                        <i class="fas fa-grip-lines-vertical me-1"></i>
                                        Capo: <?= htmlspecialchars($tab['capo']) ?>
                                    </span>
                            <?php endif; ?>
                        </div>

                        <p class="mb-0">
                            <small class="text-muted">
                                Submitted by <?= htmlspecialchars($tab['author_name']) ?> on
                                <?= date('F j, Y', strtotime($tab['created_at'])) ?>
                            </small>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">

                            <a href="../uploads/tabs/<?= $tab['file_path']?>" class="btn btn-print" download>
                                <i class="fas fa-print me-2"></i>Download
                            </a>

                            <?php if ($tab['author_id'] == $_SESSION['user_id'] || $_SESSION['role_id'] == 1): ?>
                                <a href="edit_tab.php?id=<?= $tab['id'] ?>" class="btn btn-edit">
                                    <i class="fas fa-edit me-2"></i>Edit Tab
                                </a>
                                <a href="view_tab.php?delete=<?= $tab['id'] ?>"
                                   class="btn btn-danger"
                                   title="Delete Tab"
                                   onclick="return confirm('Are you sure you want to delete this tab? This action cannot be undone.')">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="tab-container">
                    <?php foreach ($sections as $section): ?>
                        <div class="tab-section <?= $section['type'] ?>">
                            <?php if ($section['type'] === 'comment' && isset($section['title'])): ?>
                                <div class="section-title">[<?= htmlspecialchars($section['title']) ?>]</div>
                            <?php endif; ?>

                            <?php
                            $content = implode("\n", $section['content']);
                            echo htmlspecialchars($content);
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Tab Actions -->
        <div class="tab-actions">
            <a href="../uploads/tabs/<?= $tab['file_path']?>" class="btn btn-print ms-2" download>
                <i class="fas fa-print me-2"></i>Download
            </a>
            <?php if ($tab['author_id'] == $_SESSION['user_id'] || $_SESSION['role_id'] == 1): ?>
                <a href="edit_tab.php?id=<?= $tab['id'] ?>" class="btn btn-edit ms-2">
                    <i class="fas fa-edit me-2"></i>Edit Tab
                </a>
                <a href="view_tab.php?delete=<?= $tab['id'] ?>"
                   class="btn btn-danger"
                   title="Delete Tab"
                   onclick="return confirm('Are you sure you want to delete this tab? This action cannot be undone.')">
                    <i class="fas fa-trash me-2"></i>Delete Tab
                </a>
            <?php endif; ?>
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
</script>
</body>
</html>