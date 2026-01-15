<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/db.php";

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

    json_response(201, ["ok" => true, "message" => "Account created successfully."]);
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? null) === 1062) {
        json_response(409, ["ok" => false, "error" => "Email or username already exists."]);
    }
    json_response(500, ["ok" => false, "error" => "Server error."]);
}
