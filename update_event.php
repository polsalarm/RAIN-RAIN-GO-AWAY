<?php
// update_event.php
header('Content-Type: application/json');
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['date']) || !isset($data['index']) || !isset($data['title'])) {
    echo json_encode(['success'=>false, 'error'=>'Invalid input']);
    exit;
}

$eventsFile = __DIR__ . '/data/events.json';
$events = file_exists($eventsFile) ? json_decode(file_get_contents($eventsFile), true) : [];
if (!is_array($events)) $events = [];

$date = $data['date'];
$idx = intval($data['index']);
$title = trim($data['title']);

if (!isset($events[$date]) || !isset($events[$date][$idx])) {
    echo json_encode(['success'=>false, 'error'=>'Event not found']);
    exit;
}

// update
$events[$date][$idx]['title'] = $title;

// save with lock
$result = file_put_contents($eventsFile, json_encode($events, JSON_PRETTY_PRINT), LOCK_EX);
if ($result === false) {
    echo json_encode(['success'=>false, 'error'=>'Could not save file']);
} else {
    echo json_encode(['success'=>true]);
}
