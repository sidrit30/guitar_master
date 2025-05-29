<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- User Sidebar Component -->
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

        <!-- Explore Tabs (Main) -->
        <div class="nav-item mb-2">
            <a href="tabs.php" class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'tabs.php' ? 'active' : '' ?> rounded p-3 d-flex align-items-center">
                <i class="fas fa-compass me-3"></i>
                <span>Explore Tabs</span>
            </a>
        </div>

        <!-- My Favorites -->
        <div class="nav-item mb-2">
            <a href="favorites.php" class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'active' : '' ?> rounded p-3 d-flex align-items-center">
                <i class="fas fa-heart me-3"></i>
                <span>My Favorites</span>
            </a>
        </div>

        <!-- Profile -->
        <div class="nav-item mb-2">
            <a href="profile.php" class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?> rounded p-3 d-flex align-items-center">
                <i class="fas fa-user me-3"></i>
                <span>Profile</span>
            </a>
        </div>

        <hr class="my-4" style="border-color: #fed7aa;">

        <!-- Create New Tab -->
        <div class="nav-item mb-2">
            <a href="edit_tab.php" class="nav-link rounded p-3 d-flex align-items-center" style="background-color: #f97316; color: white;">
                <i class="fas fa-plus me-3"></i>
                <span>Create New Tab</span>
            </a>
        </div>
    </div>
</div>