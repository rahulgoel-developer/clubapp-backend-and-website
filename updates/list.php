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

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT
        u.id, u.title, u.content,
        u.featured_photo, u.created_at,
        usr.id AS posted_by_id,
        usr.name AS posted_by_name
    FROM updates u
    LEFT JOIN users usr ON usr.id = u.posted_by
    ORDER BY u.created_at DESC
");
$stmt->execute();
$updates = $stmt->fetchAll();

foreach ($updates as &$update) {
    $update['id'] = (int) $update['id'];
    $update['posted_by'] = [
        'id'   => (int) $update['posted_by_id'],
        'name' => $update['posted_by_name']
    ];
    unset($update['posted_by_id'], $update['posted_by_name']);
}

echo json_encode([
    'success' => true,
    'total'   => count($updates),
    'updates' => $updates
]);