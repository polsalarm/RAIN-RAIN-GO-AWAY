<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = load_json(USER_FILE);
    $username = $_POST['username'];
    $password = $_POST['password'];

    foreach ($users as $u) {
        if ($u['username'] === $username && $u['password'] === $password) {
            $_SESSION['user'] = $username;
            header("Location: home.php");
            exit;
        }
    }
    $error = "Invalid login!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Weather Calendar</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class = "login">
    <img class="logo" src="assets/logo.svg">
    <div class="login-container">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    <p class = "signup-prompt">No account? <a href="signup.php">Sign up here</a></p>
</div>
</div>
</body>
</html>
