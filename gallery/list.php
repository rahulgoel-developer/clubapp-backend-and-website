<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/authenticate.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Any logged in user
$decoded = authenticate();

$event_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['error' => 'event_id is required']);
    exit;
}

$pdo = getDB();

// Check event exists
$check = $pdo->prepare("SELECT id, title FROM events WHERE id = :id");
$check->execute([':id' => $event_id]);
$event = $check->fetch();
if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        g.id, g.photo_url, g.caption, g.created_at,
        u.id AS uploaded_by_id,
        u.name AS uploaded_by_name
    FROM gallery g
    LEFT JOIN users u ON u.id = g.uploaded_by
    WHERE g.event_id = :event_id
    ORDER BY g.created_at ASC
");
$stmt->execute([':event_id' => $event_id]);
$photos = $stmt->fetchAll();

foreach ($photos as &$photo) {
    $photo['id'] = (int) $photo['id'];
    $photo['uploaded_by'] = [
        'id'   => (int) $photo['uploaded_by_id'],
        'name' => $photo['uploaded_by_name']
    ];
    unset($photo['uploaded_by_id'], $photo['uploaded_by_name']);
}

echo json_encode([
    'success'  => true,
    'event_id' => (int) $event['id'],
    'event'    => $event['title'],
    'total'    => count($photos),
    'photos'   => $photos
]);