<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar shadow-lg" id="sidebar">
    <div class="p-4">
        <h5 class="text-orange-100 fw-bold mb-4">Navigation</h5>

        <!-- Dashboard -->
        <div class="nav-item mb-2">
            <a href="index.php"
               class="nav-link nav-link-custom rounded p-3 d-flex align-items-center <?= $currentPage == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt me-3"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Explore Tabs -->
        <div class="nav-item mb-2">
            <a href="tabs.php"
               class="nav-link nav-link-custom rounded p-3 d-flex align-items-center <?= $currentPage == 'tabs.php' ? 'active' : '' ?>">
                <i class="fas fa-music me-3"></i>
                <span>Explore Tabs</span>
            </a>
        </div>

        <!-- View Users -->
        <div class="nav-item mb-2">
            <a href="users.php"
               class="nav-link nav-link-custom rounded p-3 d-flex align-items-center <?= $currentPage == 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users me-3"></i>
                <span>View Users</span>
            </a>
        </div>

        <!-- Profile -->
        <div class="nav-item mb-2">
            <a href="profile.php"
               class="nav-link nav-link-custom rounded p-3 d-flex align-items-center <?= $currentPage == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user me-3"></i>
                <span>Profile</span>
            </a>
        </div>
    </div>
</div>
