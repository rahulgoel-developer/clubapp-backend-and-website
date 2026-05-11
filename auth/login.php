<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

use Firebase\JWT\JWT;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

// Basic validation
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

// Find user in DB
$pdo  = getDB();
$stmt = $pdo->prepare("SELECT id, name, email, password, role, profile_photo FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password']);
    exit;
}

$now = time();

// Generate Access Token
$accessPayload = [
    'iss'   => 'rotary-club-api',
    'iat'   => $now,
    'exp'   => $now + JWT_EXPIRY,
    'sub'   => $user['id'],
    'name'  => $user['name'],
    'email' => $user['email'],
    'role'  => $user['role'],
];

// Generate Refresh Token
$refreshPayload = [
    'iss'  => 'rotary-club-api',
    'iat'  => $now,
    'exp'  => $now + JWT_REFRESH,
    'sub'  => $user['id'],
    'type' => 'refresh',
];

$accessToken  = JWT::encode($accessPayload,  JWT_SECRET, JWT_ALGO);
$refreshToken = JWT::encode($refreshPayload, JWT_SECRET, JWT_ALGO);

// Store hashed refresh token in DB
$hash = hash('sha256', $refreshToken);
$pdo->prepare("UPDATE users SET refresh_token = ?, token_expires = ? WHERE id = ?")
    ->execute([$hash, date('Y-m-d H:i:s', $now + JWT_REFRESH), $user['id']]);

echo json_encode([
    'success'       => true,
    'access_token'  => $accessToken,
    'refresh_token' => $refreshToken,
    'token_type'    => 'Bearer',
    'expires_in'    => JWT_EXPIRY,
    'user'          => [
        'id'            => $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'role'          => $user['role'],
        'profile_photo' => $user['profile_photo'],
    ]
]);