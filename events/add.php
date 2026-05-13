<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/authenticate.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Admin only
$decoded = authenticate('admin');

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');

if (!$title) {
    http_response_code(400);
    echo json_encode(['error' => 'Title is required']);
    exit;
}

$description    = $data['description']  ?? null;
$event_date     = $data['event_date']   ?? null;
$location       = $data['location']     ?? null;

$pdo = getDB();

$stmt = $pdo->prepare("
    INSERT INTO events (title, description, event_date, location, created_by)
    VALUES (:title, :description, :event_date, :location, :created_by)
");

$stmt->execute([
    ':title'       => $title,
    ':description' => $description,
    ':event_date'  => $event_date,
    ':location'    => $location,
    ':created_by'  => $decoded->sub,
]);

$event_id = $pdo->lastInsertId();

if (!$event_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add event']);
    exit;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Event added successfully',
    'event_id' => (int) $event_id
]);