<?php
// Manual test to simulate a successful payment and verify database operations work
session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION["user_id"])) {
    die("Please log in first");
}

$userId = (int)$_SESSION["user_id"];

echo "<h2>Manual Purchase Test</h2>";
echo "<p>User ID: $userId</p>";

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    // Get a test plan
    $stmt = $pdo->prepare("SELECT id, code, plan_type, credits_amount, duration_days, price_cents FROM plans WHERE code = 'CREDITS_10' LIMIT 1");
    $stmt->execute();
    $plan = $stmt->fetch();
    
    if (!$plan) {
        die("Plan CREDITS_10 not found");
    }
    
    $planId = (int)$plan["id"];
    $amountCents = (int)$plan["price_cents"];
    $creditsAmount = (int)$plan["credits_amount"];
    
    echo "<p>Testing with plan: " . htmlspecialchars($plan["code"]) . "</p>";
    echo "<p>Credits to add: $creditsAmount</p>";
    
    // #region agent log
    file_put_contents(__DIR__ . '/.cursor/debug.log', json_encode(['id'=>'log_'.time().'_manual1','timestamp'=>time()*1000,'location'=>'test-manual-purchase.php:25','message'=>'Starting manual purchase test','data'=>['userId'=>$userId,'planId'=>$planId,'creditsAmount'=>$creditsAmount],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
    // #endregion
    
    // Create purchase record
    $stmt = $pdo->prepare("
        INSERT INTO purchases (user_id, plan_id, provider, provider_payment_intent_id, provider_checkout_session_id, amount_cents, currency, status, paid_at)
        VALUES (:user_id, :plan_id, 'stripe', :payment_intent_id, :checkout_session_id, :amount_cents, :currency, :status, NOW())
    ");
    
    $testSessionId = 'test_' . time();
    $testPaymentIntent = 'pi_test_' . time();
    
    $result = $stmt->execute([
        "user_id" => $userId,
        "plan_id" => $planId,
        "payment_intent_id" => $testPaymentIntent,
        "checkout_session_id" => $testSessionId,
        "amount_cents" => $amountCents,
        "currency" => "EUR",
        "status" => "paid"
    ]);
    
    // #region agent log
    file_put_contents(__DIR__ . '/.cursor/debug.log', json_encode(['id'=>'log_'.time().'_manual2','timestamp'=>time()*1000,'location'=>'test-manual-purchase.php:45','message'=>'Purchase insert result','data'=>['success'=>$result,'lastInsertId'=>$pdo->lastInsertId()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
    // #endregion
    
    if (!$result) {
        throw new Exception("Failed to insert purchase");
    }
    
    $purchaseId = $pdo->lastInsertId();
    echo "<p style='color: green;'>✓ Purchase record created (ID: $purchaseId)</p>";
    
    // Update entitlements
    $stmt = $pdo->prepare("
        INSERT INTO user_entitlements (user_id, credits_balance)
        VALUES (:user_id, :credits)
        ON DUPLICATE KEY UPDATE credits_balance = credits_balance + :credits
    ");
    
    // #region agent log
    file_put_contents(__DIR__ . '/.cursor/debug.log', json_encode(['id'=>'log_'.time().'_manual3','timestamp'=>time()*1000,'location'=>'test-manual-purchase.php:60','message'=>'Before credits update','data'=>['userId'=>$userId,'creditsAmount'=>$creditsAmount],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
    // #endregion
    
    $creditsResult = $stmt->execute([
        "user_id" => $userId,
        "credits" => $creditsAmount
    ]);
    
    // #region agent log
    file_put_contents(__DIR__ . '/.cursor/debug.log', json_encode(['id'=>'log_'.time().'_manual4','timestamp'=>time()*1000,'location'=>'test-manual-purchase.php:68','message'=>'Credits update result','data'=>['success'=>$creditsResult],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
    // #endregion
    
    if (!$creditsResult) {
        throw new Exception("Failed to update credits");
    }
    
    echo "<p style='color: green;'>✓ Credits updated</p>";
    
    $pdo->commit();
    echo "<p style='color: green;'><strong>✓ Transaction committed successfully!</strong></p>";
    
    // Verify
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = :id");
    $stmt->execute(["id" => $purchaseId]);
    $purchase = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM user_entitlements WHERE user_id = :user_id");
    $stmt->execute(["user_id" => $userId]);
    $entitlements = $stmt->fetch();
    
    echo "<h3>Verification:</h3>";
    echo "<p><strong>Purchase:</strong></p><pre>" . print_r($purchase, true) . "</pre>";
    echo "<p><strong>Entitlements:</strong></p><pre>" . print_r($entitlements, true) . "</pre>";
    
    echo "<p><a href='index.php'>Go to Homepage</a> to see if credits display</p>";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // #region agent log
    file_put_contents(__DIR__ . '/.cursor/debug.log', json_encode(['id'=>'log_'.time().'_manual_err','timestamp'=>time()*1000,'location'=>'test-manual-purchase.php:95','message'=>'Exception in manual test','data'=>['error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D'])."\n", FILE_APPEND);
    // #endregion
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>


