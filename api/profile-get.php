<?php
// Get user profile data from the database
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";

function json_response(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isAuthenticated()) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

$userId = getUserId();
if ($userId === null) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.created_at,
            u.is_admin,
            p.full_name,
            p.country,
            p.profile_picture_url
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(404, ["ok" => false, "error" => "User not found"]);
    }

    json_response(200, ["ok" => true, "user" => $user]);
} catch (PDOException $e) {
    json_response(500, ["ok" => false, "error" => "Server error"]);
}
