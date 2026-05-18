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

$content = $data['content'] ?? null;

$pdo = getDB();

$stmt = $pdo->prepare("
    INSERT INTO updates (title, content, posted_by)
    VALUES (:title, :content, :posted_by)
");

$stmt->execute([
    ':title'     => $title,
    ':content'   => $content,
    ':posted_by' => $decoded->sub,
]);

$update_id = $pdo->lastInsertId();

if (!$update_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add update']);
    exit;
}

echo json_encode([
    'success'   => true,
    'message'   => 'Update added successfully',
    'update_id' => (int) $update_id
]);