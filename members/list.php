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

// Any logged in user can access
$decoded = authenticate();

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT
        id, name, profile_photo,
        country_code, phone,
        birth_date, blood_group,
        gender, city, state,
        designation, rotary_id,
        admission_date
    FROM users
    WHERE role = 'member'
    ORDER BY name ASC
");
$stmt->execute();
$members = $stmt->fetchAll();

// Format response
foreach ($members as &$member) {
    $member['id'] = (int) $member['id'];
}

echo json_encode([
    'success' => true,
    'total'   => count($members),
    'members' => $members
]);