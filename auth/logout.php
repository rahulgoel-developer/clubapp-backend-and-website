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

// Must be logged in to logout
$decoded = authenticate();

// Clear refresh token from DB
$pdo = getDB();
$pdo->prepare("UPDATE users SET refresh_token = NULL, token_expires = NULL WHERE id = ?")
    ->execute([$decoded->sub]);

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);