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

$decoded = authenticate();

$member_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$member_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Member ID is required']);
    exit;
}

$pdo = getDB();

// Fetch main user + business
$stmt = $pdo->prepare("
    SELECT
        u.id, u.name, u.email, u.profile_photo,
        u.country_code, u.phone,
        u.country_code_2, u.phone_2,
        u.birth_date, u.anniversary_date,
        u.blood_group, u.gender, u.language,
        u.introduction,
        u.rotary_id, u.admission_date,
        u.facebook, u.instagram, u.linkedin,
        u.twitter, u.youtube, u.website,
        u.address, u.city, u.state, u.zip_code,
        u.family_photo,
        mb.business_name, mb.business_email,
        mb.designation, mb.classification,
        mb.keywords,
        mb.country_code AS business_country_code,
        mb.phone AS business_phone,
        mb.address AS business_address,
        mb.city AS business_city,
        mb.state AS business_state,
        mb.zip_code AS business_zip_code
    FROM users u
    LEFT JOIN member_business mb ON mb.user_id = u.id
    WHERE u.id = :id AND u.role = 'member'
    LIMIT 1
");
$stmt->execute([':id' => $member_id]);
$member = $stmt->fetch();

if (!$member) {
    http_response_code(404);
    echo json_encode(['error' => 'Member not found']);
    exit;
}

// Fetch family members
$stmt2 = $pdo->prepare("
    SELECT id, name, relation, photo
    FROM family_members
    WHERE user_id = :id
    ORDER BY id ASC
");
$stmt2->execute([':id' => $member_id]);
$family = $stmt2->fetchAll();

// Build response
$response = [
    'success' => true,
    'member'  => [
        'id'               => (int) $member['id'],
        'name'             => $member['name'],
        'email'            => $member['email'],
        'profile_photo'    => $member['profile_photo'],
        'rotary_id'        => $member['rotary_id'],
        'admission_date'   => $member['admission_date'],
        'introduction'     => $member['introduction'],

        'personal' => [
            'gender'           => $member['gender'],
            'birth_date'       => $member['birth_date'],
            'anniversary_date' => $member['anniversary_date'],
            'blood_group'      => $member['blood_group'],
            'language'         => $member['language'],
            'country_code'     => $member['country_code'],
            'phone'            => $member['phone'],
            'country_code_2'   => $member['country_code_2'],
            'phone_2'          => $member['phone_2'],
        ],

        'address' => [
            'address'  => $member['address'],
            'city'     => $member['city'],
            'state'    => $member['state'],
            'zip_code' => $member['zip_code'],
        ],

        'social' => [
            'facebook'  => $member['facebook'],
            'instagram' => $member['instagram'],
            'linkedin'  => $member['linkedin'],
            'twitter'   => $member['twitter'],
            'youtube'   => $member['youtube'],
            'website'   => $member['website'],
        ],

        'business' => [
            'business_name'     => $member['business_name'],
            'business_email'    => $member['business_email'],
            'designation'       => $member['designation'],
            'classification'    => $member['classification'],
            'keywords'          => $member['keywords'],
            'country_code'      => $member['business_country_code'],
            'phone'             => $member['business_phone'],
            'address'           => $member['business_address'],
            'city'              => $member['business_city'],
            'state'             => $member['business_state'],
            'zip_code'          => $member['business_zip_code'],
        ],

        'family' => [
            'family_photo' => $member['family_photo'],
            'members'      => $family,
        ],
    ]
];

echo json_encode($response);