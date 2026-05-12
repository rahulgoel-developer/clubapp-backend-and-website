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

try {
    $pdo->beginTransaction();

    // Dynamically build update query for users table
    $fields = [
        'name', 'country_code', 'phone',
        'birth_date', 'anniversary_date', 'blood_group',
        'country_code_2', 'phone_2',
        'gender', 'language', 'introduction',
        'rotary_id', 'admission_date',
        'facebook', 'instagram', 'linkedin',
        'twitter', 'youtube', 'website',
        'address', 'state', 'city', 'zip_code'
    ];

    $setClauses = [];
    $values     = [];

    foreach ($fields as $field) {
        if (array_key_exists($field, $input)) {
            $setClauses[] = "$field = ?";
            $values[]     = $input[$field] === '' ? null : trim($input[$field]);
        }
    }

    // Handle password update separately
    if (!empty($input['password'])) {
        $setClauses[] = "password = ?";
        $values[]     = password_hash(trim($input['password']), PASSWORD_BCRYPT);
    }

    // Handle email update with duplicate check
    if (!empty($input['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([trim($input['email']), $member_id]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'Email already in use']);
            exit;
        }
        $setClauses[] = "email = ?";
        $values[]     = trim($input['email']);
    }

    if (!empty($setClauses)) {
        $values[] = $member_id;
        $pdo->prepare("UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = ?")
            ->execute($values);
    }

    // Update business details if provided
    $business = $input['business'] ?? null;
    if ($business !== null) {
        // Check if business record exists
        $stmt = $pdo->prepare("SELECT id FROM member_business WHERE user_id = ?");
        $stmt->execute([$member_id]);
        $existingBusiness = $stmt->fetch();

        if ($existingBusiness) {
            $pdo->prepare("
                UPDATE member_business SET
                    business_name = ?, business_email = ?,
                    designation = ?, classification = ?, keywords = ?,
                    country_code = ?, phone = ?,
                    address = ?, state = ?, city = ?, zip_code = ?
                WHERE user_id = ?
            ")->execute([
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
                $member_id,
            ]);
        } else {
            $pdo->prepare("
                INSERT INTO member_business (
                    user_id, business_name, business_email,
                    designation, classification, keywords,
                    country_code, phone,
                    address, state, city, zip_code
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $member_id,
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
    }

    // Update family members if provided
    $familyMembers = $input['family_members'] ?? null;
    if ($familyMembers !== null) {
        // Delete existing and re-insert
        $pdo->prepare("DELETE FROM family_members WHERE user_id = ?")
            ->execute([$member_id]);

        $famStmt = $pdo->prepare("
            INSERT INTO family_members (user_id, name, relation)
            VALUES (?, ?, ?)
        ");
        foreach ($familyMembers as $member) {
            $famName = trim($member['name'] ?? '');
            if (!empty($famName)) {
                $famStmt->execute([
                    $member_id,
                    $famName,
                    trim($member['relation'] ?? ''),
                ]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Member updated successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update member: ' . $e->getMessage()]);
}