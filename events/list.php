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

// Any logged in user can access
$decoded = authenticate();

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT
        e.id, e.title, e.description,
        e.event_date, e.location,
        e.featured_photo, e.created_at,
        u.id AS created_by_id,
        u.name AS created_by_name
    FROM events e
    LEFT JOIN users u ON u.id = e.created_by
    ORDER BY e.event_date DESC
");
$stmt->execute();
$events = $stmt->fetchAll();

foreach ($events as &$event) {
    $event['id'] = (int) $event['id'];
    $event['created_by'] = [
        'id'   => (int) $event['created_by_id'],
        'name' => $event['created_by_name']
    ];
    unset($event['created_by_id'], $event['created_by_name']);
}

echo json_encode([
    'success' => true,
    'total'   => count($events),
    'events'  => $events
]);