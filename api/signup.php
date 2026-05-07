<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";

if (!function_exists('db')) {
    throw new RuntimeException("Database function 'db' not found. Check config/db.php");
}

function json_response(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(405, ["ok" => false, "error" => "Method not allowed"]);
}

// Accept JSON body OR form-encoded
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$username = trim((string)($data["username"] ?? ""));
$email = trim((string)($data["email"] ?? ""));
$password = (string)($data["password"] ?? "");
$confirmPassword = (string)($data["confirmPassword"] ?? "");


if ($username === "" || $email === "" || $password === "" || $confirmPassword === "") {
    json_response(400, ["ok" => false, "error" => "Please fill in all fields."]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(400, ["ok" => false, "error" => "Invalid email address."]);
}

if ($password !== $confirmPassword) {
    json_response(400, ["ok" => false, "error" => "Passwords do not match."]);
}

// Password rules
if (strlen($password) < 8) {
    json_response(400, ["ok" => false, "error" => "Password must be at least 8 characters long."]);
}
if (!preg_match('/[A-Z]/', $password)) {
    json_response(400, ["ok" => false, "error" => "Password must contain at least one uppercase letter."]);
}
if (!preg_match('/[0-9]/', $password)) {
    json_response(400, ["ok" => false, "error" => "Password must contain at least one number."]);
}

// Restrict username chars/length
if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    json_response(400, ["ok" => false, "error" => "Username must be 3-30 chars (letters, numbers, underscore)."]);
}

try {
    $pdo = db();

    // Check existing email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(["email" => $email]);
    if ($stmt->fetch()) {
        json_response(409, ["ok" => false, "error" => "Email is already registered."]);
    }

    // Check existing username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(["username" => $username]);
    if ($stmt->fetch()) {
        json_response(409, ["ok" => false, "error" => "Username is already taken."]);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, is_admin, created_at)
        VALUES (:username, :email, :password_hash, 0, NOW())
    ");
    $stmt->execute([
        "username" => $username,
        "email" => $email,
        "password_hash" => $hash
    ]);

    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (:uid)");
    $stmt->execute(["uid" => $userId]);

    // Create default entitlements for new user
    $stmt = $pdo->prepare("INSERT INTO user_entitlements (user_id, credits_balance) VALUES (:uid, 0)");
    $stmt->execute(["uid" => $userId]);

    // Send 2FA code instead of directly logging in
    require_once __DIR__ . "/../config/two_factor.php";
    $result = send2FAVerification($userId, $email);
    
    if (!$result['success']) {
        // User is created but 2FA failed - they can still login later
        json_response(201, [
            "ok" => true,
            "message" => "Account created, but verification email failed. Please login to receive a new code.",
            "requires_2fa" => false,
            "user" => [
                "id" => $userId,
                "username" => $username,
                "email" => $email,
                "is_admin" => false
            ]
        ]);
    }

    // Store user info temporarily for 2FA verification
    $tempToken = bin2hex(random_bytes(32));
    $userData = [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'is_admin' => 0
    ];
    
    // Store in database temporarily
    $stmt = $pdo->prepare("
        INSERT INTO two_factor_codes (user_id, email, code, expires_at)
        VALUES (:user_id, :email, :code, :expires_at)
    ");
    $stmt->execute([
        "user_id" => $userId,
        "email" => "TEMP_TOKEN_" . $tempToken,
        "code" => base64_encode(json_encode($userData)),
        "expires_at" => date('Y-m-d H:i:s', time() + 900) // 15 minutes
    ]);
    
    // Set temporary cookie with token
    setcookie('2fa_token', $tempToken, time() + 900, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);

    json_response(201, [
        "ok" => true,
        "message" => "Account created. Verification code sent to your email.",
        "requires_2fa" => true,
        "email" => $email
    ]);
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? null) === 1062) {
        json_response(409, ["ok" => false, "error" => "Email or username already exists."]);
    }
    json_response(500, ["ok" => false, "error" => "Server error."]);
}
