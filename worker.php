<?php
/**
 * Background Worker for Essay Analysis
 * 
 * This worker processes pending essay submissions asynchronously.
 * Safe for multiple concurrent workers using SELECT ... FOR UPDATE.
 * 
 * Usage:
 *   php worker.php                    # Run once
 *   php worker.php --daemon           # Run continuously
 *   php worker.php --max-jobs=10      # Process max 10 jobs then exit
 */

declare(strict_types=1);

require_once __DIR__ . "/config/db.php";

// Ensure PDO is available
if (!function_exists('db')) {
    die("Database configuration not found\n");
}

// Configuration
$AI_SERVICE_URL = getenv('AI_SERVICE_URL') ?: 'http://localhost:8000';
$WORKER_ID = getenv('WORKER_ID') ?: gethostname() . '-' . getmypid();
$MAX_JOBS = isset($argv) && in_array('--max-jobs', $argv) 
    ? (int)($argv[array_search('--max-jobs', $argv) + 1] ?? 10)
    : null;
$DAEMON_MODE = isset($argv) && in_array('--daemon', $argv);
$SLEEP_SECONDS = 2; // Sleep between job checks

// Worker heartbeat
function updateHeartbeat(PDO $pdo, string $workerId): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO worker_instances (worker_id, last_heartbeat, status)
            VALUES (?, NOW(), 'active')
            ON DUPLICATE KEY UPDATE 
                last_heartbeat = NOW(),
                status = 'active'
        ");
        $stmt->execute([$workerId]);
    } catch (Exception $e) {
        // Ignore heartbeat errors
    }
}

