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

if (!isset($_FILES["profile_picture"]) || $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {
    json_response(400, ["ok" => false, "error" => "No file uploaded or upload error occurred"]);
}

$file = $_FILES["profile_picture"];

// Validate file type
$allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    json_response(400, ["ok" => false, "error" => "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed."]);
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file["size"] > $maxSize) {
    json_response(400, ["ok" => false, "error" => "File size exceeds 5MB limit."]);
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . "/../uploads/profile_pictures/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = "user_" . $userId . "_" . time() . "_" . uniqid() . "." . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file["tmp_name"], $filepath)) {
    json_response(500, ["ok" => false, "error" => "Failed to save uploaded file."]);
}

$url = "uploads/profile_pictures/" . $filename;

try {
    $pdo = db();
    
    $stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = :id LIMIT 1");
    $stmt->execute(["id" => $userId]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (:id)");
        $stmt->execute(["id" => $userId]);
    }
    
    // Delete old profile picture if exists
    $stmt = $pdo->prepare("SELECT profile_picture_url FROM user_profiles WHERE user_id = :id LIMIT 1");
    $stmt->execute(["id" => $userId]);
    $old = $stmt->fetch();
    if ($old && $old["profile_picture_url"]) {
        $oldPath = __DIR__ . "/../" . $old["profile_picture_url"];
        if (file_exists($oldPath) && strpos($oldPath, "uploads/profile_pictures/") !== false) {
            @unlink($oldPath);
        }
    }
    
    // Update profile picture URL
    $stmt = $pdo->prepare("UPDATE user_profiles SET profile_picture_url = :url WHERE user_id = :id");
    $stmt->execute(["url" => $url, "id" => $userId]);
    
    // Update cookie with new profile picture URL
    updateUserCookie('profile_picture_url', $url);
    
    json_response(200, ["ok" => true, "url" => $url, "message" => "Profile picture updated successfully."]);
} catch (PDOException $e) {
    // Delete uploaded file if database update fails
    @unlink($filepath);
    json_response(500, ["ok" => false, "error" => "Server error"]);
}

