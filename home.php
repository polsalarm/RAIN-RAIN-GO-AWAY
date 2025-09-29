<?php
require 'config.php';
if (!isset($_SESSION['user'])) header("Location: index.php");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home - Weather Calendar</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
    <h2>Welcome, <?php echo $_SESSION['user']; ?>!</h2>
    <p>Go to <a href="calendar.php">Calendar</a> to see your events and weather.</p>
<?php include 'includes/footer.php'; ?>
</body>
</html>