// Fetch next pending job with row-level lock
function fetchNextJob(PDO $pdo): ?array {
    $pdo->beginTransaction();
    
    try {
        // SELECT ... FOR UPDATE locks the row for this transaction
        // Only fetch jobs with valid task_type and task_prompt
        $stmt = $pdo->prepare("
            SELECT 
                ws.id,
                ws.user_id,
                ws.task_id,
                ws.content,
                ws.task_prompt,
                ws.task_type,
                ws.image_file_id,
                f.storage_key as image_path,
                ws.word_count
            FROM writing_submissions ws
            LEFT JOIN files f ON ws.image_file_id = f.id
            WHERE ws.status = 'pending'
            AND ws.task_type IS NOT NULL
            AND ws.task_type != ''
            AND ws.task_prompt IS NOT NULL
            AND ws.task_prompt != ''
            AND ws.content IS NOT NULL
            AND ws.content != ''
            ORDER BY ws.submitted_at ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute();
        $job = $stmt->fetch();
        
        if (!$job) {
            $pdo->rollBack();
            return null;
        }
        
        // Ensure all fields are set (handle NULL values)
        $job['task_type'] = $job['task_type'] ?? '';
        $job['task_prompt'] = $job['task_prompt'] ?? '';
        $job['content'] = $job['content'] ?? '';
        $job['image_path'] = $job['image_path'] ?? null;
        
        // Mark as processing
        $updateStmt = $pdo->prepare("
            UPDATE writing_submissions
            SET status = 'processing'
            WHERE id = ?
        ");
        $updateStmt->execute([$job['id']]);
        
        $pdo->commit();
        return $job;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Call AI service
function callAIService(string $url, array $job): array {
    $taskType = $job['task_type'] ?? '';
    $taskPrompt = $job['task_prompt'] ?? '';
    $essay = $job['content'] ?? '';
    
    // Validate required fields
    if (empty($taskType) || empty($taskPrompt) || empty($essay)) {
        throw new Exception("Missing required fields: task_type='" . ($taskType ?: 'empty') . "', task_prompt='" . ($taskPrompt ?: 'empty') . "', essay='" . ($essay ?: 'empty') . "'");
    }
    
    // Map task types to AI service expected values
    // AI service expects: academic_task_1, general_task_1, or task_2
    $aiTaskType = $taskType;
    if ($taskType === 'academic_task_2' || $taskType === 'general_task_2') {
        $aiTaskType = 'task_2';
    }
    
    // Validate mapped task type
    $allowedTypes = ['academic_task_1', 'general_task_1', 'task_2'];
    if (!in_array($aiTaskType, $allowedTypes)) {
        throw new Exception("Invalid task_type for AI service: '$taskType' (mapped to '$aiTaskType'). Allowed: " . implode(', ', $allowedTypes));
    }
    
    // Prepare request data
    $data = [
        'task_type' => $aiTaskType,
        'task_prompt' => $taskPrompt,
        'essay' => $essay
    ];
    
    // Handle image if present
    $files = [];
    if (!empty($job['image_path']) && file_exists(__DIR__ . '/uploads/' . $job['image_path'])) {
        $imagePath = __DIR__ . '/uploads/' . $job['image_path'];
        $imageData = file_get_contents($imagePath);
        $imageBase64 = base64_encode($imageData);
        
        // Determine image format
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);
        
        $format = 'jpeg';
        if (strpos($mimeType, 'png') !== false) $format = 'png';
        elseif (strpos($mimeType, 'gif') !== false) $format = 'gif';
        elseif (strpos($mimeType, 'webp') !== false) $format = 'webp';
        
        $data['image_base64'] = 'data:image/' . $format . ';base64,' . $imageBase64;
    }
    
    // Make HTTP request
    $ch = curl_init($url . '/evaluate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 120, // 2 minute timeout
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL error: " . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("AI service returned HTTP $httpCode: " . substr($response, 0, 200));
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response: " . json_last_error_msg());
    }
    
    return $result;
}

// Update job status
function updateJobStatus(PDO $pdo, int $jobId, string $status, ?array $result = null, ?string $error = null): void {
    $stmt = $pdo->prepare("
        UPDATE writing_submissions
        SET 
            status = ?,
            analysis_result = ?,
            error_message = ?,
            processed_at = NOW()
        WHERE id = ?
    ");
    
    $resultJson = $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null;
    
    $stmt->execute([
        $status,
        $resultJson,
        $error,
        $jobId
    ]);
}

// Main worker loop
function runWorker(): void {
    global $pdo, $WORKER_ID, $MAX_JOBS, $DAEMON_MODE, $SLEEP_SECONDS, $AI_SERVICE_URL;
    
    $pdo = db();
    $jobsProcessed = 0;
    
    echo "Worker $WORKER_ID started\n";
    
    while (true) {
        try {
            // Update heartbeat
            updateHeartbeat($pdo, $WORKER_ID);
            
            // Fetch next job
            $job = fetchNextJob($pdo);
            
            if (!$job) {
                if (!$DAEMON_MODE) {
                    echo "No pending jobs. Exiting.\n";
                    break;
                }
                sleep($SLEEP_SECONDS);
                continue;
            }
            
            echo "Processing job {$job['id']} (user {$job['user_id']}, task {$job['task_id']}, type: {$job['task_type']})\n";
            
            // Validate job has required fields
            if (empty($job['task_type']) || empty($job['task_prompt']) || empty($job['content'])) {
                updateJobStatus($pdo, (int)$job['id'], 'failed', null, 
                    "Missing required fields: task_type=" . ($job['task_type'] ?? 'NULL') . 
                    ", task_prompt=" . (empty($job['task_prompt']) ? 'empty' : 'present') . 
                    ", content=" . (empty($job['content']) ? 'empty' : 'present'));
                echo "Job {$job['id']} skipped: Missing required fields\n";
                continue;
            }
            
            try {
                // Call AI service
                $result = callAIService($AI_SERVICE_URL, $job);
                
                // Save result
                updateJobStatus($pdo, (int)$job['id'], 'done', $result);
                
                echo "Job {$job['id']} completed successfully\n";
                $jobsProcessed++;
                
            } catch (Exception $e) {
                // Mark as failed
                updateJobStatus($pdo, (int)$job['id'], 'failed', null, $e->getMessage());
                echo "Job {$job['id']} failed: " . $e->getMessage() . "\n";
            }
            
            // Check max jobs limit
            if ($MAX_JOBS !== null && $jobsProcessed >= $MAX_JOBS) {
                echo "Reached max jobs limit ($MAX_JOBS). Exiting.\n";
                break;
            }
            
            // Small delay between jobs
            usleep(100000); // 0.1 seconds
            
        } catch (Exception $e) {
            echo "Worker error: " . $e->getMessage() . "\n";
            sleep($SLEEP_SECONDS);
        }
    }
    
    echo "Worker $WORKER_ID finished. Processed $jobsProcessed jobs.\n";
}

// Run worker
try {
    runWorker();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
