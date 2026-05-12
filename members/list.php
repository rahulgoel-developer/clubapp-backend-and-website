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
        u.id, u.name, u.profile_photo,
        u.country_code, u.phone,
        u.birth_date, u.blood_group,
        u.gender, u.city, u.state,
        u.rotary_id, u.admission_date,
        mb.designation
    FROM users u
    LEFT JOIN member_business mb ON mb.user_id = u.id
    WHERE u.role = 'member'
    ORDER BY u.name ASC
");
$stmt->execute();
$members = $stmt->fetchAll();

foreach ($members as &$member) {
    $member['id'] = (int) $member['id'];
}

echo json_encode([
    'success' => true,
    'total'   => count($members),
    'members' => $members
]);