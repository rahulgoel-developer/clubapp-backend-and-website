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

$input = json_decode(file_get_contents('php://input'), true);

// Required fields
$name     = trim($input['name']     ?? '');
$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name, email and password are required']);
    exit;
}

$pdo = getDB();

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

// Optional fields
$phone           = $input['phone']            ?? null;
$country_code    = $input['country_code']     ?? null;
$birth_date      = $input['birth_date']       ?? null;
$anniversary_date= $input['anniversary_date'] ?? null;
$blood_group     = $input['blood_group']      ?? null;
$gender          = $input['gender']           ?? null;
$language        = $input['language']         ?? null;
$introduction    = $input['introduction']     ?? null;
$rotary_id       = $input['rotary_id']        ?? null;
$admission_date  = $input['admission_date']   ?? null;
$designation     = $input['designation']      ?? null;
$facebook        = $input['facebook']         ?? null;
$instagram       = $input['instagram']        ?? null;
$linkedin        = $input['linkedin']         ?? null;
$twitter         = $input['twitter']          ?? null;
$youtube         = $input['youtube']          ?? null;
$website         = $input['website']          ?? null;
$address         = $input['address']          ?? null;
$state           = $input['state']            ?? null;
$city            = $input['city']             ?? null;
$zip_code        = $input['zip_code']         ?? null;

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO users (
        name, email, password, role,
        country_code, phone,
        birth_date, anniversary_date, blood_group,
        gender, language, introduction,
        rotary_id, admission_date, designation,
        facebook, instagram, linkedin, twitter, youtube, website,
        address, state, city, zip_code
    ) VALUES (
        ?, ?, ?, 'member',
        ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?
    )
");

$stmt->execute([
    $name, $email, $hashedPassword,
    $country_code, $phone,
    $birth_date, $anniversary_date, $blood_group,
    $gender, $language, $introduction,
    $rotary_id, $admission_date, $designation,
    $facebook, $instagram, $linkedin, $twitter, $youtube, $website,
    $address, $state, $city, $zip_code
]);

$newUserId = $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'message' => 'Member added successfully',
    'user_id' => $newUserId
]);