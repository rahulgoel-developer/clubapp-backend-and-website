<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input        = json_decode(file_get_contents('php://input'), true);
$refreshToken = trim($input['refresh_token'] ?? '');

if (empty($refreshToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'Refresh token is required']);
    exit;
}

try {
    $decoded = JWT::decode($refreshToken, new Key(JWT_SECRET, JWT_ALGO));

    // Make sure it's actually a refresh token
    if (($decoded->type ?? '') !== 'refresh') {
        throw new Exception('Invalid token type');
    }

    // Verify token hash exists in DB
    $pdo  = getDB();
    $hash = hash('sha256', $refreshToken);
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND refresh_token = ?");
    $stmt->execute([$decoded->sub, $hash]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Refresh token revoked or invalid']);
        exit;
    }

    // Issue new access token
    $now = time();
    $newAccessToken = JWT::encode([
        'iss'   => 'rotary-club-api',
        'iat'   => $now,
        'exp'   => $now + JWT_EXPIRY,
        'sub'   => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ], JWT_SECRET, JWT_ALGO);

    echo json_encode([
        'success'      => true,
        'access_token' => $newAccessToken,
        'token_type'   => 'Bearer',
        'expires_in'   => JWT_EXPIRY,
    ]);

} catch (ExpiredException $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Refresh token expired. Please login again.']);
    exit;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid refresh token']);
    exit;
}