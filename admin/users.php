<?php
global $conn;
require_once '../config.php';

// Ensure only admin can access
if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['delete'])) {
    $deleteUsername = $_GET['delete'];

    if ($deleteUsername == $_SESSION['username']) {
        header('Location: users.php?error=self-delete');
        exit;
    }

    $query = "DELETE FROM users WHERE username = '$deleteUsername' ";
    $result = mysqli_query($conn, $query);
    header('Location: users.php?deleted=1');
    exit;
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$whereClause = $search ? "WHERE username LIKE '%$search%' OR email LIKE '%$search%'" : '';

$query = "SELECT id, username, email, role_id, profile_picture FROM users $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

$totalResult = $conn->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Guitar Master Admin</title>
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

        .alert-success {
            background-color: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        .table th {
            background-color: #5c3d2e;
            color: white;
            border-color: #3e2f1c;
        }

        .table td {
            border-color: #fed7aa;
        }

        .btn-warning {
            background-color: #f97316;
            border-color: #f97316;
            color: white;
        }

        .btn-warning:hover {
            background-color: #ea580c;
            border-color: #ea580c;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        .pagination .page-link {
            color: #5c3d2e;
            border-color: #fed7aa;
        }

        .pagination .page-item.active .page-link {
            background-color: #5c3d2e;
            border-color: #5c3d2e;
        }

        .pagination .page-link:hover {
            background-color: #fed7aa;
            border-color: #f97316;
            color: #5c3d2e;
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
                <h2 class="fw-bold mb-1" style="color: #5c3d2e;">User Management</h2>
                <p class="text-gray-600 mb-0">Manage user accounts and permissions</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> User deleted successfully. The user and all related data have been removed from the system.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'self-delete'): ?>
            <div class="alert alert-error alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error!</strong> You cannot delete your own account!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form class="mb-3" method="get" action="">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by username or email" value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-primary" type="submit">Search</button>
            </div>
        </form>

        <!-- Users Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                    <i class="fas fa-users me-2"></i>All Users
                </h5>
                <span class="badge" style="background-color: #f97316; color: white;">
                        Total: <?= $totalUsers ?> users
                    </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($user['id']) ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <img src="../<?= htmlspecialchars($user['profile_picture']) ?>"
                                                 alt=""
                                                 class="rounded-circle"
                                                 style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fed7aa;">
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($user['username']) ?></div>
                                            <?php if ($user['username'] == $_SESSION['username']): ?>
                                                <small class="text-muted">(You)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($user['role_id'] == 1): ?>
                                        <span class="badge" style="background-color: #5c3d2e; color: white;">
                                                    <i class="fas fa-crown me-1"></i>Admin
                                                </span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #f97316; color: white;">
                                                    <i class="fas fa-user me-1"></i>Client
                                                </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="btn-group" role="group">
                                        <?php if ($user['username'] != $_SESSION['username']): ?>
                                        <a href="edit_user.php?username=<?= $user['username'] ?>"
                                           class="btn btn-sm btn-warning"
                                           title="View Profile">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Go to Profile tab to edit your own account!">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($user['username'] != $_SESSION['username']): ?>
                                            <a href="users.php?delete=<?= $user['username'] ?>"
                                               class="btn btn-sm btn-danger"
                                               title="Delete User"
                                               onclick="return confirm('Are you sure you want to delete this user and all related data? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Cannot delete your own account">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-gray-600">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalUsers) ?> of <?= $totalUsers ?> users
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
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