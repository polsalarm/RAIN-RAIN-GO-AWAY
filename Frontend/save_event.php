<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "test_user";
}
$currentUser = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? null;
    $title = $_POST['title'] ?? null;

    if ($date && $title) {
        $eventsFile = __DIR__ . "/data/events.json";
        $events = file_exists($eventsFile) ? json_decode(file_get_contents($eventsFile), true) : [];
        if (!is_array($events)) $events = [];

        if (!isset($events[$date])) $events[$date] = [];
        $events[$date][] = ["title" => $title, "user" => $currentUser];

        file_put_contents($eventsFile, json_encode($events, JSON_PRETTY_PRINT));
    }
}
header("Location: calendar.php");
exit;
