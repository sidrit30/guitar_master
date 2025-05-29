<?php
global $conn;
require_once "config.php";

// If user is already logged in via session
if (isset($_SESSION['username']) && isset($_SESSION['role_id'])) {
    redirect($_SESSION['role_id']);
    exit;
}

// Check for remember_token cookie
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt =
        "SELECT users.username, users.role_id 
        FROM tokens 
        JOIN users ON tokens.user_id = users.id 
        WHERE tokens.token = '$token'";
    $result = mysqli_query($conn, $stmt);

    if (mysqli_num_rows($result) > 0) {
        // Set session
        $row = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $row['username'];
        $_SESSION['role_id'] = $row['role_id'];
        $_SESSION['profile_picture'] = $row['profile_picture'];
        $_SESSION['token'] = $token;
        $_SESSION['user_id'] = $row['id'];

        redirect($_SESSION['role_id']);
        exit;
    }
    mysqli_free_result($result);
}

// Helper function to redirect based on role
function redirect($roleId) {
    if ($roleId == 1) {
        header('Location: admin/');
    } elseif ($roleId == 2) {
        header('Location: user/');
    } else {
        header('Location: login.php');
    }
    exit;
}