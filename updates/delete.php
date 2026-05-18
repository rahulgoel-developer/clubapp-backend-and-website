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

$update_id = isset($data['update_id']) ? (int) $data['update_id'] : 0;

if (!$update_id) {
    http_response_code(400);
    echo json_encode(['error' => 'update_id is required']);
    exit;
}

$pdo = getDB();

$check = $pdo->prepare("SELECT id FROM updates WHERE id = :id");
$check->execute([':id' => $update_id]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Update not found']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM updates WHERE id = :id");
$stmt->execute([':id' => $update_id]);

echo json_encode([
    'success' => true,
    'message' => 'Update deleted successfully'
]);