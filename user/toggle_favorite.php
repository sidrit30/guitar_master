<?php
global $conn;
require_once '../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Ensure regular user (not admin)
if ($_SESSION['role_id'] == 1) {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$tabId = isset($_GET['tab_id']) ? (int)$_GET['tab_id'] : 0;
$returnUrl = $_GET['return'] ?? 'tabs.php';

if ($tabId <= 0) {
    header("Location: " . $returnUrl);
    exit;
}

// Check if tab exists
$tabCheck = mysqli_query($conn, "SELECT id FROM tabs WHERE id = $tabId");
if (mysqli_num_rows($tabCheck) === 0) {
    header("Location: " . $returnUrl);
    exit;
}

$favoriteCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM favorites WHERE user_id = $userId AND tab_id = $tabId");
$row = mysqli_fetch_assoc($favoriteCheck);
$isFavorited = $row['count'] > 0;

echo $isFavorited;

if ($isFavorited) {
    // Remove from favorites
    mysqli_query($conn, "DELETE FROM favorites WHERE user_id = $userId AND tab_id = $tabId ");
} else {
    // Add to favorites
    mysqli_query($conn, "INSERT INTO favorites (user_id, tab_id) VALUES ($userId , $tabId ) ");
}

// Redirect back to the referring page
header("Location: " . $returnUrl);
exit;
