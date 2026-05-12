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

$input     = json_decode(file_get_contents('php://input'), true);
$member_id = intval($input['member_id'] ?? 0);

if (!$member_id) {
    http_response_code(400);
    echo json_encode(['error' => 'member_id is required']);
    exit;
}

$pdo = getDB();

// Check member exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'member'");
$stmt->execute([$member_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Member not found']);
    exit;
}

// Prevent admin from deleting themselves
if ($member_id === (int)$decoded->sub) {
    http_response_code(403);
    echo json_encode(['error' => 'You cannot delete your own account']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Delete family members (cascade would handle it but explicit is cleaner)
    $pdo->prepare("DELETE FROM family_members WHERE user_id = ?")
        ->execute([$member_id]);

    // Delete business details
    $pdo->prepare("DELETE FROM member_business WHERE user_id = ?")
        ->execute([$member_id]);

    // Delete the user
    $pdo->prepare("DELETE FROM users WHERE id = ?")
        ->execute([$member_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Member deleted successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete member: ' . $e->getMessage()]);
}