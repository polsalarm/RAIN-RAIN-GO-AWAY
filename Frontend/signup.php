<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = load_json(USER_FILE);
    $users[] = [
        "username" => $_POST['username'],
        "password" => $_POST['password']
    ];
    save_json(USER_FILE, $users);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - Weather Calendar</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h2>Sign Up</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Choose username" required><br>
        <input type="password" name="password" placeholder="Choose password" required><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>

