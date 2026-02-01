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

try {
    $pdo = db();
    
    // Check for active subscription
    $stmt = $pdo->prepare("
        SELECT us.id, us.current_period_end, p.name as plan_name, p.code as plan_code
        FROM user_subscriptions us
        JOIN plans p ON p.id = us.plan_id
        WHERE us.user_id = :user_id 
        AND us.status IN ('active', 'trialing')
        ORDER BY us.current_period_end DESC
        LIMIT 1
    ");
    $stmt->execute(["user_id" => $userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // Check if subscription is still valid (not expired)
        $endDate = new DateTime($subscription["current_period_end"]);
        $now = new DateTime();
        
        if ($endDate > $now) {
            json_response(200, [
                "ok" => true,
                "has_subscription" => true,
                "plan_name" => $subscription["plan_name"],
                "plan_code" => $subscription["plan_code"],
                "expires_at" => $subscription["current_period_end"]
            ]);
        } else {
            // Subscription expired
            json_response(200, [
                "ok" => true,
                "has_subscription" => false
            ]);
        }
    } else {
        json_response(200, [
            "ok" => true,
            "has_subscription" => false
        ]);
    }
} catch (PDOException $e) {
    json_response(500, ["ok" => false, "error" => "Server error"]);
}

