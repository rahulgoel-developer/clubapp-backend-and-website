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

$photo_id = isset($data['photo_id']) ? (int) $data['photo_id'] : 0;

if (!$photo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'photo_id is required']);
    exit;
}

$pdo = getDB();

$check = $pdo->prepare("SELECT id FROM gallery WHERE id = :id");
$check->execute([':id' => $photo_id]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Photo not found']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM gallery WHERE id = :id");
$stmt->execute([':id' => $photo_id]);

echo json_encode([
    'success' => true,
    'message' => 'Photo deleted successfully'
]);