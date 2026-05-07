<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

error_reporting(E_ALL);

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/two_factor.php";

function json_response(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(405, ["ok" => false, "error" => "Method not allowed"]);
}

// Read JSON body OR fallback to form POST
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$code = trim((string)($data["code"] ?? ""));

// Validation
if ($code === "" || strlen($code) !== 6) {
    json_response(400, ["ok" => false, "error" => "Please enter a valid 6-digit code."]);
}

$pendingUid = $_COOKIE['2fa_pending_uid'] ?? null;
if (!$pendingUid || !ctype_digit($pendingUid)) {
    json_response(401, ["ok" => false, "error" => "Session expired. Please login again."]);
}

try {
    $pdo = db();

    $userId = (int)$pendingUid;
    $stmt = $pdo->prepare("SELECT id, username, email, is_admin FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $userId]);
    $userData = $stmt->fetch();
    if (!$userData) {
        json_response(401, ["ok" => false, "error" => "Session expired. Please login again."]);
    }

    $email = (string)$userData['email'];

    // Verify the 2FA code
    if (!verify2FACode($userId, $email, $code)) {
        json_response(401, ["ok" => false, "error" => "Invalid or expired verification code."]);
    }
    
    // Code is valid - set authentication cookies
    setUserCookies(
        $userId,
        (string)$userData['username'],
        $email,
        (int)($userData['is_admin'] ?? 0)
    );
    
    // Update last_login
    $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $upd->execute(["id" => $userId]);
    
    // Clear the pending UID cookie
    setcookie('2fa_pending_uid', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    json_response(200, [
        "ok" => true,
        "message" => "Verification successful. Logging you in...",
        "user" => [
            "id" => $userId,
            "username" => (string)$userData['username'],
            "email" => $email,
            "is_admin" => (int)($userData['is_admin'] ?? 0)
        ]
    ]);

} catch (PDOException $e) {
    error_log("2FA Verification Error: " . $e->getMessage());
    json_response(500, ["ok" => false, "error" => "Server error."]);
}

