<?php
global $conn;
require_once "config.php";
require_once "send_mail.php";

$errors = [];
$success = false;

function random_str() {
    $length = 8;
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;

    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

if (isset($_POST['email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $query = "SELECT username FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    $username = $result->fetch_assoc()['username'];

    if (mysqli_num_rows($result) === 1) {
        $password = random_str();
        send_mail($email, $username, $password);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password_hash = '$password_hash' WHERE email = '$email'";
        mysqli_query($conn, $query);
        $success = true;
    } else {
        $errors[] = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-orange-50 min-h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md relative">
    <a href="login.php" class="absolute left-4 top-4 text-[#5c3d2e] hover:text-[#3e2f1c] text-xl">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    </a>
    <h2 class="text-2xl font-bold mb-6 text-center text-[#5c3d2e]">Forgot Your Password?</h2>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-center">
            Check your email for the reset link.
        </div>
    <?php else: ?>
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
                <label class="block mb-1 font-medium text-[#5c3d2e]">Email</label>
                <input type="email" name="email" required class="w-full border border-orange-300 p-2 rounded">
            </div>
            <button type="submit" class="w-full bg-[#5c3d2e] text-white py-2 px-4 rounded hover:bg-[#3e2f1c] transition">Send Reset Link</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
