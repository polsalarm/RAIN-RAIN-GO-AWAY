<?php
// delete_event.php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['date']) || !isset($data['index'])) {
    echo json_encode(['success'=>false, 'error'=>'Missing parameters']);
    exit;
}

$date = $data['date'];
$index = (int)$data['index'];
$file = __DIR__ . "/data/events.json";

// Load events
$events = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($events)) $events = [];

// Check if event exists
if (isset($events[$date]) && isset($events[$date][$index])) {
    array_splice($events[$date], $index, 1);
    if (empty($events[$date])) unset($events[$date]);
    file_put_contents($file, json_encode($events, JSON_PRETTY_PRINT));
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Event not found']);
}
?>
