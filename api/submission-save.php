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

if (!isAuthenticated()) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

$userId = getUserId();
if ($userId === null) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(405, ["ok" => false, "error" => "Method not allowed"]);
}

try {
    $pdo = db();
    
    // Get form data
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : null;
    $taskType = isset($_POST['task_type']) ? trim($_POST['task_type']) : '';
    $taskPrompt = isset($_POST['task_prompt']) ? trim($_POST['task_prompt']) : '';
    $examVariantId = isset($_POST['exam_variant_id']) ? (int)$_POST['exam_variant_id'] : null;
    $essay = isset($_POST['essay']) ? trim($_POST['essay']) : '';
    
    // Validate required fields
    if (!$taskId || !$taskType || !$examVariantId || !$essay) {
        json_response(400, [
            "ok" => false, 
            "error" => "Missing required fields: task_id, task_type, exam_variant_id, essay"
        ]);
    }
    
    // Validate task exists
    $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? LIMIT 1");
    $stmt->execute([$taskId]);
    if (!$stmt->fetch()) {
        json_response(404, ["ok" => false, "error" => "Task not found"]);
    }
    
    // Validate exam_variant exists
    $stmt = $pdo->prepare("SELECT id FROM exam_variants WHERE id = ? LIMIT 1");
    $stmt->execute([$examVariantId]);
    if (!$stmt->fetch()) {
        json_response(404, ["ok" => false, "error" => "Exam variant not found"]);
    }
    
    // Calculate word count
    $wordCount = str_word_count($essay);
    
    // Handle image upload (for academic_task_1)
    $imageFileId = null;
    if ($taskType === 'academic_task_1' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageFile = $_FILES['image'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            json_response(400, ["ok" => false, "error" => "Invalid image type. Allowed: JPEG, PNG, GIF, WebP"]);
        }
        
        // Validate file size (5MB limit)
        if ($imageFile['size'] > 5 * 1024 * 1024) {
            json_response(400, ["ok" => false, "error" => "Image size must be less than 5MB"]);
        }
        
        // Generate unique filename
        $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
        $storageKey = 'submissions/' . date('Y/m/') . uniqid() . '_' . $userId . '.' . $extension;
        $uploadDir = __DIR__ . '/../uploads/' . dirname($storageKey);
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = __DIR__ . '/../uploads/' . $storageKey;
        
        // Move uploaded file
        if (!move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
            json_response(500, ["ok" => false, "error" => "Failed to save image"]);
        }
        
        // Save file record to database
        $stmt = $pdo->prepare("
            INSERT INTO files (storage_key, mime, size_bytes, uploaded_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $storageKey,
            $mimeType,
            $imageFile['size'],
            $userId
        ]);
        $imageFileId = $pdo->lastInsertId();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert into writing_submissions
        $stmt = $pdo->prepare("
            INSERT INTO writing_submissions (
                user_id,
                exam_variant_id,
                task_id,
                content,
                word_count,
                task_prompt,
                task_type,
                image_file_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $examVariantId,
            $taskId,
            $essay,
            $wordCount,
            $taskPrompt,
            $taskType,
            $imageFileId
        ]);
        $submissionId = $pdo->lastInsertId();
        
        // Mark task as completed in user_task_completions
        // Use INSERT IGNORE to handle case where user already completed this task
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_task_completions (
                user_id,
                task_id,
                exam_variant_id
            ) VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $taskId,
            $examVariantId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        json_response(200, [
            "ok" => true,
            "submission_id" => $submissionId,
            "message" => "Submission saved successfully"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Delete uploaded file if transaction failed
        if ($imageFileId && isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        throw $e;
    }
    
} catch (PDOException $e) {
    json_response(500, [
        "ok" => false, 
        "error" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    json_response(500, [
        "ok" => false, 
        "error" => "Server error: " . $e->getMessage()
    ]);
}
