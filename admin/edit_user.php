<?php
global $conn;
require_once '../config.php';

// Ensure only admin can access
if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Fetch user details
if (!isset($_GET['username'])) {
    header("Location: users.php");
    exit;
}

$username = mysqli_real_escape_string($conn, $_GET['username']);
$userQuery = mysqli_query($conn, "SELECT id, username, email, role_id, profile_picture FROM users WHERE username = '$username'");

if (mysqli_num_rows($userQuery) === 0) {
    header("Location: users.php?error=user-not-found");
    exit;
}

$user = mysqli_fetch_assoc($userQuery);
$id = $user['id'];

// Handle promotions or bans
if (isset($_POST['promote'])) {
    mysqli_query($conn, "UPDATE users SET role_id = 1 WHERE id = '$id'");
    $user['role_id'] = 1;
    $successMessage = "User has been promoted to Admin successfully!";
} elseif (isset($_POST['ban'])) {
    mysqli_query($conn, "INSERT INTO banned_users(user_id) VALUES ('$id') ");
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");
    header("Location: users.php?banned=1&username=" . urlencode($username));
    exit;
}

// Fetch user tabs
$tabsResult = mysqli_query($conn, "SELECT id, song_name, artist_name, created_at FROM tabs WHERE author_id = {$user['id']} ORDER BY created_at DESC");
$tabs = mysqli_fetch_all($tabsResult, MYSQLI_ASSOC);

// Get current admin username
$currentUsername = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile: <?= htmlspecialchars($user['username']) ?> - Guitar Master Admin</title>
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

        header {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
        }

        header a.btn:hover {
            background-color: #ea580c !important;
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
        }

        .profile-header {
            background-color: #5c3d2e;
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: #fed7aa;
            color: #5c3d2e;
            font-size: 3rem;
            border: 4px solid white;
            margin-top: -60px;
        }

        .btn-promote {
            background-color: #5c3d2e;
            color: white;
            border: none;
        }

        .btn-promote:hover {
            background-color: #3e2f1c;
            color: white;
        }

        .btn-ban {
            background-color: #dc2626;
            color: white;
            border: none;
        }

        .btn-ban:hover {
            background-color: #b91c1c;
            color: white;
        }

        .table th {
            background-color: #5c3d2e;
            color: white;
            border-color: #3e2f1c;
        }

        .table td {
            border-color: #fed7aa;
        }

        .breadcrumb-item a {
            color: #5c3d2e;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: #f97316;
            text-decoration: underline;
        }

        .breadcrumb-item.active {
            color: #f97316;
        }

        .badge-role {
            font-size: 0.9rem;
            padding: 0.35rem 0.65rem;
        }

        .user-info-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .user-info-item:last-child {
            border-bottom: none;
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

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($user['username']) ?></li>
            </ol>
        </nav>

        <!-- Success Message -->
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- User Profile Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="profile-header p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">User Profile</h4>
                    <div>
                        <span class="badge bg-light text-dark">ID: <?= $user['id'] ?></span>
                    </div>
                </div>
            </div>
            <div class="card-body pt-5 pb-4 px-4">
                <div class="d-flex flex-column flex-md-row">
                    <!-- User Avatar -->
                    <div class="d-flex flex-column align-items-center me-md-5 mb-4 mb-md-0">
                        <div class="me-3">
                            <img src="../<?= htmlspecialchars($user['profile_picture']) ?>"
                                 alt=""
                                 class="rounded-circle"
                                 style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #fed7aa;">
                        </div>

                        <!-- Role Badge -->
                        <?php if ($user['role_id'] == 1): ?>
                            <span class="badge badge-role mt-3" style="background-color: #5c3d2e;">
                                    <i class="fas fa-crown me-1"></i> Administrator
                                </span>
                        <?php else: ?>
                            <span class="badge badge-role mt-3" style="background-color: #f97316;">
                                    <i class="fas fa-user me-1"></i> Client
                                </span>
                        <?php endif; ?>
                    </div>

                    <!-- User Info -->
                    <div class="flex-grow-1">
                        <h3 class="mb-4" style="color: #5c3d2e;"><?= htmlspecialchars($user['username']) ?></h3>

                        <div class="user-info-item">
                            <div class="row">
                                <div class="col-md-3 fw-bold text-muted">Email</div>
                                <div class="col-md-9"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                        </div>

                        <div class="user-info-item">
                            <div class="row">
                                <div class="col-md-3 fw-bold text-muted">Account ID</div>
                                <div class="col-md-9">#<?= $user['id'] ?></div>
                            </div>
                        </div>

                        <div class="user-info-item">
                            <div class="row">
                                <div class="col-md-3 fw-bold text-muted">Role</div>
                                <div class="col-md-9">
                                    <?= $user['role_id'] == 1 ? 'Administrator' : 'Client' ?>
                                </div>
                            </div>
                        </div>

                        <div class="user-info-item">
                            <div class="row">
                                <div class="col-md-3 fw-bold text-muted">Tabs Submitted</div>
                                <div class="col-md-9"><?= count($tabs) ?></div>
                            </div>
                        </div>

                        <!-- User Actions -->
                        <div class="mt-4">
                            <form method="post" class="d-flex gap-2">
                                <?php if ($user['role_id'] != 1): ?>
                                    <button type="submit" name="promote" class="btn btn-promote">
                                        <i class="fas fa-user-shield me-2"></i>Promote to Admin
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="btn btn-ban" data-bs-toggle="modal" data-bs-target="#banUserModal">
                                    <i class="fas fa-ban me-2"></i>Ban User
                                </button>

                                <a href="users.php" class="btn btn-outline-secondary ms-auto">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Tabs Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                    <i class="fas fa-music me-2"></i>Submitted Tabs
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($tabs) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Song Name</th>
                                <th class="px-4 py-3">Artist</th>
                                <th class="px-4 py-3">Created At</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tabs as $tab): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($tab['id']) ?></span>
                                    </td>
                                    <td class="px-4 py-3 fw-semibold"><?= htmlspecialchars($tab['song_name']) ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($tab['artist_name']) ?></td>
                                    <td class="px-4 py-3 text-muted">
                                        <?= date('M d, Y', strtotime($tab['created_at'])) ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-sm" style="background-color: #5c3d2e; color: white;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-guitar text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No tabs submitted yet</h5>
                        <p class="text-muted">This user hasn't created any guitar tabs.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ban User Confirmation Modal -->
<div class="modal fade" id="banUserModal" tabindex="-1" aria-labelledby="banUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc2626; color: white;">
                <h5 class="modal-title" id="banUserModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ban User Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to ban <strong><?= htmlspecialchars($user['username']) ?></strong>?</p>
                <p>This action will:</p>
                <ul>
                    <li>Remove the user from the system</li>
                    <li>Add them to the banned users list</li>
                    <li>Prevent them from creating a new account</li>
                </ul>
                <p class="text-danger"><strong>This action cannot be undone!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" style="display: inline;">
                    <button type="submit" name="ban" class="btn btn-danger">
                        <i class="fas fa-ban me-2"></i>Ban User
                    </button>
                </form>
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

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
</body>
</html>