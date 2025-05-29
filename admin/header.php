<?php
session_start();
$username = $_SESSION['username'];
?>

<header style="background-color: #5c3d2e; color: white; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;" class="p-3 d-flex justify-content-between align-items-center shadow-lg">
    <div class="d-flex align-items-center">
        <p><a href="index.php"><img src="../uploads/logo_guitar.png" alt="Guitar Master Logo" width="45" height="45" class="me-2">
                <h4 class="mb-0 text-white">Guitar Master Admin</h4></a></p>
    </div>
    <div class="d-flex align-items-center">
        <span class="me-3 text-orange-100">Welcome, <strong><?= htmlspecialchars($username) ?></strong></span>
        <!-- Profile Picture -->
        <div class="me-3">
                <img src="../<?= htmlspecialchars($_SESSION['profile_picture']) ?>"
                     alt=""
                     class="rounded-circle"
                     style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fed7aa;">
        </div>
        <a href="../logout.php?token=<?=$_SESSION['token'] ?>" class="btn btn-sm" style="background-color: #f97316; color: white; border: none;"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
    </div>
</header>


<style>
    /* Ensure body has padding to account for fixed header */
    body {
        padding-top: 76px;
    }

    /* Make logout button more visible on hover */
    header a.btn:hover {
        background-color: #ea580c !important;
        box-shadow: 0 0 8px rgba(0,0,0,0.2);
    }

    /* Ensure header stays on top */
    header {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
    }
</style>