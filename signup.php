<?php
require_once "config.php";
require_once "functions.php";
require_once "check_auth.php";

$errors = [];

if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    validate($username, $email, $password, $errors);
    $profile_picture_path = validateProfilePicture($_FILES['profile'], $errors);

    if (!$errors) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO users (username, email, password_hash, profile_picture, role_id) VALUES ('$username', '$email', '$password_hash', '$profile_picture_path', 2)";
        if (mysqli_query($conn, $insert_query)) {
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "An error occurred while adding the user.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
    <h2 class="text-2xl font-bold mb-6 text-center text-[#5c3d2e]">Create Your Guitar Master Account</h2>

    <?php if ($errors): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block mb-1 font-medium text-[#5c3d2e]">Username</label>
            <input type="text" name="username" required class="w-full border border-orange-300 p-2 rounded">
        </div>

        <div>
            <label class="block mb-1 font-medium text-[#5c3d2e]">Email</label>
            <input type="email" name="email" required class="w-full border border-orange-300 p-2 rounded">
        </div>

        <div>
            <label class="block mb-1 font-medium text-[#5c3d2e]">Password</label>
            <input type="password" name="password" id="password" required class="w-full border border-orange-300 p-2 rounded">
            <div class="mt-2">
                <input type="checkbox" onclick="togglePassword()"> Show Password
            </div>
        </div>

        <div>
            <label class="block mb-1 font-medium text-[#5c3d2e]">Profile Picture</label>
            <input type="file" name="profile" accept="image/*" onchange="previewImage(event)" class="w-full">
            <img id="imagePreview" src="#" alt="Image Preview" class="mt-2 hidden w-24 h-24 object-cover rounded">
        </div>

        <button type="submit" class="w-full bg-[#5c3d2e] text-white py-2 px-4 rounded hover:bg-[#3e2f1c] transition">Sign Up</button>
    </form>

    <div class="text-center mt-4">
        <p class="text-sm text-[#5c3d2e]">Already have an account? <a href="login.php" class="text-[#5c3d2e] hover:underline font-semibold">Log in</a></p>
    </div>
</div>

<script>
    function togglePassword() {
        const pwd = document.getElementById("password");
        pwd.type = pwd.type === "password" ? "text" : "password";
    }

    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('imagePreview');
            output.src = reader.result;
            output.classList.remove('hidden');
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
</body>
</html>
