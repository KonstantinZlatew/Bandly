<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

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

if (!isAuthenticated()) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

$userId = getUserId();
if ($userId === null) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}


$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;


$username = isset($data["username"]) ? trim((string)$data["username"]) : null;
$email = isset($data["email"]) ? trim((string)$data["email"]) : null;
$fullName = isset($data["full_name"]) ? trim((string)$data["full_name"]) : null;
$country = isset($data["country"]) ? trim((string)$data["country"]) : null;

// Validate if user tries to change it
if ($username !== null && $username !== "" && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    json_response(400, ["ok" => false, "error" => "Username must be 3-30 chars (letters, numbers, underscore)."]);
}

if ($email !== null && $email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(400, ["ok" => false, "error" => "Invalid email address."]);
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Update users table (username/email)
    if ($username !== null || $email !== null) {
        // If changing username, check uniqueness
        if ($username !== null && $username !== "") {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id <> :id LIMIT 1");
            $stmt->execute(["u" => $username, "id" => $userId]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                json_response(409, ["ok" => false, "error" => "Username is already taken."]);
            }
        }

        // If changing email, check uniqueness
        if ($email !== null && $email !== "") {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e AND id <> :id LIMIT 1");
            $stmt->execute(["e" => $email, "id" => $userId]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                json_response(409, ["ok" => false, "error" => "Email is already registered."]);
            }
        }

        // Build dynamic update
        $fields = [];
        $params = ["id" => $userId];

        if ($username !== null && $username !== "") {
            $fields[] = "username = :username";
            $params["username"] = $username;
        }

        if ($email !== null && $email !== "") {
            $fields[] = "email = :email";
            $params["email"] = $email;
        }

        if (!empty($fields)) {
            $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Update cookies to keep them consistent
            if (isset($params["username"])) updateUserCookie('username', $params["username"]);
            if (isset($params["email"])) updateUserCookie('email', $params["email"]);
        }
    }

    // Ensure user_profiles row exists
    $stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = :id LIMIT 1");
    $stmt->execute(["id" => $userId]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (:id)");
        $stmt->execute(["id" => $userId]);
    }

    // Update user_profiles table
    $profileFields = [];
    $profileParams = ["id" => $userId];

    if ($fullName !== null) {
        $profileFields[] = "full_name = :full_name";
        $profileParams["full_name"] = ($fullName === "" ? null : $fullName);
    }

    if ($country !== null) {
        $profileFields[] = "country = :country";
        $profileParams["country"] = ($country === "" ? null : $country);
    }

    if (!empty($profileFields)) {
        $sql = "UPDATE user_profiles SET " . implode(", ", $profileFields) . " WHERE user_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($profileParams);
    }

    $pdo->commit();
    json_response(200, ["ok" => true, "message" => "Profile updated successfully."]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(500, ["ok" => false, "error" => "Server error"]);
}
