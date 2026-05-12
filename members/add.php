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

// Check duplicate email
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert main user record
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, email, password, role,
            country_code, phone,
            birth_date, anniversary_date, blood_group,
            country_code_2, phone_2,
            gender, language, introduction,
            rotary_id, admission_date,
            facebook, instagram, linkedin, twitter, youtube, website,
            address, state, city, zip_code
        ) VALUES (
            ?, ?, ?, 'member',
            ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $name,
        $email,
        password_hash($password, PASSWORD_BCRYPT),
        trim($input['country_code']      ?? ''),
        trim($input['phone']             ?? ''),
        $input['birth_date']             ?? null,
        $input['anniversary_date']       ?? null,
        trim($input['blood_group']       ?? ''),
        trim($input['country_code_2']    ?? ''),
        trim($input['phone_2']           ?? ''),
        trim($input['gender']            ?? ''),
        trim($input['language']          ?? ''),
        trim($input['introduction']      ?? ''),
        trim($input['rotary_id']         ?? ''),
        $input['admission_date']         ?? null,
        trim($input['facebook']          ?? ''),
        trim($input['instagram']         ?? ''),
        trim($input['linkedin']          ?? ''),
        trim($input['twitter']           ?? ''),
        trim($input['youtube']           ?? ''),
        trim($input['website']           ?? ''),
        trim($input['address']           ?? ''),
        trim($input['state']             ?? ''),
        trim($input['city']              ?? ''),
        trim($input['zip_code']          ?? ''),
    ]);

    $userId = $pdo->lastInsertId();

    // Insert business details if provided
    $business = $input['business'] ?? null;
    if ($business) {
        $pdo->prepare("
            INSERT INTO member_business (
                user_id, business_name, business_email,
                designation, classification, keywords,
                country_code, phone,
                address, state, city, zip_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            trim($business['business_name']  ?? ''),
            trim($business['business_email'] ?? ''),
            trim($business['designation']    ?? ''),
            trim($business['classification'] ?? ''),
            trim($business['keywords']       ?? ''),
            trim($business['country_code']   ?? ''),
            trim($business['phone']          ?? ''),
            trim($business['address']        ?? ''),
            trim($business['state']          ?? ''),
            trim($business['city']           ?? ''),
            trim($business['zip_code']       ?? ''),
        ]);
    }

    // Insert family members if provided
    $familyMembers = $input['family_members'] ?? [];
    if (!empty($familyMembers)) {
        $famStmt = $pdo->prepare("
            INSERT INTO family_members (user_id, name, relation)
            VALUES (?, ?, ?)
        ");
        foreach ($familyMembers as $member) {
            $famName = trim($member['name'] ?? '');
            if (!empty($famName)) {
                $famStmt->execute([
                    $userId,
                    $famName,
                    trim($member['relation'] ?? ''),
                ]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'message'   => 'Member added successfully',
        'member_id' => $userId
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add member: ' . $e->getMessage()]);
}