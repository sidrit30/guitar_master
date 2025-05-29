<?php
include 'check_auth.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guitar Master</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            height: 100%;
            background-color: #f5f0e6;
            color: #3e2f1c;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: #d2a679;
        }
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8em;
            font-weight: bold;
            color: #5c3d2e;
        }
        .logo img {
            height: 45px;
            width: 45px;
            margin-right: 10px;
        }
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            background-color: #8b5e3c;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background-color: #754b2e;
        }
        .hero {
            background: url('uploads/guitarguy.jpg') no-repeat center center/cover;
            height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .overlay {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 60px;
            border-radius: 15px;
            color: #3e2f1c;
        }
        .overlay h1 {
            font-size: 3em;
            margin-bottom: 20px;
        }
        .overlay p {
            font-size: 1.2em;
            max-width: 700px;
            margin: auto auto 30px;
        }
        .features {
            padding: 60px 20px;
            text-align: center;
            background-color: #fff8f0;
        }
        .features h2 {
            color: #5c3d2e;
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        .song-list {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .song {
            background-color: #f0d9b5;
            border-radius: 10px;
            padding: 20px;
            width: 200px;
            color: #3e2f1c;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<nav>
    <div class="logo">
        <img src="uploads/logo_guitar.png" alt="Guitar Logo">
        Guitar Master
    </div>
    <div class="nav-links">
        <a href="signup.php">Sign Up</a>
        <a href="login.php">Login</a>
    </div>
</nav>

<div class="hero">
    <div class="overlay">
        <h1>Master the Guitar, Together</h1>
        <p>Join a community of players, share your favorite tabs, and discover how others bring music to life. Whether you're playing Pop, Rock or Metal, it all starts here.</p>
    </div>
</div>

<section class="features">
    <h2>Learn to Play Top Hits Like</h2>
    <div class="song-list">
        <div class="song">
            <i class="fa-solid fa-music"></i> Creep - Radiohead
        </div>
        <div class="song">
            <i class="fa-solid fa-music"></i> No One Knows - Queens of the Stone Age
        </div>
        <div class="song">
            <i class="fa-solid fa-music"></i> Karma Police - Radiohead
        </div>
        <div class="song">
            <i class="fa-solid fa-music"></i> Go With the Flow - QOTSA
        </div>
        <div class="song">
            <i class="fa-solid fa-music"></i> Paranoid Android - Radiohead
        </div>
    </div>
</section>
</body>
</html>
