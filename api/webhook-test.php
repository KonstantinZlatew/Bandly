<?php
// Simple test endpoint to verify webhook is accessible
header("Content-Type: application/json");

// #region agent log
file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_webtest1','timestamp'=>time()*1000,'location'=>'webhook-test.php:5','message'=>'Webhook test endpoint called','data'=>['method'=>$_SERVER['REQUEST_METHOD'],'hasPayload'=>!empty(file_get_contents('php://input'))],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A'])."\n", FILE_APPEND);
// #endregion

echo json_encode([
    'status' => 'ok',
    'message' => 'Webhook endpoint is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD']
]);
?>

