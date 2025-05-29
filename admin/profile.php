<?php
global $conn;
require_once '../config.php';
require_once '../functions.php';


// Ensure only admin can access
if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

$username = $_SESSION['username'];
$errors = [];
$success = [];

// Get current user data
$userQuery = mysqli_query($conn, "SELECT * FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'");
$currentUser = mysqli_fetch_assoc($userQuery);

// Handle profile picture upload
if (isset($_POST['upload_picture'])) {

    $image_flag = true;
    $target_dir = "uploads/profile/";
    $extension = strtolower(pathinfo($_FILES['profile_picture']["name"], PATHINFO_EXTENSION));
    $allowed_extensions = ["jpg", "jpeg", "png"];

    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = "Profile picture must be a JPG or PNG file.";
    }

    $newfilename = uniqid() . '.' . $extension;
    $target_file = $target_dir . basename($newfilename);

    $check = getimagesize($_FILES['profile_picture']["tmp_name"]);
    if ($image_flag && !$check) {
        $errors[] = "File is not a valid image.";
        $image_flag = false;
    }

    if ($image_flag && $_FILES['profile_picture']["size"] > 10485760) {
        $errors[] = "Profile picture must be less than 10MB.";
        $image_flag = false;
    }

    if ($image_flag && !move_uploaded_file($_FILES['profile_picture']["tmp_name"], dirname(__DIR__) . DIRECTORY_SEPARATOR . $target_file)) {
        $errors[] = "Failed to upload profile picture.";
        $image_flag = false;
    }

    if(empty($errors) && $image_flag) {
        if (!($_FILES['profile_picture']["name"] === "uploads/profile/profile_default.png"))
            unlink(realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . $_SESSION['profile_picture']));
        $username = $currentUser['username'];
        $query = "UPDATE users SET profile_picture = '$target_file' WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        $_SESSION['profile_picture'] = $target_file;
    }

}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (password_verify($currentPassword, $currentUser['password_hash'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) > 8 && strlen($newPassword) <= 20) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET password_hash = '" . mysqli_real_escape_string($conn, $hashedPassword) . "' WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'";

                if (mysqli_query($conn, $updateQuery)) {
                    $success[] = "Password changed successfully!";
                } else {
                    $errors[] = "Failed to update password.";
                }
            } else {
                $errors[] = "New password must be between 8 and 20 characters.";
            }
        } else {
            $errors[] = "New passwords do not match.";
        }
    } else {
        $errors[] = "Current password is incorrect.";
    }
}

// Get user's tabs
$tabsQuery = mysqli_query($conn, "SELECT id, song_name, artist_name, created_at FROM tabs WHERE author_id = " . $currentUser['id'] . " ORDER BY created_at DESC");
$userTabs = mysqli_fetch_all($tabsQuery, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Guitar Master Admin</title>
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

        .profile-picture-container {
            position: relative;
            display: inline-block;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #fed7aa;
        }

        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            background-color: #fed7aa;
            color: #5c3d2e;
            font-size: 4rem;
            border: 4px solid #fed7aa;
        }

        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
            border-radius: 50%;
        }

        .profile-picture-container:hover .upload-overlay {
            opacity: 1;
        }

        .table th {
            background-color: #5c3d2e;
            color: white;
            border-color: #3e2f1c;
        }

        .table td {
            border-color: #fed7aa;
        }

        .form-control:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.25);
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
    </style>
</head>
<body>

<div id="header-container"> <?php include "header.php" ?>></div>

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
                <h2 class="fw-bold mb-1" style="color: #5c3d2e;">My Profile</h2>
                <p class="text-gray-600 mb-0">Manage your account settings and preferences</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <ul class="mb-0">
                    <?php foreach ($success as $message): ?>
                        <li><?= htmlspecialchars($message) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <!-- Profile Picture Upload -->
                        <form method="post" enctype="multipart/form-data" id="profilePictureForm">
                            <div class="profile-picture-container mb-3">
                                    <img src="../<?= htmlspecialchars($_SESSION['profile_picture']) ?>"
                                         alt=""
                                         class="profile-picture rounded-circle"
                                         style="width: 150px; height: 150px; object-fit: cover; border: 2px solid #fed7aa;">
                                <div class="upload-overlay rounded-circle" onclick="document.getElementById('profilePictureInput').click()">
                                    <i class="fas fa-camera fa-2x"></i>
                                </div>
                            </div>
                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;" onchange="document.getElementById('profilePictureForm').submit();">
                            <input type="hidden" name="upload_picture" value="1">
                        </form>

                        <h4 class="mb-2" style="color: #5c3d2e;"><?= htmlspecialchars($currentUser['username']) ?></h4>
                        <p class="text-muted mb-2"><?= htmlspecialchars($currentUser['email']) ?></p>
                        <span class="badge" style="background-color: #5c3d2e; color: white;">
                                <i class="fas fa-crown me-1"></i>Administrator
                            </span>

                        <hr class="my-4">

                        <div class="row text-center">
                            <div class="col-6">
                                <h5 class="mb-1" style="color: #f97316;"><?= count($userTabs) ?></h5>
                                <small class="text-muted">Tabs Created</small>
                            </div>
                            <div class="col-6">
                                <h5 class="mb-1" style="color: #f97316;"><?= date('M Y', strtotime($currentUser['created_at'] ?? 'now')) ?></h5>
                                <small class="text-muted">Member Since</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="col-lg-8">
                <!-- Change Password -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- My Tabs -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                            <i class="fas fa-music me-2"></i>My Tabs
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($userTabs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th class="px-4 py-3">Song Name</th>
                                        <th class="px-4 py-3">Artist</th>
                                        <th class="px-4 py-3">Created</th>
                                        <th class="px-4 py-3 text-center">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($userTabs as $tab): ?>
                                        <tr>
                                            <td class="px-4 py-3 fw-semibold"><?= htmlspecialchars($tab['song_name']) ?></td>
                                            <td class="px-4 py-3"><?= htmlspecialchars($tab['artist_name']) ?></td>
                                            <td class="px-4 py-3 text-muted">
                                                <?= date('M d, Y', strtotime($tab['created_at'])) ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <a href="view_tab.php?id=<?= $tab['id'] ?>" class="btn btn-sm btn-orange">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_tab.php?id=<?= $tab['id'] ?>" class="btn btn-sm btn-primary ms-1">
                                                    <i class="fas fa-edit"></i>
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
                                <h5 class="text-muted">No tabs created yet</h5>
                                <p class="text-muted">Start creating guitar tabs to see them here.</p>
                                <a href="edit_tab.php" class="btn btn-orange">
                                    <i class="fas fa-plus me-2"></i>Create Your First Tab
                                </a>
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

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;

        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
</script>
</body>
</html>