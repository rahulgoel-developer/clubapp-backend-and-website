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

$event_id  = isset($data['event_id'])  ? (int) $data['event_id']  : 0;
$photo_url = trim($data['photo_url']   ?? '');

if (!$event_id || !$photo_url) {
    http_response_code(400);
    echo json_encode(['error' => 'event_id and photo_url are required']);
    exit;
}

$caption = $data['caption'] ?? null;

$pdo = getDB();

// Check event exists
$check = $pdo->prepare("SELECT id FROM events WHERE id = :id");
$check->execute([':id' => $event_id]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO gallery (event_id, photo_url, caption, uploaded_by)
    VALUES (:event_id, :photo_url, :caption, :uploaded_by)
");

$stmt->execute([
    ':event_id'    => $event_id,
    ':photo_url'   => $photo_url,
    ':caption'     => $caption,
    ':uploaded_by' => $decoded->sub,
]);

$photo_id = $pdo->lastInsertId();

if (!$photo_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add photo']);
    exit;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Photo added successfully',
    'photo_id' => (int) $photo_id
]);