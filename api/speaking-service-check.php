<?php
/**
 * Check if speaking service and worker are running
 */

header("Content-Type: application/json; charset=utf-8");

$AI_SERVICE_URL = getenv('SPEAKING_AI_SERVICE_URL') ?: 'http://localhost:8001';

$checks = [
    'python_service' => false,
    'python_service_error' => null,
    'worker_running' => false,
    'pending_jobs' => 0
];

// Check Python service
try {
    $ch = curl_init($AI_SERVICE_URL . '/health');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!$error && $httpCode === 200) {
        $checks['python_service'] = true;
    } else {
        $checks['python_service_error'] = $error ?: "HTTP $httpCode";
    }
} catch (Exception $e) {
    $checks['python_service_error'] = $e->getMessage();
}

// Check pending jobs and worker status
try {
    require_once __DIR__ . "/../config/db.php";
    $pdo = db();
    
    // Count pending jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM speaking_submissions WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->fetch();
    $checks['pending_jobs'] = (int)($result['count'] ?? 0);
    
    // Check if worker has been active recently (within last 5 minutes)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM worker_instances 
        WHERE status = 'active' 
        AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $checks['worker_running'] = ((int)($result['count'] ?? 0)) > 0;
    
} catch (Exception $e) {
    $checks['db_error'] = $e->getMessage();
}

echo json_encode([
    'ok' => true,
    'checks' => $checks,
    'service_url' => $AI_SERVICE_URL
], JSON_PRETTY_PRINT);
