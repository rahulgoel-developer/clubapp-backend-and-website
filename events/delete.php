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

// Delete event (gallery rows will cascade if FK set, otherwise delete manually)
$pdo->beginTransaction();

try {
    // Delete related gallery photos first
    $delGallery = $pdo->prepare("DELETE FROM gallery WHERE event_id = :event_id");
    $delGallery->execute([':event_id' => $event_id]);

    // Delete the event
    $delEvent = $pdo->prepare("DELETE FROM events WHERE id = :id");
    $delEvent->execute([':id' => $event_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete event']);
}