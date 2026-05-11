<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

function authenticate(string $requiredRole = null): object {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization token missing']);
        exit;
    }

    $token = substr($authHeader, 7);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGO));

        // Role check
        if ($requiredRole && $decoded->role !== $requiredRole) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Admins only.']);
            exit;
        }

        return $decoded;

    } catch (ExpiredException $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token expired', 'code' => 'TOKEN_EXPIRED']);
        exit;
    } catch (SignatureInvalidException $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token signature']);
        exit;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalid']);
        exit;
    }
}

/*
How to use it in any endpoint:

php
// Any logged-in member can access:
$user = authenticate();

// Admin only:
$user = authenticate('admin');

*/