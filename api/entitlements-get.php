<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/db.php";
session_start();

function json_response(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    json_response(401, ["ok" => false, "error" => "Not authenticated"]);
}

$userId = (int)$_SESSION["user_id"];

try {
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
    
    if (!$entitlements) {
        // Create default entitlements if they don't exist
        $stmt = $pdo->prepare("
            INSERT INTO user_entitlements (user_id, credits_balance)
            VALUES (:user_id, 0)
        ");
        $stmt->execute(["user_id" => $userId]);
        
        $entitlements = [
            "credits_balance" => 0,
            "unlimited_until" => null
        ];
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
    
    $creditsBalance = (int)($entitlements["credits_balance"] ?? 0);
    $unlimitedUntil = $entitlements["unlimited_until"] ?? null;
    $subscriptionEnd = $subscription["current_period_end"] ?? null;
    $planName = $subscription["plan_name"] ?? null;
    
    // Use subscription end date if available, otherwise use unlimited_until
    $hasUnlimited = false;
    $unlimitedUntilDate = null;
    
    if ($subscriptionEnd) {
        $hasUnlimited = true;
        $unlimitedUntilDate = $subscriptionEnd;
    } elseif ($unlimitedUntil) {
        $unlimitedDate = new DateTime($unlimitedUntil);
        $now = new DateTime();
        if ($unlimitedDate > $now) {
            $hasUnlimited = true;
            $unlimitedUntilDate = $unlimitedUntil;
        }
    }
    
    json_response(200, [
        "ok" => true,
        "credits_balance" => $creditsBalance,
        "has_unlimited" => $hasUnlimited,
        "unlimited_until" => $unlimitedUntilDate,
        "subscription_plan" => $planName
    ]);
} catch (PDOException $e) {
    json_response(500, ["ok" => false, "error" => "Server error"]);
}

