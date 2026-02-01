<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

error_reporting(E_ALL);

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";

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

    // Set authentication cookies
    setUserCookies(
        (int)$user["id"],
        (string)$user["username"],
        (string)$user["email"],
        (int)$user["is_admin"]
    );

    // Update last_login (optional)
    $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
    $upd->execute(["id" => $user["id"]]);

    json_response(200, [
        "ok" => true,
        "message" => "Logged in successfully.",
        "user" => [
            "id" => (int)$user["id"],
            "username" => (string)$user["username"],
            "email" => (string)$user["email"],
            "is_admin" => (int)$user["is_admin"]
        ]
    ]);

} catch (PDOException $e) {
    json_response(500, ["ok" => false, "error" => "Server error."]);
}
