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

$tempToken = $_COOKIE['2fa_token'] ?? null;

if (!$tempToken) {
    json_response(401, ["ok" => false, "error" => "Session expired. Please login again."]);
}

try {
    $pdo = db();
    
    // Retrieve user data from temporary storage
    $stmt = $pdo->prepare("
        SELECT code FROM two_factor_codes
        WHERE email = :email 
        AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute(["email" => "TEMP_TOKEN_" . $tempToken]);
    $tempData = $stmt->fetch();
    
    if (!$tempData) {
        json_response(401, ["ok" => false, "error" => "Session expired. Please login again."]);
    }
    
    $userData = json_decode(base64_decode($tempData['code']), true);
    if (!is_array($userData) || !isset($userData['id']) || !isset($userData['email'])) {
        json_response(401, ["ok" => false, "error" => "Invalid session data. Please login again."]);
    }
    
    $userId = (int)$userData['id'];
    $email = (string)$userData['email'];
    
    // Send new 2FA code
    $result = send2FAVerification($userId, $email);
    
    if (!$result['success']) {
        json_response(500, ["ok" => false, "error" => $result['error'] ?? "Failed to send verification code."]);
    }
    
    json_response(200, [
        "ok" => true,
        "message" => "New verification code sent to your email."
    ]);

} catch (PDOException $e) {
    error_log("Resend 2FA Error: " . $e->getMessage());
    json_response(500, ["ok" => false, "error" => "Server error."]);
}

