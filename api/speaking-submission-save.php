<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../includes/entitlements-check.php";
require_once __DIR__ . "/../includes/entitlements-deduct.php";

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

// Check entitlements before proceeding
$entitlementCheck = checkCanAnalyze($userId);
if (!$entitlementCheck['can_analyze']) {
    json_response(403, [
        "ok" => false, 
        "error" => $entitlementCheck['reason']
    ]);
}

try {
    $pdo = db();
    
    // Get form data
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : null;
    $taskType = isset($_POST['task_type']) ? trim($_POST['task_type']) : '';
    $taskPrompt = isset($_POST['task_prompt']) ? trim($_POST['task_prompt']) : '';
    $examVariantId = isset($_POST['exam_variant_id']) ? (int)$_POST['exam_variant_id'] : null;
    
    // Validate required fields
    if (!$taskId || !$taskType || !$examVariantId) {
        json_response(400, [
            "ok" => false, 
            "error" => "Missing required fields: task_id, task_type, exam_variant_id"
        ]);
    }
    
    // Validate audio file
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        json_response(400, [
            "ok" => false, 
            "error" => "Audio file is required"
        ]);
    }
    
    $audioFile = $_FILES['audio'];
    
    // Validate file type (accept webm, mp3, wav, ogg)
    $allowedTypes = ['audio/webm', 'audio/mp3', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/x-wav'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $audioFile['tmp_name']);
    finfo_close($finfo);
    
    // Also check file extension as fallback
    $extension = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['webm', 'mp3', 'wav', 'ogg'];
    
    if (!in_array($mimeType, $allowedTypes) && !in_array($extension, $allowedExtensions)) {
        json_response(400, [
            "ok" => false, 
            "error" => "Invalid audio file type. Allowed: WebM, MP3, WAV, OGG"
        ]);
    }
    
    // Validate file size (10MB limit for audio)
    if ($audioFile['size'] > 10 * 1024 * 1024) {
        json_response(400, [
            "ok" => false, 
            "error" => "Audio file size must be less than 10MB"
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
    
    // Generate unique filename
    $storageKey = 'speaking/' . date('Y/m/') . uniqid() . '_' . $userId . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/' . dirname($storageKey);
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = __DIR__ . '/../uploads/' . $storageKey;
    
    // Move uploaded file
    if (!move_uploaded_file($audioFile['tmp_name'], $uploadPath)) {
        json_response(500, ["ok" => false, "error" => "Failed to save audio file"]);
    }
    
    // Calculate audio duration (approximate, will be updated later if needed)
    $audioDurationSeconds = null;
    // Note: For accurate duration, you might need to use a library like getid3
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Deduct credit if user doesn't have subscription
        if (!$entitlementCheck['has_subscription']) {
            $deductResult = deductCreditForAnalysis($userId);
            if (!$deductResult['success']) {
                // Rollback and delete uploaded file
                $pdo->rollBack();
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                json_response(402, [
                    "ok" => false, 
                    "error" => $deductResult['message']
                ]);
            }
        }
        
        // Save file record to database
        $stmt = $pdo->prepare("
            INSERT INTO files (storage_key, mime, size_bytes, uploaded_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $storageKey,
            $mimeType,
            $audioFile['size'],
            $userId
        ]);
        $fileId = $pdo->lastInsertId();
        
        // Insert into speaking_submissions
        $stmt = $pdo->prepare("
            INSERT INTO speaking_submissions (
                user_id,
                exam_variant_id,
                task_id,
                task_prompt,
                audio_url,
                audio_duration_seconds,
                file_id,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $audioUrl = '/uploads/' . $storageKey; // Relative URL for frontend
        $stmt->execute([
            $userId,
            $examVariantId,
            $taskId,
            $taskPrompt,
            $audioUrl,
            $audioDurationSeconds,
            $fileId
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
            "message" => "Recording saved successfully",
            "credits_remaining" => $entitlementCheck['has_subscription'] ? null : ($entitlementCheck['credits_remaining'] - 1)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Delete uploaded file if transaction failed
        if (isset($uploadPath) && file_exists($uploadPath)) {
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
