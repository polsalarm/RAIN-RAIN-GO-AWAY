<?php
require 'config.php';
if (!isset($_SESSION['user'])) header("Location: index.php");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home - Weather Calendar</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <h2 class = "user-welcome">Welcome, <?php echo $_SESSION['user']; ?>!</h2>
    <!--<p>Go to <a href="calendar.php">Calendar</a> to see your events and weather.</p>-->
    <div class = "first-content">
        <img class="logo" src="assets/logo2.svg">
        <div class = "first-content-text">Your real-time climate data visualization and risk assessment web application in Metro Manila, powered by NASA's Earth Observation Data.</div>
    </div>
    <div class = "second-content">
        <div class = "second-content-location">
            <div class = "second-content-location-text">Location</div>
            <select class = "location-dropdown" id="locationSelect">
                <option value="metro-manila">Metro Manila</option>
            </select>
        </div>
        <div class = "second-content-date">
            <div class = "second-content-date-text">Date and Time</div>
            <input type="date" class = "second-content-date-input"></input>
            <input type="time" class = "second-content-time-input"></input>
        </div>
    </div>
    <div class = "third-content">
        <div class = "third-content-temperature">
                <div class = "third-content-temperature-text">Temperature</div>
                <div class = "third-content-temperature-value">39.1 C</div>
        </div>
        <div class = "third-content-windspeed">
                <div class = "third-content-windspeed-text">Wind Speed</div>
                <div class = "third-content-windspeed-value">12.3 km/h</div>
        </div>
        <div class = "third-content-precipitation">
                <div class = "third-content-precipitation-text">Precipitation</div>
                <div class = "third-content-precipitation-value">0.0 mm</div>
        </div>
    </div>
</body>
</html>

