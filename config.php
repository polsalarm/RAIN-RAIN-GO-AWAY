<?php
session_start();

// Simple session config
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
}

// Path to data files
define("USER_FILE", __DIR__ . "/data/users.json");
define("EVENT_FILE", __DIR__ . "/data/events.json");
define("WEATHER_FILE", __DIR__ . "/data/weather.json");

// Load JSON helper
function load_json($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
?>

