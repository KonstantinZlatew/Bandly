<?php
/**
 * Background Worker for Speaking Analysis
 * 
 * This worker processes pending speaking submissions asynchronously.
 * Safe for multiple concurrent workers using SELECT ... FOR UPDATE.
 * 
 * Usage:
 *   php speaking-worker.php                    # Run once
 *   php speaking-worker.php --daemon           # Run continuously
 *   php speaking-worker.php --max-jobs=10      # Process max 10 jobs then exit
 */

declare(strict_types=1);

require_once __DIR__ . "/config/db.php";

// Ensure PDO is available
if (!function_exists('db')) {
    die("Database configuration not found\n");
}

// Configuration
$AI_SERVICE_URL = getenv('SPEAKING_AI_SERVICE_URL') ?: 'http://localhost:8001';
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
        // Only fetch jobs with valid task_prompt and audio_url
        $stmt = $pdo->prepare("
            SELECT 
                ss.id,
                ss.user_id,
                ss.task_id,
                ss.task_prompt,
                ss.audio_url,
                ss.file_id,
                f.storage_key as audio_path
            FROM speaking_submissions ss
            LEFT JOIN files f ON ss.file_id = f.id
            WHERE ss.status = 'pending'
            AND ss.task_prompt IS NOT NULL
            AND ss.task_prompt != ''
            AND ss.audio_url IS NOT NULL
            AND ss.audio_url != ''
            ORDER BY ss.submitted_at ASC
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
        $job['task_prompt'] = $job['task_prompt'] ?? '';
        $job['audio_url'] = $job['audio_url'] ?? '';
        $job['audio_path'] = $job['audio_path'] ?? null;
        
        // Mark as processing
        $updateStmt = $pdo->prepare("
            UPDATE speaking_submissions
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
    $taskPrompt = $job['task_prompt'] ?? '';
    $audioPath = $job['audio_path'] ?? null;
    
    // Validate required fields
    if (empty($taskPrompt)) {
        throw new Exception("Missing required field: task_prompt");
    }
    
    if (!$audioPath || !file_exists(__DIR__ . '/uploads/' . $audioPath)) {
        throw new Exception("Audio file not found: " . ($audioPath ?? 'null'));
    }
    
    $fullAudioPath = __DIR__ . '/uploads/' . $audioPath;
    
    // Prepare multipart form data
    // Since file is already on server, send audio_path instead of file content
    $boundary = uniqid();
    $delimiter = '-------------' . $boundary;
    
    $postData = '';
    $postData .= '--' . $delimiter . "\r\n";
    $postData .= 'Content-Disposition: form-data; name="task_prompt"' . "\r\n\r\n";
    $postData .= $taskPrompt . "\r\n";
    
    $postData .= '--' . $delimiter . "\r\n";
    $postData .= 'Content-Disposition: form-data; name="audio_path"' . "\r\n\r\n";
    $postData .= $fullAudioPath . "\r\n";
    
    $postData .= '--' . $delimiter . '--';
    
    // Make HTTP request
    $ch = curl_init($url . '/evaluate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
        ],
        CURLOPT_TIMEOUT => 180, // 3 minute timeout for audio processing
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
    
    // Extract result from response
    if (isset($result['ok']) && $result['ok'] && isset($result['result'])) {
        return $result['result'];
    } elseif (isset($result['ok']) && !$result['ok']) {
        throw new Exception("AI service error: " . ($result['detail'] ?? $result['error'] ?? 'Unknown error'));
    } else {
        // Assume the response is the result directly
        return $result;
    }
}

// Update job status
function updateJobStatus(PDO $pdo, int $jobId, string $status, ?array $result = null, ?string $error = null): void {
    $stmt = $pdo->prepare("
        UPDATE speaking_submissions
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
    
    echo "Speaking Worker $WORKER_ID started\n";
    echo "AI Service URL: $AI_SERVICE_URL\n";
    
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
            
            echo "Processing job {$job['id']} (user {$job['user_id']}, task {$job['task_id']})\n";
            
            // Validate job has required fields
            if (empty($job['task_prompt']) || empty($job['audio_path'])) {
                updateJobStatus($pdo, (int)$job['id'], 'failed', null, 
                    "Missing required fields: task_prompt=" . (empty($job['task_prompt']) ? 'empty' : 'present') . 
                    ", audio_path=" . (empty($job['audio_path']) ? 'empty' : 'present'));
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
    
    echo "Speaking Worker $WORKER_ID finished. Processed $jobsProcessed jobs.\n";
}

// Run worker
try {
    runWorker();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
