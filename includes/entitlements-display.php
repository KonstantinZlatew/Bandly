<?php
// This file should be included in pages to display user entitlements
// It fetches entitlements and displays them

if (!isset($_SESSION["user_id"])) {
    return;
}

require_once __DIR__ . "/../config/db.php";

$userId = (int)$_SESSION["user_id"];

try {
    // #region agent log
    file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_display1','timestamp'=>time()*1000,'location'=>'entitlements-display.php:13','message'=>'Display function called','data'=>['userId'=>$userId],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E'])."\n", FILE_APPEND);
    // #endregion
    
    $pdo = db();
    
    // Get user entitlements
    $stmt = $pdo->prepare("
        SELECT credits_balance, unlimited_until 
        FROM user_entitlements 
        WHERE user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute(["user_id" => $userId]);
    $entitlements = $stmt->fetch();
    
    // #region agent log
    file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_display2','timestamp'=>time()*1000,'location'=>'entitlements-display.php:24','message'=>'Entitlements query result','data'=>['found'=>!empty($entitlements),'creditsBalance'=>$entitlements['credits_balance']??null],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E'])."\n", FILE_APPEND);
    // #endregion
    
    if (!$entitlements) {
        // Create default entitlements if they don't exist
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_entitlements (user_id, credits_balance)
                VALUES (:user_id, 0)
            ");
            $stmt->execute(["user_id" => $userId]);
            $creditsBalance = 0;
            $unlimitedUntil = null;
        } catch (PDOException $e) {
            // If insert fails (e.g., duplicate key), try to fetch again
            $stmt = $pdo->prepare("
                SELECT credits_balance, unlimited_until 
                FROM user_entitlements 
                WHERE user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(["user_id" => $userId]);
            $entitlements = $stmt->fetch();
            if ($entitlements) {
                $creditsBalance = (int)($entitlements["credits_balance"] ?? 0);
                $unlimitedUntil = $entitlements["unlimited_until"] ?? null;
            } else {
                $creditsBalance = 0;
                $unlimitedUntil = null;
            }
        }
    } else {
        $creditsBalance = (int)($entitlements["credits_balance"] ?? 0);
        $unlimitedUntil = $entitlements["unlimited_until"] ?? null;
    }
    
    // Get active subscription if exists
    $stmt = $pdo->prepare("
        SELECT us.current_period_end, p.name as plan_name
        FROM user_subscriptions us
        JOIN plans p ON p.id = us.plan_id
        WHERE us.user_id = :user_id 
        AND us.status IN ('active', 'trialing')
        ORDER BY us.current_period_end DESC
        LIMIT 1
    ");
    $stmt->execute(["user_id" => $userId]);
    $subscription = $stmt->fetch();
    
    $hasUnlimited = false;
    $unlimitedUntilDate = null;
    $planName = null;
    
    if ($subscription) {
        $hasUnlimited = true;
        $unlimitedUntilDate = $subscription["current_period_end"];
        $planName = $subscription["plan_name"];
    } elseif ($unlimitedUntil) {
        $unlimitedDate = new DateTime($unlimitedUntil);
        $now = new DateTime();
        if ($unlimitedDate > $now) {
            $hasUnlimited = true;
            $unlimitedUntilDate = $unlimitedUntil;
        }
    }
}
catch (Exception $e) {
    // Log error for debugging (remove in production or use proper logging)
    error_log("Entitlements display error: " . $e->getMessage());
    $creditsBalance = 0;
    $hasUnlimited = false;
    $unlimitedUntilDate = null;
    $planName = null;
}
?>

<div class="entitlements-bar">
    <?php if ($hasUnlimited && $unlimitedUntilDate): ?>
        <div class="entitlement-item subscription">
            <span class="entitlement-label">Subscription:</span>
            <span class="entitlement-value">
                <?php echo htmlspecialchars($planName ?? "Unlimited"); ?>
                <?php 
                $endDate = new DateTime($unlimitedUntilDate);
                $now = new DateTime();
                $daysLeft = $now->diff($endDate)->days;
                if ($daysLeft > 0) {
                    echo " (expires in " . $daysLeft . " day" . ($daysLeft > 1 ? "s" : "") . ")";
                } else {
                    echo " (expires today)";
                }
                ?>
            </span>
        </div>
    <?php else: ?>
        <div class="entitlement-item credits">
            <span class="entitlement-label">Credits:</span>
            <span class="entitlement-value"><?php 
            // #region agent log
            file_put_contents(__DIR__ . '/../.cursor/debug.log', json_encode(['id'=>'log_'.time().'_display3','timestamp'=>time()*1000,'location'=>'entitlements-display.php:120','message'=>'Displaying credits','data'=>['creditsBalance'=>$creditsBalance],'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E'])."\n", FILE_APPEND);
            // #endregion
            echo $creditsBalance; ?></span>
        </div>
    <?php endif; ?>
</div>

