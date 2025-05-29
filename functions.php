<?php
require_once "config.php";

function validate($username, $email, $password, &$errors)
{
    global $conn;
    $errors = [];

    if(!preg_match("/^[a-zA-Z0-9]*$/", $username)){
        $errors[] = "Username can only contain letters and numbers!";
        return;
    }
    if(strlen($username) < 4 || strlen($username) > 20){
        $errors[] = "Username must be between 4 and 50 characters!";
        return;
    }
    if(strlen($password) < 8 || strlen($password) > 20){
        $errors[] = "Password must have between 8 and 20 characters!";
        return;
    }

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        $errors[] = "Username is taken!";
        return;
    }


    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        $errors[] = "Email already exists!";
        return;
    }

    $query = "SELECT * FROM banned_users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        $errors[] = "Email account is banned!";
    }

}

function validateProfilePicture($file, &$errors): ?string {
    $target_dir = "uploads/profile/";
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = ["jpg", "jpeg", "png"];

    if (empty($file["name"])) {
        return "uploads/profile/profile_default.png";
    }

    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = "Profile picture must be a JPG or PNG file.";
        return null;
    }

    $newfilename = uniqid() . "." . $extension;
    $target_file = $target_dir . basename($newfilename);

    $check = getimagesize($file["tmp_name"]);
    if (!$check) {
        $errors[] = "File is not a valid image.";
        return null;
    }

    if ($file["size"] > 10485760) {
        $errors[] = "Profile picture must be less than 10MB.";
        return null;
    }

    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        $errors[] = "Failed to upload profile picture.";
        return null;
    }
    return $target_file;
}

