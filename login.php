<?php
global $conn;
require_once "config.php";
require_once "check_auth.php";
$errors = [];

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['profile_picture'] = $user['profile_picture'];

            $token = bin2hex(random_bytes(32));
            $_SESSION['token'] = $token;

            if (!empty($_POST['remember'])) {
                $id = $user['id'];
                mysqli_query($conn, "DELETE FROM tokens WHERE user_id = '$id'");
                mysqli_query($conn, "INSERT INTO tokens (user_id, token) VALUES ('$id', '$token')");
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            }

            if ($_SESSION['role_id'] == 1) {
                header("Location: admin/index.php");
            } else if ($_SESSION['role_id'] == 2) {
                header("Location: user/index.php");
            }
            exit;
        } else {
            $errors[] = "Incorrect password.";
        }
    } else {
        $errors[] = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-orange-50 min-h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md relative">
    <a href="index.php" class="absolute left-4 top-4 text-[#5c3d2e] hover:text-[#3e2f1c] text-xl">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    </a>
    <h2 class="text-2xl font-bold mb-6 text-center text-[#5c3d2e]">Login to Guitar Master</h2>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
        <div>
            <label class="block mb-1 font-medium text-[#5c3d2e]">Username</label>
            <input type="text" name="username" required class="w-full border border-orange-300 p-2 rounded">
        </div>

        <div>
            <label class="block mb-1 font-medium text-[#5c3d2e]">Password</label>
            <input type="password" name="password" id="password" required class="w-full border border-orange-300 p-2 rounded">
            <div class="mt-2">
                <input type="checkbox" onclick="togglePassword()"> Show Password
            </div>
        </div>

        <div>
            <label class="inline-flex items-center text-[#5c3d2e]">
                <input type="checkbox" name="remember" class="mr-2"> Remember Me
            </label>
        </div>

        <button type="submit" class="w-full bg-[#5c3d2e] text-white py-2 px-4 rounded hover:bg-[#3e2f1c] transition">Login</button>

        <div class="text-center mt-4">
            <a href="forgot_password.php" class="text-sm text-[#5c3d2e] hover:underline">Forgot Password?</a>
        </div>
    </form>
</div>

<script>
    function togglePassword() {
        const pwd = document.getElementById("password");
        pwd.type = pwd.type === "password" ? "text" : "password";
    }
</script>
</body>
</html>
