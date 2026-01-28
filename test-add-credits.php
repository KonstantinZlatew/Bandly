<?php
// Simple test script to manually add credits to a user
// This helps verify the display is working

session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION["user_id"])) {
    die("Please log in first");
}

$userId = (int)$_SESSION["user_id"];

echo "<h2>Test: Add Credits Manually</h2>";
echo "<p>User ID: $userId</p>";

try {
    $pdo = db();
    
    // Ensure entitlements record exists
    $stmt = $pdo->prepare("
        INSERT INTO user_entitlements (user_id, credits_balance)
        VALUES (:user_id, 10)
        ON DUPLICATE KEY UPDATE credits_balance = credits_balance + 10
    ");
    $stmt->execute(["user_id" => $userId]);
    
    echo "<p style='color: green;'>✓ Added 10 credits</p>";
    
    // Fetch and display current entitlements
    $stmt = $pdo->prepare("SELECT * FROM user_entitlements WHERE user_id = :user_id");
    $stmt->execute(["user_id" => $userId]);
    $entitlements = $stmt->fetch();
    
    echo "<h3>Current Entitlements:</h3>";
    echo "<pre>";
    print_r($entitlements);
    echo "</pre>";
    
    echo "<p><a href='index.php'>Go to Homepage</a> to see if credits display correctly</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

