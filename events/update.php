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

$event_id = isset($data['event_id']) ? (int) $data['event_id'] : 0;

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['error' => 'event_id is required']);
    exit;
}

$pdo = getDB();

// Check event exists
$check = $pdo->prepare("SELECT id FROM events WHERE id = :id");
$check->execute([':id' => $event_id]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

// Build dynamic SET clause — only update fields that are sent
$fields = ['title', 'description', 'event_date', 'location'];
$setClauses = [];
$params = [':id' => $event_id];

foreach ($fields as $field) {
    if (array_key_exists($field, $data)) {
        $setClauses[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

if (empty($setClauses)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields provided to update']);
    exit;
}

$sql = "UPDATE events SET " . implode(', ', $setClauses) . " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode([
    'success' => true,
    'message' => 'Event updated successfully'
]);