<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$payload = [
    'iss' => 'jwt-app',       // Issuer (your app name)
    'iat' => time(),          // Issued at (current time)
    'exp' => time() + JWT_EXPIRY, // Expiry time
    'user_id' => 1,           // Any custom data
    'email' => 'rahul@example.com'
];

$token = JWT::encode($payload, JWT_SECRET, JWT_ALGO);

echo "Generated Token:\n" . $token . "\n";


$token_to_verify = $token; //"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJqd3QtYXBwIiwiaWF0IjoxNzc4NDQ3MTc1LCJleHAiOjE3Nzg0NDgwNzUsInVzZXJfaWQiOjEsImVtYWlsIjoicmFodWxAZXhhbXBsZS5jb20ifQ.v2tEw6pOinDOKPmoeTHthHu7P4m5prYb8lm8s4jn2cw"; // paste the token from previous step

try {
    $decoded = JWT::decode($token_to_verify, new Key(JWT_SECRET, JWT_ALGO));
    echo "Token is VALID!\n";
    print_r((array) $decoded);
} catch (Exception $e) {
    echo "Token INVALID: " . $e->getMessage();
}