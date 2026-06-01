<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

error_reporting(E_ALL);

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/two_factor.php";

/**
 * Send JSON response and exit.
 *
 * @param integer $code    HTTP status code.
 * @param array   $payload Response data.
 * @return void
 */
function json_response(int $code, array $payload): void
{
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

$email = trim((string)($data["email"] ?? ""));
$password = (string)($data["password"] ?? "");

// Backend validation
if ($email === "" || $password === "") {
    json_response(400, ["ok" => false, "error" => "Please fill in all fields."]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(400, ["ok" => false, "error" => "Invalid email address."]);
}

try {
    $pdo = db();

    // Find user by email
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, is_admin FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(["email" => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(401, ["ok" => false, "error" => "Invalid email or password."]);
    }

    // Verify password
    if (!password_verify($password, (string)$user["password_hash"])) {
        json_response(401, ["ok" => false, "error" => "Invalid email or password."]);
    }

    // Rehash password if needed
    if (password_needs_rehash((string)$user["password_hash"], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $upd->execute(["h" => $newHash, "id" => $user["id"]]);
    }

    // Admins skip 2FA and are logged in directly
    if ((int)$user["is_admin"] === 1) {
        setUserCookies((int)$user["id"], (string)$user["username"], (string)$user["email"], 1);
        $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $upd->execute(["id" => $user["id"]]);
        json_response(200, [
            "ok"           => true,
            "message"      => "Login successful.",
            "requires_2fa" => false,
            "redirect"     => "admin/index.php"
        ]);
    }

    // Send 2FA code instead of directly logging in
    try {
        $result = send2FAVerification((int)$user["id"], (string)$user["email"]);

        if (!$result['success']) {
            $errorMsg = $result['error'] ?? "Failed to send verification code.";
            if (
                strpos($errorMsg, "two_factor_codes") !== false ||
                strpos($errorMsg, "does not exist") !== false
            ) {
                json_response(500, [
                    "ok" => false,
                    "error" => "Database setup incomplete. Please run: config/two_factor_auth_schema.sql"
                ]);
            } else {
                json_response(500, ["ok" => false, "error" => $errorMsg]);
            }
        }
    } catch (Exception $e) {
        error_log("2FA Send Error: " . $e->getMessage());
        json_response(500, [
            "ok" => false,
            "error" => "Failed to send verification code: " . (ini_get('display_errors') ? $e->getMessage() : "Please check server configuration")
        ]);
    }

    // Set a short-lived cookie with the user ID so verify-2fa.php knows who is verifying.
    // Security comes from the 2FA code itself — no one can complete login without it.
    setcookie('2fa_pending_uid', (string)$user["id"], [
        'expires'  => time() + 900,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    json_response(200, [
        "ok" => true,
        "message" => "Verification code sent to your email.",
        "requires_2fa" => true,
        "email" => (string)$user["email"]
    ]);
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    error_log("Login Error Trace: " . $e->getTraceAsString());

    // Check if it's a table missing error
    if (
        strpos($e->getMessage(), "two_factor_codes") !== false ||
        strpos($e->getMessage(), "doesn't exist") !== false
    ) {
        json_response(500, [
            "ok" => false,
            "error" => "Database table missing. Please run the migration: config/two_factor_auth_schema.sql"
        ]);
    } else {
        json_response(500, [
            "ok" => false,
            "error" => "Server error: " . (ini_get('display_errors') ? $e->getMessage() : "Database connection failed")
        ]);
    }
} catch (Exception $e) {
    error_log("Login General Error: " . $e->getMessage());
    json_response(500, [
        "ok" => false,
        "error" => "Server error: " . (ini_get('display_errors') ? $e->getMessage() : "An error occurred")
    ]);
}
