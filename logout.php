<?php

global $conn;
require_once 'config.php';
if(isset($_SESSION["token"]) && isset($_GET["token"])){
    $token = $_GET["token"];
    if($_SESSION['token'] == $token){
        $query = "DELETE FROM tokens WHERE token = '$token'";
        mysqli_query($conn, $query);

        setcookie('remember_token', '', time() - 3600, '/', '', false, true);

        $_SESSION = [];
        session_destroy();
    }
}
header("location: index.php");

