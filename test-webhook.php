<?php
// Test script to manually trigger webhook processing
// This simulates what Stripe would send

require_once realpath(__DIR__ . "/vendor/autoload.php");
require_once __DIR__ . "/config/db.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

if (!isset($_SESSION["user_id"])) {
    die("Please log in first");
}

$userId = (int)$_SESSION["user_id"];

echo "<h2>Testing Webhook Processing</h2>";
echo "<p>User ID: $userId</p>";

// Get a test plan
try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, code, plan_type, credits_amount, duration_days, price_cents FROM plans WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $plan = $stmt->fetch();
    
    if (!$plan) {
        die("No active plans found in database");
    }
    
    echo "<p>Testing with plan: " . htmlspecialchars($plan["code"]) . "</p>";
    
    // Simulate a checkout session object
    $mockSession = (object)[
        'id' => 'cs_test_' . time(),
        'payment_intent' => 'pi_test_' . time(),
        'amount_total' => (int)$plan["price_cents"],
        'currency' => 'eur',
        'payment_status' => 'paid',
        'metadata' => (object)[
            'user_id' => (string)$userId,
            'plan_code' => $plan["code"],
            'plan_id' => (string)$plan["id"]
        ]
    ];
    
    echo "<pre>";
    print_r($mockSession);
    echo "</pre>";
    
    // Include the webhook handler function
    require_once __DIR__ . "/api/stripe-webhook.php";
    
    // Call the handler directly
    echo "<h3>Processing...</h3>";
    handleCheckoutSessionCompleted($mockSession);
    
    echo "<p style='color: green;'>✓ Processing complete!</p>";
    
    // Check if purchase was created
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(["user_id" => $userId]);
    $purchase = $stmt->fetch();
    
    if ($purchase) {
        echo "<h3>Purchase Created:</h3>";
        echo "<pre>";
        print_r($purchase);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ No purchase record found</p>";
    }
    
    // Check entitlements
    $stmt = $pdo->prepare("SELECT * FROM user_entitlements WHERE user_id = :user_id");
    $stmt->execute(["user_id" => $userId]);
    $entitlements = $stmt->fetch();
    
    if ($entitlements) {
        echo "<h3>Entitlements:</h3>";
        echo "<pre>";
        print_r($entitlements);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ No entitlements record found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

