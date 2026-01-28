<?php
// Debug script to check entitlements
session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION["user_id"])) {
    die("Not logged in");
}

$userId = (int)$_SESSION["user_id"];

echo "<h2>Debug Entitlements for User ID: $userId</h2>";

try {
    $pdo = db();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :user_id");
    $stmt->execute(["user_id" => $userId]);
    $user = $stmt->fetch();
    echo "<p><strong>User:</strong> " . ($user ? htmlspecialchars($user["username"]) : "NOT FOUND") . "</p>";
    
    // Check entitlements
    $stmt = $pdo->prepare("SELECT * FROM user_entitlements WHERE user_id = :user_id");
    $stmt->execute(["user_id" => $userId]);
    $entitlements = $stmt->fetch();
    
    echo "<h3>Entitlements Record:</h3>";
    if ($entitlements) {
        echo "<pre>";
        print_r($entitlements);
        echo "</pre>";
        echo "<p><strong>Credits Balance:</strong> " . ($entitlements["credits_balance"] ?? "NULL") . "</p>";
        echo "<p><strong>Unlimited Until:</strong> " . ($entitlements["unlimited_until"] ?? "NULL") . "</p>";
    } else {
        echo "<p style='color: red;'><strong>NO ENTITLEMENTS RECORD FOUND</strong></p>";
        echo "<p>Creating entitlements record...</p>";
        
        $stmt = $pdo->prepare("INSERT INTO user_entitlements (user_id, credits_balance) VALUES (:user_id, 0)");
        $stmt->execute(["user_id" => $userId]);
        echo "<p style='color: green;'>Entitlements record created!</p>";
        
        // Fetch again
        $stmt = $pdo->prepare("SELECT * FROM user_entitlements WHERE user_id = :user_id");
        $stmt->execute(["user_id" => $userId]);
        $entitlements = $stmt->fetch();
        echo "<pre>";
        print_r($entitlements);
        echo "</pre>";
    }
    
    // Check subscriptions
    $stmt = $pdo->prepare("
        SELECT us.*, p.name as plan_name
        FROM user_subscriptions us
        JOIN plans p ON p.id = us.plan_id
        WHERE us.user_id = :user_id
    ");
    $stmt->execute(["user_id" => $userId]);
    $subscriptions = $stmt->fetchAll();
    
    echo "<h3>Subscriptions:</h3>";
    if ($subscriptions) {
        echo "<pre>";
        print_r($subscriptions);
        echo "</pre>";
    } else {
        echo "<p>No subscriptions found</p>";
    }
    
    // Check purchases
    $stmt = $pdo->prepare("
        SELECT p.*, pl.name as plan_name
        FROM purchases p
        JOIN plans pl ON pl.id = p.plan_id
        WHERE p.user_id = :user_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(["user_id" => $userId]);
    $purchases = $stmt->fetchAll();
    
    echo "<h3>Purchases:</h3>";
    if ($purchases) {
        echo "<pre>";
        print_r($purchases);
        echo "</pre>";
    } else {
        echo "<p>No purchases found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

