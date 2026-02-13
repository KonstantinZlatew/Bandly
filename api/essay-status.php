<?php
/**
 * Essay Status Endpoint
 * 
 * Returns the current status and analysis result for a submission.
 */

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

$submissionId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$submissionId) {
    json_response(400, ["ok" => false, "error" => "Missing submission ID"]);
}

try {
    $pdo = db();
    
    // Fetch submission (only if it belongs to the user)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            status,
            analysis_result,
            error_message,
            submitted_at,
            processed_at,
            word_count
        FROM writing_submissions
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$submissionId, $userId]);
    $submission = $stmt->fetch();
    
    if (!$submission) {
        json_response(404, ["ok" => false, "error" => "Submission not found"]);
    }
    
    // Parse JSON result if present
    $analysisResult = null;
    if ($submission['analysis_result']) {
        $analysisResult = json_decode($submission['analysis_result'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $analysisResult = null;
        }
    }
    
    json_response(200, [
        "ok" => true,
        "submission" => [
            "id" => (int)$submission['id'],
            "status" => $submission['status'],
            "analysis_result" => $analysisResult,
            "error_message" => $submission['error_message'],
            "submitted_at" => $submission['submitted_at'],
            "processed_at" => $submission['processed_at'],
            "word_count" => $submission['word_count'] ? (int)$submission['word_count'] : null
        ]
    ]);
    
} catch (PDOException $e) {
    json_response(500, [
        "ok" => false, 
        "error" => "Database error"
    ]);
} catch (Exception $e) {
    json_response(500, [
        "ok" => false, 
        "error" => "Server error"
    ]);
}
